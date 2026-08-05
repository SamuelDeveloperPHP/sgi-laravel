<?php

namespace App\Policies;

use App\Models\Ferias;
use App\Models\User;

class FeriasPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'ferias';
    // Herda todas as regras padrão baseadas em Tenant e Master Admin
}
