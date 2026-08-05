<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class Candidato extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'candidatos';

    protected $fillable = [
        'company_id',
        'processo_seletivo_id',
        'nome',
        'email',
        'telefone',
        'idade',
        'endereco',
        'bairro',
        'cidade_estado',
        'nivel_ensino',
        'faculdade',
        'experiencia_anos',
        'ultima_empresa',
        'cargo',
        'tempo_ultimo_emprego',
        'avaliacao_geral',
        'referencias',
        'etapa_atual',
    ];

    public function processoSeletivo()
    {
        return $this->belongsTo(ProcessoSeletivo::class, 'processo_seletivo_id');
    }
}
