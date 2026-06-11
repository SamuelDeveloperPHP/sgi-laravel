<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteOnboardingRequest;
use App\Models\Company;
use App\Services\CnpjValidator;
use App\Services\ReceitaWsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public function __construct(private readonly ReceitaWsService $receitaWs)
    {
    }

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

    /**
     * Endpoint AJAX para lookup de CNPJ na ReceitaWS.
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

        // Validação local primeiro — não desperdiça quota da ReceitaWS
        // se o CNPJ é obviamente inválido
        if (!CnpjValidator::isValid($cnpj)) {
            return response()->json([
                'error' => 'CNPJ inválido.',
            ], 422);
        }

        $data = $this->receitaWs->lookup($cnpj);

        if (!$data) {
            return response()->json([
                'error' => 'Não foi possível consultar este CNPJ. Preencha manualmente.',
            ], 404);
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
                $company = Company::create([
                    'nome_fantasia' => $request->validated('nome_fantasia'),
                    'razao_social' => $request->validated('razao_social'),
                    'cnpj' => $request->validated('cnpj'),
                    'status' => true,
                ]);

                // Vincula usuário à empresa
                $user->company_id = $company->id;
                $user->save();

                // Também vincula via pivot (companies pode ter relação N:N)
                $user->companies()->syncWithoutDetaching([$company->id]);

                // Atribui role default "Administrador da Empresa" — primeiro
                // usuário da empresa vira admin dela. Se a role não existir,
                // ignora silenciosamente (não bloqueia onboarding).
                $defaultRole = Role::where('name', 'Administrador da Empresa')->first();
                if ($defaultRole) {
                    $user->assignRole($defaultRole);
                }

                Log::info('Onboarding concluído', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                ]);

                return $company->id;
            });

            return redirect()->route('dashboard')
                ->with('success', 'Bem-vindo! Sua empresa foi cadastrada com sucesso.');
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
