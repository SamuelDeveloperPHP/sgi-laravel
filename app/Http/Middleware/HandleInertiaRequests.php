<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * NOTA DE SEGURANÇA (auditoria multiempresa Fase 2):
     * Antes desta refatoração, $request->user()->toArray() exportava TODOS
     * os campos do model User para o frontend, incluindo:
     *   - company_id, legacy_adms_user_id, is_active, created_at, updated_at
     * que não são usados pelo frontend e expunham metadados internos
     * desnecessariamente.
     *
     * Agora usamos whitelist explícita. Adicionar um novo campo ao User
     * NÃO o expõe automaticamente — precisa ser adicionado aqui de forma
     * consciente.
     *
     * O bloco `tenant` (separado) carrega o nome da empresa ativa para
     * exibição na UI. Quando a "troca de tenant na sessão" for
     * implementada (Fase 3), este bloco passará a refletir o tenant
     * ativo da sessão e não apenas o users.company_id legado.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user'   => $this->buildUserPayload($user),
                'tenant' => $this->buildTenantPayload($user),
                'notifications' => $user ? $user->unreadNotifications()->take(5)->get() : []
            ],
            'navigation' => $this->buildNavigation($user),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ]
        ];
    }

    /**
     * Carrega a arvore de navegacao do banco (Modules).
     *
     * REGRA DE FILTRAGEM POR PAPEL (memoria sgi-laravel-access-rules):
     *   - Master admin: ve TODOS os modulos
     *   - Outros usuarios: ve tudo EXCETO os slugs em $masterOnlySlugs
     *     (modulo Projetos e modulo de gerenciamento de Modulos)
     *
     * Tambem oculta itens cujo usuario nao tem permission Spatie
     * correspondente (defesa em camadas — backend tambem bloqueia
     * via Policies/middleware, mas nao mostra na UI o que ele nao
     * pode usar).
     */
    protected function buildNavigation(?\App\Models\User $user = null): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('modules')) {
            return [];
        }

        $isMasterAdmin = $user?->is_master_admin === true;

        // Slugs visiveis APENAS para master admin
        $masterOnlySlugs = [
            'view-projetos',
            'manage-modules',
            'manage-companies',
            'iso-9001', // slug do modulo pai criado pelo ModuleSeeder
        ];

        $modules = \App\Models\Module::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where('is_visible_in_menu', true)
            ->orderBy('order')
            ->get();

        return $modules
            ->filter(function ($mod) use ($isMasterAdmin, $masterOnlySlugs) {
                // Filtra modulos restritos a master admin
                if (!$isMasterAdmin && in_array($mod->slug, $masterOnlySlugs, true)) {
                    return false;
                }
                return true;
            })
            ->map(function ($mod) use ($user, $isMasterAdmin) {
                return [
                    'name' => $mod->name,
                    'href' => $mod->route_name,
                    'icon' => $mod->icon,
                    'permission' => $mod->slug,
                    'children' => $mod->children
                        ->where('is_active', true)
                        ->where('is_visible_in_menu', true)
                        ->filter(function ($child) use ($user, $isMasterAdmin) {
                            // Master admin ve todos os filhos
                            if ($isMasterAdmin) {
                                return true;
                            }
                            // Usuario sem login: sem filtro extra (so vai
                            // chegar aqui se navigation for renderizado
                            // em layout publico, o que nao deveria)
                            if (!$user) {
                                return true;
                            }
                            // Usuario logado: precisa ter a permission Spatie
                            // que casa com o slug do modulo
                            return $user->can($child->slug);
                        })
                        ->map(function ($child) {
                            return [
                                'name' => $child->name,
                                'href' => $child->route_name,
                                'permission' => $child->slug,
                                'icon' => $child->icon,
                            ];
                        })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Whitelist explícita de campos do User que vão para o frontend.
     * Não retornar campos sensíveis ou de uso interno.
     */
    protected function buildUserPayload(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'is_master_admin'   => (bool) $user->is_master_admin,
            'roles'             => $user->getRoleNames(),
            'permissions'       => $user->getAllPermissions()->pluck('name'),
        ];
    }

    /**
     * Carrega dados resumidos da empresa atual do usuário.
     * Master admin sem company_id retorna null (UI deve indicar
     * "Global / Todas as empresas" para esse caso).
     */
    protected function buildTenantPayload(?User $user): ?array
    {
        if (!$user || !$user->company_id) {
            return null;
        }

        $company = Company::select('id', 'nome_fantasia', 'razao_social')
            ->find($user->company_id);

        if (!$company) {
            return null;
        }

        return [
            'id'            => $company->id,
            'nome_fantasia' => $company->nome_fantasia,
            'razao_social'  => $company->razao_social,
        ];
    }
}
