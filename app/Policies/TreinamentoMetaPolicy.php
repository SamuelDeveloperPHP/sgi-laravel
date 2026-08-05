<?php

namespace App\Policies;

use App\Models\TreinamentoMeta;
use App\Models\User;

class TreinamentoMetaPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'treinamentos-metas';
}
