<?php

namespace App\Policies;

use App\Models\Funcionario;
use App\Models\User;

class FuncionarioPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'funcionarios';
    // Herda todas as regras padrão baseadas em Tenant e Master Admin
}
