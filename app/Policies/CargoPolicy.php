<?php

namespace App\Policies;

use App\Models\Cargo;
use App\Models\User;

class CargoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'cargos';
    // Herda todas as regras padrão baseadas em Tenant e Master Admin
}
