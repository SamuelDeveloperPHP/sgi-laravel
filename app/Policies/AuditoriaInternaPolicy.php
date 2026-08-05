<?php

namespace App\Policies;

/**
 * Policy para App\Models\AuditoriaInterna.
 * Herda padrão tenant-scoped. Sobrescreva métodos para adicionar regras
 * específicas (ex.: só auditor líder pode editar, bloquear edição após
 * fechamento da auditoria).
 */
class AuditoriaInternaPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'auditorias';
}
