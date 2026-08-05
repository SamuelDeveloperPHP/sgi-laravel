<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Rules\CorporateEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * 5 roles canonicas que Administrador da empresa pode atribuir.
     * Master Admin NAO esta nesta lista — e setado por seeder/comando.
     * Ver memoria sgi-laravel-access-rules item 5.
     */
    private const ALLOWED_ROLES_FOR_ADMIN = [
        'Administrador',
        'Analista da Qualidade',
        'Tecnico da Qualidade',
        'Enfermeiros',
        'Tecnicos de Enfermagem',
    ];

    public function index(Request $request)
    {
        $this->authorizeManagementAccess();
        $authUser = $request->user();
        $isMaster = (bool) $authUser?->is_master_admin;
        $search = $request->input('search');

        $query = User::with('companies');

        // Filtragem por empresa (memoria sgi-laravel-access-rules
        // item 6): Administrador da empresa so ve usuarios da SUA
        // empresa. Master admin ve TODOS os usuarios de TODAS as
        // empresas.
        if (!$isMaster) {
            $companyId = $authUser->company_id;
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhereHas('companies', fn ($qq) => $qq->where('companies.id', $companyId));
            });
            // Esconde master admins da listagem para nao-master:
            // master e papel transversal de sistema, nao gerenciavel
            // pelos administradores de empresa.
            $query->where('is_master_admin', false);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        // Metricas tambem scoped por papel
        if ($isMaster) {
            $masterCount = User::where('is_master_admin', true)->count();
            $standardCount = User::where('is_master_admin', false)->count();
        } else {
            $companyId = $authUser->company_id;
            $masterCount = 0; // nao mostrado para non-master
            $standardCount = User::where('is_master_admin', false)
                ->where('company_id', $companyId)
                ->count();
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
            'metrics' => [
                'master' => $masterCount,
                'standard' => $standardCount,
                'total' => $masterCount + $standardCount,
            ],
            'isMasterAdmin' => $isMaster,
        ]);
    }

    private function getFormProps(User $user = null)
    {
        $authUser = auth()->user();
        $isMaster = (bool) $authUser?->is_master_admin;
        // Apenas verbos canonicos sao considerados "acoes" na matriz de
        // privilegios. Permissions que nao comecam com um deles (ex:
        // 'iso-9001' que e slug de modulo pai criado pelo ModuleSeeder,
        // nao uma acao granular) sao excluidas para nao distorcer o
        // agrupamento. A gestao desses modulos pais e feita em
        // /admin/modules, nao nesta matriz.
        $actionVerbs = ['view', 'list', 'create', 'edit', 'delete', 'manage'];

        $allPermissions = Permission::all()
            ->filter(function ($item) use ($actionVerbs) {
                $parts = explode('-', $item->name);
                return isset($parts[1]) && in_array($parts[0], $actionVerbs, true);
            })
            ->groupBy(function ($item) {
                // Reune tudo apos a acao como recurso. Ex:
                //   'view-nossa-historia' -> 'Nossa-historia'
                //   'manage-controle-documentos' -> 'Controle-documentos'
                $parts = explode('-', $item->name);
                $resource = implode('-', array_slice($parts, 1));
                return ucfirst($resource);
            });

        // Format permissions for matrix
        $modules = [];
        foreach($allPermissions as $moduleName => $perms) {
            $modules[] = [
                // CAST EXPLICITO PARA STRING: PHP arrays convertem
                // strings numericas (ex: '9001') em INT automaticamente
                // quando viram chaves. Sem este cast, mod.name no React
                // virava number e .toLowerCase() quebrava. Mantemos o
                // String() wrap no JSX como defesa em profundidade.
                'name' => (string) $moduleName,
                'permissions' => $perms->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'action' => explode('-', $p->name)[0] // view, create, edit, delete, manage
                    ];
                })->values()
            ];
        }

        // Master admin: escolhe entre todas as roles + todas as empresas
        // Administrador da empresa: roles limitadas as 5 + apenas SUA
        // empresa nas opcoes
        if ($isMaster) {
            $availableRoles = Role::orderBy('name')->get();
            $availableCompanies = Company::orderBy('nome_fantasia')->get(['id', 'nome_fantasia']);
        } else {
            $availableRoles = Role::whereIn('name', self::ALLOWED_ROLES_FOR_ADMIN)
                ->orderBy('name')
                ->get();
            $availableCompanies = Company::where('id', $authUser->company_id)
                ->get(['id', 'nome_fantasia']);
        }

        return [
            'user' => $user ? $user->load('companies', 'permissions', 'roles') : new User(),
            'companies' => $availableCompanies,
            'roles' => $availableRoles,
            'modules' => $modules,
            'isEdit' => $user !== null,
            'isMasterAdmin' => $isMaster,
        ];
    }

    public function create()
    {
        $this->authorizeManagementAccess();
        return Inertia::render('Admin/Users/Form', $this->getFormProps());
    }

    public function store(Request $request)
    {
        $this->authorizeManagementAccess();
        $authUser = $request->user();
        $isMaster = (bool) $authUser?->is_master_admin;

        // Validacao base. Para non-master, restringe roles e
        // companies aos valores permitidos.
        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'password'  => ['required', Rules\Password::defaults()],
            'companies' => 'nullable|array',
            'is_active' => 'boolean',
            'role'      => 'nullable|string',
            'permissions' => 'nullable|array',
            // Defesa em profundidade contra escalada de privilegio:
            // bloqueia explicitamente is_master_admin no body. A
            // criacao de master admins e via seeder/comando, nao HTTP.
            'is_master_admin' => 'prohibited',
        ];

        if ($isMaster) {
            // Master: pode escolher qualquer role e qualquer company
            $rules['role'] = 'nullable|string|exists:roles,name';
            $rules['companies.*'] = 'exists:companies,id';
            // Email globalmente unico para master
            $selectedCompany = isset($request->companies[0])
                ? Company::find($request->companies[0])
                : null;
        } else {
            // Non-master: role obrigatoria, restrita as 5 do negocio
            $allowedRoles = implode(',', self::ALLOWED_ROLES_FOR_ADMIN);
            $rules['role'] = "required|string|in:{$allowedRoles}";
            // Companies: aceita SO a do criador (ignora outros valores)
            $rules['companies.*'] = 'in:' . $authUser->company_id;
            // Email unique por empresa (composite UNIQUE no DB
            // suporta isso; aqui validamos antes para mensagem amigavel)
            $selectedCompany = Company::find($authUser->company_id);
        }

        $rules['email'][] = new CorporateEmail($selectedCompany?->dominio_corporativo);
        $rules['email'][] = Rule::unique('users', 'email');

        $validated = $request->validate($rules);

        // company_id: master pode setar via body (futuro), non-master
        // SEMPRE pega do seu proprio company_id (impede criar
        // usuarios em outra empresa)
        $companyId = $isMaster
            ? ($validated['companies'][0] ?? $authUser->company_id)
            : $authUser->company_id;

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);
        // company_id nao esta no $fillable do User por seguranca —
        // setado via forceFill (ver memoria sgi-laravel-multi-tenant)
        $user->forceFill(['company_id' => $companyId])->save();

        // Sincroniza pivot company_user (suporta futura troca de
        // tenant). Para non-master a unica empresa permitida e a dele.
        $user->companies()->sync([$companyId]);

        if (isset($validated['role']) && $validated['role'] !== '') {
            $user->syncRoles([$validated['role']]);
        } else {
            $user->syncRoles([]);
        }

        // Permissions granulares: master pode ajustar livremente;
        // non-master nao mexe em permissions diretamente (a role ja
        // determina). Ignora silenciosamente se enviado.
        if ($isMaster && isset($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso!');
    }

    public function edit(User $user)
    {
        $this->authorizeManagementAccess();
        $this->authorizeManage($user);
        return Inertia::render('Admin/Users/Form', $this->getFormProps($user));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManagementAccess();
        $this->authorizeManage($user);

        $authUser = $request->user();
        $isMaster = (bool) $authUser?->is_master_admin;

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'companies' => 'nullable|array',
            'is_active' => 'boolean',
            'role'      => 'nullable|string',
            'permissions' => 'nullable|array',
            // Defesa em profundidade contra escalada de privilegio: ver store().
            'is_master_admin' => 'prohibited',
        ];

        if ($isMaster) {
            $rules['role'] = 'nullable|string|exists:roles,name';
            $rules['companies.*'] = 'exists:companies,id';
            $selectedCompany = isset($request->companies[0])
                ? Company::find($request->companies[0])
                : Company::find($user->company_id);
        } else {
            $allowedRoles = implode(',', self::ALLOWED_ROLES_FOR_ADMIN);
            $rules['role'] = "required|string|in:{$allowedRoles}";
            $rules['companies.*'] = 'in:' . $authUser->company_id;
            // Unique por empresa (excluindo o proprio user na edicao)
            $selectedCompany = Company::find($authUser->company_id);
        }

        $rules['email'][] = new CorporateEmail($selectedCompany?->dominio_corporativo);
        $rules['email'][] = Rule::unique('users', 'email')->ignore($user->id);

        if ($request->filled('password')) {
            $rules['password'] = [Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $validated['is_active'] ?? true;

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Master admin pode trocar a empresa do usuario; non-master
        // nao mexe (usuario continua na empresa dele)
        if ($isMaster && isset($validated['companies'])) {
            $user->companies()->sync($validated['companies']);
            if (!empty($validated['companies'])) {
                $user->forceFill(['company_id' => $validated['companies'][0]])->save();
            }
        }

        if (isset($validated['role']) && $validated['role'] !== '') {
            $user->syncRoles([$validated['role']]);
        } else {
            $user->syncRoles([]);
        }

        // Permissions granulares: so master ajusta diretamente
        if ($isMaster) {
            if (isset($validated['permissions'])) {
                $user->syncPermissions($validated['permissions']);
            } else {
                $user->syncPermissions([]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        $this->authorizeManagementAccess();
        $this->authorizeManage($user);

        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Você não pode excluir a si mesmo.']);
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuário excluído com sucesso!');
    }

    /**
     * Verifica se o usuario autenticado pode gerenciar o $user alvo.
     *
     *   - Master admin: pode gerenciar qualquer usuario
     *   - Administrador da empresa: pode gerenciar APENAS usuarios
     *     da SUA empresa, e NUNCA pode mexer em master admins
     *
     * Aborta 403 caso violacao. Defesa em camadas: bloqueia mesmo
     * que a UI deixe vazar uma URL/payload incorreta.
     */
    private function authorizeManage(User $user): void
    {
        $authUser = auth()->user();
        if (!$authUser) {
            abort(403);
        }
        if ($authUser->is_master_admin) {
            return; // master admin sempre pode
        }
        // Non-master: nao pode mexer em master admin
        if ($user->is_master_admin) {
            abort(403, 'Você não pode gerenciar Administradores Master.');
        }
        // Non-master: alvo precisa ser da mesma empresa
        if ($user->company_id !== $authUser->company_id) {
            abort(403, 'Usuário pertence a outra empresa.');
        }
    }

    private function authorizeManagementAccess(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->is_master_admin && !$user->hasRole('Administrador'))) {
            abort(403, 'Apenas o Administrador da empresa pode gerenciar acessos.');
        }
    }
}
