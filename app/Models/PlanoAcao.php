<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class PlanoAcao extends SGIModel
{
    use HasFactory, SoftDeletes, Tenantable;

    /**
     * Tabela associada ao model.
     * Mapeia para a tabela legada 'sts_pa'.
     */
    protected $table = 'sts_pa';

    /**
     * Os atributos que não são atribuíveis em massa.
     */
    protected $guarded = ['id'];
}
