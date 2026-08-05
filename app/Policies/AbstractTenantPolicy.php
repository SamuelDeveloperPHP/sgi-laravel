<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy abstrata para models tenant-scoped (que usam a trait Tenantable).
 *
 * Centraliza o padrão "master admin bypassa tudo; demais comparam company_id".
 *
 * Subclasses concretas (ProjetoPolicy, NaoConformidadePolicy, etc.) podem
 * ser apenas `class FooPolicy extends AbstractTenantPolicy {}` quando o
 * model não tem regras adicionais, ou sobrescrever métodos específicos
 * (ex.: bloquear delete de NCs já aprovadas, exigir role específica para
 * fechar PA, etc.).
 *
 * IMPORTANTE: esta Policy é uma SEGUNDA CAMADA de proteção. A primeira
 * camada é a trait Tenantable, que aplica TenantScope automaticamente
 * em todas as queries Eloquent. A Policy é defesa em profundidade contra:
 *   - controllers que usem withoutGlobalScopes() acidentalmente
 *   - refatorações futuras que removam Tenantable
 *   - acessos via route-model binding em casos extremos
 */
abstract class AbstractTenantPolicy
{
    /**
     * Identificador usado nas permissions Spatie deste recurso.
     *
     * Cada policy concreta deve definir explicitamente este valor. O valor
     * vazio falha fechado: nenhum usuario comum recebe acesso por acidente.
     */
    protected string $permissionResource = '';

    /**
     * Hook before(): permite que master admin bypasse TODAS as checagens.
     *
     * Retornando true aqui faz o Gate aceitar imediatamente, sem chamar
     * o método específico (view, update, etc.).
     * Retornando null delega para o método específico.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_master_admin) {
            return true;
        }
        return null;
    }

    /**
     * Listagem: qualquer usuário autenticado com tenant válido pode listar.
     * O TenantScope filtra automaticamente os resultados ao tenant do user.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasTenant($user) && $this->hasPermission($user, 'view');
    }

    /**
     * Visualização: usuário pode ver se o registro pertence ao seu tenant.
     */
    public function view(User $user, Model $model): bool
    {
        return $this->sameTenant($user, $model) && $this->hasPermission($user, 'view');
    }

    /**
     * Criação: usuário autenticado com tenant válido pode criar.
     * Tenantable carimba company_id automaticamente no creating.
     */
    public function create(User $user): bool
    {
        return $this->hasTenant($user) && $this->hasPermission($user, 'create');
    }

    /**
     * Edição: usuário pode editar se o registro pertence ao seu tenant.
     */
    public function update(User $user, Model $model): bool
    {
        return $this->sameTenant($user, $model) && $this->hasPermission($user, 'edit');
    }

    /**
     * Exclusão (soft delete): usuário pode excluir se for do mesmo tenant.
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->sameTenant($user, $model) && $this->hasPermission($user, 'delete');
    }

    /**
     * Restauração de soft delete: usuário do mesmo tenant.
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->sameTenant($user, $model) && $this->hasPermission($user, 'edit');
    }

    /**
     * Exclusão FÍSICA (forceDelete): bloqueada por padrão. Só master admin
     * (via hook before) pode fazer. Sobrescreva se quiser permitir a
     * outros perfis com cuidado.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }

    /**
     * Helper interno: compara company_id do user com o do model.
     * Ambos devem ser não-nulos.
     */
    protected function sameTenant(User $user, Model $model): bool
    {
        if ($user->company_id === null || $model->company_id === null) {
            return false;
        }

        return (int) $user->company_id === (int) $model->company_id;
    }

    protected function hasTenant(User $user): bool
    {
        return $user->company_id !== null;
    }

    /**
     * Usa Gate::can em vez de hasPermissionTo diretamente. Alem de integrar
     * roles e permissions, o Gate falha fechado quando a permission ainda nao
     * existe no banco, evitando transformar uma negacao em erro HTTP 500.
     */
    protected function hasPermission(User $user, string $action): bool
    {
        if ($this->permissionResource === '') {
            return false;
        }

        return $user->can($action.'-'.$this->permissionResource);
    }
}
