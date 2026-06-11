<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoRevisao extends Model
{
    use HasFactory;

    protected $table = 'sts_documento_revisoes';

    protected $fillable = [
        'documento_id',
        'revisao',
        'data_revisao',
        'alteracoes',
        'responsavel_id',
        'aprovador_id',
    ];

    protected $casts = [
        'data_revisao' => 'date',
    ];

    public function documento()
    {
        return $this->belongsTo(DocumentoRegistro::class, 'documento_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function aprovador()
    {
        return $this->belongsTo(User::class, 'aprovador_id');
    }
}
