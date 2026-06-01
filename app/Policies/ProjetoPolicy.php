<?php

namespace App\Policies;

/**
 * Policy para App\Models\Projeto.
 * Auto-descoberta por Laravel via convenção de nome (Model + Policy suffix).
 *
 * Herda comportamento padrão de AbstractTenantPolicy:
 *   - Master admin bypassa tudo via before()
 *   - Demais usuários só podem ver/editar/excluir projetos do mesmo tenant
 *
 * Sobrescreva métodos aqui para adicionar regras específicas
 * (ex.: só responsavel_id pode editar, só membros podem ver, etc.).
 */
class ProjetoPolicy extends AbstractTenantPolicy
{
}
