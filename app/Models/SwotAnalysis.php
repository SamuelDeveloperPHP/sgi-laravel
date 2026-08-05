<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class SwotAnalysis extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'iso_swot_analyses';

    protected $fillable = [
        'company_id',
        'titulo',
        'data_analise',
        'aprovador_id',
        'status',
        'motivo_rejeicao',
        'objetivo_estrategico',
        'strengths',
        'weaknesses',
        'opportunities',
        'threats',
        'cruzamentos',
        'planos_acao',
        'conclusao',
        'user_create',
        'user_edit',
    ];

    /**
     * Cast de atributos.
     */
    protected $casts = [
        'data_analise' => 'date',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'opportunities' => 'array',
        'threats' => 'array',
        'cruzamentos' => 'array',
        'planos_acao' => 'array',
    ];

    /**
     * Empresa vinculada à análise.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Usuário aprovador selecionado.
     */
    public function aprovador()
    {
        return $this->belongsTo(User::class, 'aprovador_id');
    }

    /**
     * Criador do registro.
     */
    public function criador()
    {
        return $this->belongsTo(User::class, 'user_create');
    }

    /**
     * Editor do registro.
     */
    public function editor()
    {
        return $this->belongsTo(User::class, 'user_edit');
    }
}
