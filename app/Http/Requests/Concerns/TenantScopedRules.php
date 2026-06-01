<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Helper trait para FormRequests que precisam validar IDs com escopo
 * de tenant (company_id). Centraliza o padrão "Rule::exists scopeada
 * com bypass para master admin".
 *
 * Uso típico em rules():
 *   'projeto_id' => ['required', $this->tenantScopedExists('sts_projetos')],
 *   'membros.*'  => [$this->tenantScopedExists('users')],
 */
trait TenantScopedRules
{
    /**
     * Gera Rule::exists para $table.$column filtrando por company_id do
     * usuário autenticado. Master admin bypassa o filtro.
     *
     * Captura company_id e is_master_admin no momento da chamada
     * (validação acontece logo depois — não há janela de mudança).
     */
    protected function tenantScopedExists(
        string $table,
        string $column = 'id',
        string $tenantColumn = 'company_id'
    ): Exists {
        $user = $this->user();
        $isMaster = $user?->is_master_admin ?? false;
        $companyId = $user?->company_id;

        return Rule::exists($table, $column)->where(function ($q) use ($isMaster, $companyId, $tenantColumn) {
            if (!$isMaster) {
                $q->where($tenantColumn, $companyId);
            }
        });
    }
}
