<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class PoliticaQualidade extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'sts_politica_qualidade';

    protected $fillable = [
        'conteudo',
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
        'data_elaboracao' => 'datetime',
        'data_revisao' => 'datetime',
        'data_aprovacao' => 'datetime',
    ];

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
