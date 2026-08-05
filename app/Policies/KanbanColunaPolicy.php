<?php

namespace App\Policies;

/**
 * Policy para App\Models\KanbanColuna.
 * Herda padrão tenant-scoped.
 */
class KanbanColunaPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'kanban-colunas';
}
