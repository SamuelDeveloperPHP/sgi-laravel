<?php

namespace App\Policies;

use App\Models\LocalTreinamento;
use App\Models\User;

class LocalTreinamentoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'treinamentos-locais';
}
