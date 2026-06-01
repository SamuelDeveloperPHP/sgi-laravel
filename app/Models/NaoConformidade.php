<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class NaoConformidade extends SGIModel
{
    use HasFactory, SoftDeletes, Tenantable;

    /**
     * Tabela associada ao model.
     * Mapeia para a tabela legada 'sts_naoconforme'.
     */
    protected $table = 'sts_naoconforme';

    /**
     * Os atributos que não são atribuíveis em massa.
     */
    protected $guarded = ['id'];

    /**
     * Casts dos campos JSON e Data.
     */
    protected $casts = [
        'dados_origem' => 'array',
        'acao_contencao_grid' => 'array',
        'cinco_porques' => 'array',
        'plano_acao_grid' => 'array',
        'evidencias' => 'array',
        'dataAbertura' => 'datetime',
        'prazoEncerramento' => 'datetime',
    ];

    // Relacionamentos futuros:
    // public function empresaAberturaRel() { return $this->belongsTo(Company::class, 'empresaAbertura'); }
}
