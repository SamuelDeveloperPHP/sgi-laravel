<?php

namespace App\Policies;

use App\Models\FolhaPagamento;
use App\Models\User;

class FolhaPagamentoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'folha-pagamento';
    // Herda todas as regras padrão baseadas em Tenant e Master Admin
}
