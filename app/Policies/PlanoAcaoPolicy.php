<?php

namespace App\Policies;

/**
 * Policy para App\Models\PlanoAcao.
 * Herda padrão tenant-scoped. Sobrescreva métodos para adicionar regras
 * QSMS (ex.: só responsavel pode marcar concluído, exigir evidência
 * de conclusão, bloquear edição após aprovação).
 */
class PlanoAcaoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'planosacao';
}
