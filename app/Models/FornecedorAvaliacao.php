<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FornecedorAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'fornecedor_avaliacoes';

    protected $fillable = [
        'fornecedor_id',
        'data_avaliacao',
        'avaliador_id',
        'criterios',
        'nota_geral',
        'observacoes',
    ];

    protected $casts = [
        'data_avaliacao' => 'date',
        'criterios' => 'array',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliador_id');
    }
}
