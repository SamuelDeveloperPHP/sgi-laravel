<?php

namespace App\Policies;

/**
 * Policy para App\Models\TarefaProjeto.
 * Herda padrão tenant-scoped. Sobrescreva métodos para adicionar regras
 * específicas (ex.: só assigs podem mover, só responsavel pode fechar).
 */
class TarefaProjetoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'tarefas';
}
