<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\CnpjLookupService;
use App\Services\CnpjValidator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly CnpjLookupService $cnpjService) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function lookupCnpj(Request $request): JsonResponse
    {
        $request->validate([
            'cnpj' => ['required', 'string', 'max:18'],
        ]);

        if (! CnpjValidator::isValid((string) $request->input('cnpj'))) {
            return response()->json(['error' => 'CNPJ inválido. CPF não é aceito.'], 422);
        }

        $result = $this->cnpjService->lookup((string) $request->input('cnpj'));

        if ($result === null) {
            return response()->json([
                'error' => 'Consulta indisponível. Preencha os dados manualmente; o cadastro ficará pendente de análise.',
                'manual_review' => true,
            ], 503);
        }

        return response()->json($result);
    }

    public function store(RegisterCompanyRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $cnpjData = $this->cnpjService->lookup($validated['cnpj']);
        $approved = $cnpjData !== null
            && $this->cnpjService->canAutoApprove($cnpjData, $validated['dominio_corporativo']);

        $user = DB::transaction(function () use ($validated, $approved): User {
            $company = Company::create([
                'nome_fantasia' => $validated['nome_fantasia'],
                'razao_social' => $validated['razao_social'],
                'cnpj' => $validated['cnpj'],
                'cnpj_verificado_em' => $approved ? now() : null,
                'status' => $approved,
                'registration_status' => $approved ? 'approved' : 'pending',
                'cep' => $validated['cep'] ?? null,
                'logradouro' => $validated['logradouro'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'] ?? null,
                'cidade' => $validated['cidade'] ?? null,
                'estado' => $validated['estado'] ?? null,
                'telefone' => $validated['telefone'] ?? null,
                'dominio_corporativo' => $validated['dominio_corporativo'],
                'email_corporativo' => $validated['email_corporativo'],
                'nome_administrador' => $validated['name'],
                'email_administrador' => $validated['email'],
                'email_recuperacao_secundario' => $validated['email_recuperacao_secundario'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->forceFill(['company_id' => $company->id])->save();
            $user->companies()->attach($company->id);

            $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
            $user->assignRole($role);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
