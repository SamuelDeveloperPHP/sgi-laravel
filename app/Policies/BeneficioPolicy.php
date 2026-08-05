<?php

namespace App\Policies;

use App\Models\Beneficio;
use App\Models\User;

class BeneficioPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'beneficios';
    // Herda todas as regras padrão baseadas em Tenant e Master Admin
}
