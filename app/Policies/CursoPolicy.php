<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\User;

class CursoPolicy extends AbstractTenantPolicy
{
    protected string $permissionResource = 'treinamentos-cursos';
}
