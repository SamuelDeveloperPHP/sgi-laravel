<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteOnboardingRequest;
use App\Models\Company;
use App\Services\CnpjLookupService;
use App\Services\CnpjValidator;
use App\Support\CompanyModuleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Onboarding obrigatório de empresa para novos usuários.
 *
 * Fluxo:
 *   1. Usuário se registra em /register (já implementado)
 *   2. Após verificar email, qualquer rota que requeira company_id
 *      o redireciona para /onboarding/company (middleware RequireCompany)
 *   3. Usuário preenche o formulário (com lookup opcional de CNPJ)
 *   4. POST /onboarding/company cria a Company em transação atômica,
 *      vincula users.company_id e atribui role default
 *   5. Redireciona para /dashboard
 *
 * Segurança:
 *   - Validação de CNPJ via checksum (CnpjValidator)
 *   - UNIQUE constraint no banco em companies.cnpj
 *   - Transação atômica: Company + atualização do User + role
 *   - Rate limit já aplicado nas rotas (throttle:register)
 *
 * Nota: master admin nunca passa por este fluxo — ele é criado via
 * seeder/comando com is_master_admin=true e company_id pode ser null.
 */
class OnboardingController extends Controller
{
    public function __construct(private readonly CnpjLookupService $cnpjService) {}

    /**
     * Renderiza o formulário de onboarding.
     * Se o usuário já tem empresa, devolve ele para o dashboard
     * (evita uso do endpoint para tentar trocar de empresa).
     */
    public function showCompanyForm(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Se já tem company, não deveria estar aqui
        if ($user->company_id) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Company', [
            'userName' => $user->name,
            'userEmail' => $user->email,
        ]);
    }

    public function pending(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->company_id) {
            return redirect()->route('onboarding.company');
        }

        if ($user->company?->status && $user->company?->registration_status === 'approved') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Pending', [
            'companyName' => $user->company?->nome_fantasia,
            'registrationStatus' => $user->company?->registration_status,
            'reviewReason' => $user->company?->registration_review_reason,
        ]);
    }

    /**
     * Endpoint AJAX para lookup de CNPJ na APIBrasil.
     * Retorna dados sanitizados ou 404 se não encontrar/falhar.
     *
     * NÃO confiamos no resultado para criar a empresa automaticamente
     * — apenas pré-preenchemos os inputs no frontend para que o
     * usuário confirme/edite e submeta.
     */
    public function lookupCnpj(Request $request): JsonResponse
    {
        $request->validate([
            'cnpj' => ['required', 'string', 'max:18'],
        ]);

        $cnpj = $request->input('cnpj');

        // Validação local primeiro — não desperdiça quota da APIBrasil
        // se o CNPJ é obviamente inválido
        if (! CnpjValidator::isValid($cnpj)) {
            return response()->json([
                'error' => 'CNPJ inválido.',
            ], 422);
        }

        $data = $this->cnpjService->lookup($cnpj);

        if (! $data) {
            return response()->json([
                'error' => 'Não foi possível consultar este CNPJ na APIBrasil.',
            ], 503);
        }

        return response()->json($data);
    }

    /**
     * Conclui o onboarding: cria a empresa, vincula ao usuário e
     * atribui role padrão. Tudo em transação para garantir consistência.
     */
    public function completeOnboarding(CompleteOnboardingRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Defesa adicional contra reuso: se o usuário já tem company,
        // bloqueia (evita criar empresa-fantasma ou trocar tenant
        // via este endpoint)
        if ($user->company_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Você já está vinculado a uma empresa.');
        }

        try {
            $companyId = DB::transaction(function () use ($request, $user) {
                $validated = $request->validated();

                $cnpjData = $this->cnpjService->lookup($validated['cnpj']);
                $approved = $cnpjData !== null
                    && $this->cnpjService->canAutoApprove($cnpjData, $validated['dominio_corporativo']);

                // Cria a empresa com TODOS os dados validados +
                // nome/email do administrador setados pelo backend
                // (nao confiavel via form input - sao tomados do
                // proprio user autenticado para garantir consistencia)
                $company = Company::create([
                    // Identificacao
                    'nome_fantasia' => $validated['nome_fantasia'],
                    'razao_social' => $validated['razao_social'],
                    'cnpj' => $validated['cnpj'],
                    'cnpj_verificado_em' => $approved ? now() : null,
                    'status' => $approved,
                    'registration_status' => $approved ? 'approved' : 'pending',

                    // Endereco (campos opcionais via ?? null)
                    'cep' => $validated['cep'] ?? null,
                    'logradouro' => $validated['logradouro'] ?? null,
                    'numero' => $validated['numero'] ?? null,
                    'complemento' => $validated['complemento'] ?? null,
                    'bairro' => $validated['bairro'] ?? null,
                    'cidade' => $validated['cidade'] ?? null,
                    'estado' => $validated['estado'] ?? null,

                    // Contato corporativo
                    'email_corporativo' => $validated['email_corporativo'] ?? null,
                    'telefone' => $validated['telefone'] ?? null,
                    'dominio_corporativo' => $validated['dominio_corporativo'],

                    // Rastreabilidade: quem cadastrou a empresa
                    // (regra de negocio: bloquear ressubmissao com
                    // outro email para mesmo CNPJ - como CNPJ ja e
                    // unique, isso so vale para rastreamento/audit)
                    'nome_administrador' => $user->name,
                    'email_administrador' => $user->email,
                    'email_recuperacao_secundario' => $validated['email_recuperacao_secundario'],

                    'observacoes' => $validated['observacoes'] ?? null,
                ]);
                CompanyModuleAccess::syncDefaultsFor($company);

                // Vincula usuário à empresa
                $user->company_id = $company->id;
                $user->save();

                // Vincula via pivot company_user (N:N para suportar
                // futuro tenant switching). Master admin nao precisa
                // disso pois o relacionamento e sobrescrito no model.
                $user->companies()->syncWithoutDetaching([$company->id]);

                // Atribui role 'Administrador' (memoria sgi-laravel-
                // access-rules item 5). E o papel padrao de quem
                // cadastrou a empresa. Se a role nao existir
                // (BusinessRolesSeeder nao rodou), ignora.
                $defaultRole = Role::where('name', 'Administrador')->first();
                if ($defaultRole) {
                    $user->assignRole($defaultRole);
                }

                Log::info('Onboarding concluido', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'company_id' => $company->id,
                    'cnpj' => $company->cnpj,
                ]);

                return $company->id;
            });

            return redirect()->route('dashboard')
                ->with('success', 'Bem-vindo! Sua empresa foi cadastrada com sucesso.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Falha no onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('cnpj'))
                ->with('error', 'Não foi possível concluir o cadastro. Tente novamente.');
        }
    }
}
