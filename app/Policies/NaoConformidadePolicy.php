<?php

namespace App\Policies;

/**
 * Policy para App\Models\NaoConformidade.
 * Herda padrão tenant-scoped. Sobrescreva métodos para adicionar fluxo
 * QSMS (ex.: bloquear delete após aprovação, só responsável pode
 * encerrar, exigir evidência antes de fechar).
 */
class NaoConformidadePolicy extends AbstractTenantPolicy
{
}
