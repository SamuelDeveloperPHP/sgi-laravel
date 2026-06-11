<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FornecedorDocumento extends Model
{
    use HasFactory;

    protected $table = 'fornecedor_documentos';

    protected $fillable = [
        'fornecedor_id',
        'nome_documento',
        'arquivo',
        'data_validade',
        'status_aprovacao',
        'motivo_reprovacao',
        'avaliado_por',
        'avaliado_em',
    ];

    protected $casts = [
        'data_validade' => 'date',
        'avaliado_em' => 'datetime',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliado_por');
    }
}
