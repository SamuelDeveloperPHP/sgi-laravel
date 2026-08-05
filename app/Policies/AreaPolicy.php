<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'areas';
    // Herda todas as regras padrão baseadas em Tenant e Master Admin
}
