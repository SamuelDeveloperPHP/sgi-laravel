<?php

namespace App\Policies;

use App\Models\Treinamento;
use App\Models\User;

class TreinamentoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'treinamentos-turmas';
}
