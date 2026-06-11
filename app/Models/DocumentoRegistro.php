<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class DocumentoRegistro extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'sts_documentos';

    protected $fillable = [
        'company_id',
        'codigo',
        'identificacao',
        'area',
        'tipo_documento',
        'revisao_atual',
        'ano_ultima_revisao',
        'meio',
        'local_arquivo',
        'indexacao',
        'protecao',
        'tempo_arquivamento',
        'destino_apos_prazo',
    ];

    public function revisoes()
    {
        return $this->hasMany(DocumentoRevisao::class, 'documento_id')->orderBy('data_revisao', 'desc');
    }
}
