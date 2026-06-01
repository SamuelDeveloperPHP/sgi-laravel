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
            ],
        ];
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
