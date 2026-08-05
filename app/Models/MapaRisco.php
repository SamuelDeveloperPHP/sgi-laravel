<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class MapaRisco extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'sts_mapas_risco';

    protected $fillable = [
        'company_id',
        'titulo',
        'setor',
        'aprovador_id',
        'status',
        'motivo_rejeicao',
        'data_mapeamento',
        'pontos_risco',
        'user_create',
        'user_edit',
    ];

    /**
     * Cast de atributos.
     */
    protected $casts = [
        'data_mapeamento' => 'date',
        'pontos_risco' => 'array',
    ];

    /**
     * Empresa vinculada ao mapa de risco.
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
