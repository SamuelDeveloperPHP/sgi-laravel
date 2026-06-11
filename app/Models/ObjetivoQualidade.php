<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class ObjetivoQualidade extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'sts_objetivos_qualidade';

    protected $fillable = [
        'titulo',
        'descricao',
        'prazo',
        'status',
        'elaborador_id',
        'data_elaboracao',
        'revisor_id',
        'data_revisao',
        'aprovador_id',
        'data_aprovacao',
        'hash_assinatura',
        'company_id',
        'user_create',
        'user_edit',
    ];

    protected $casts = [
        'prazo' => 'date',
        'data_elaboracao' => 'datetime',
        'data_revisao' => 'datetime',
        'data_aprovacao' => 'datetime',
    ];

    public function responsaveis()
    {
        return $this->belongsToMany(User::class, 'objetivo_user', 'objetivo_qualidade_id', 'user_id');
    }

    public function planosAcao()
    {
        return $this->hasMany(PlanoAcao::class, 'objetivo_qualidade_id');
    }

    public function elaborador()
    {
        return $this->belongsTo(User::class, 'elaborador_id');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function aprovador()
    {
        return $this->belongsTo(User::class, 'aprovador_id');
    }
}
