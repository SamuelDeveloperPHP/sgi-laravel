<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class ProcessoSeletivo extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'processos_seletivos';

    protected $fillable = [
        'company_id',
        'nome',
        'status',
        'data_inicio',
        'data_fim',
        'custo_planejado',
        'custo_realizado',
    ];

    public function candidatos()
    {
        return $this->hasMany(Candidato::class, 'processo_seletivo_id');
    }
}
