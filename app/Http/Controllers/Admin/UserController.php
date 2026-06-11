<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = User::with('companies');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        $masterCount = User::where('is_master_admin', true)->count();
        $standardCount = User::where('is_master_admin', false)->count();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
            'metrics' => [
                'master' => $masterCount,
                'standard' => $standardCount,
                'total' => $masterCount + $standardCount,
            ]
        ]);
    }

    private function getFormProps(User $user = null)
    {
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

        return [
            'user' => $user ? $user->load('companies', 'permissions', 'roles') : new User(),
            'companies' => Company::orderBy('nome_fantasia')->get(['id', 'nome_fantasia']),
            'roles' => Role::all(),
            'modules' => $modules,
            'isEdit' => $user !== null
        ];
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Form', $this->getFormProps());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', Rules\Password::defaults()],
            'companies' => 'nullable|array',
            'companies.*' => 'exists:companies,id',
            'is_active' => 'boolean',
            'role' => 'nullable|string|exists:roles,name',
            'permissions' => 'nullable|array',
            // Defesa em profundidade contra escalada de privilégio: bloqueia
            // explicitamente o flag is_master_admin no body. A criação de
            // master admins deve ser feita por seeder ou comando artisan,
            // nunca via HTTP.
            'is_master_admin' => 'prohibited',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (isset($validated['companies'])) {
            $user->companies()->sync($validated['companies']);
        }

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        } else {
            $user->syncRoles([]);
        }

        if (isset($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.users.index')->with('message', 'Usuário criado com sucesso!');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Form', $this->getFormProps($user));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class.',email,'.$user->id,
            'companies' => 'nullable|array',
            'companies.*' => 'exists:companies,id',
            'is_active' => 'boolean',
            'role' => 'nullable|string|exists:roles,name',
            'permissions' => 'nullable|array',
            // Defesa em profundidade contra escalada de privilégio: ver store().
            'is_master_admin' => 'prohibited',
        ];

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

        if (isset($validated['companies'])) {
            $user->companies()->sync($validated['companies']);
        } else {
            $user->companies()->detach();
        }

        if (isset($validated['role']) && $validated['role'] !== '') {
            $user->syncRoles([$validated['role']]);
        } else {
            $user->syncRoles([]);
        }

        if (isset($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()->route('admin.users.index')->with('message', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Você não pode excluir a si mesmo.']);
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('message', 'Usuário excluído com sucesso!');
    }
}
