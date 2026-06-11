<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Identificacao
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'logo',
        'status',
        // Endereco
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        // Contato corporativo
        'email_corporativo',
        'telefone',
        // Administrador que cadastrou (rastreabilidade + LGPD)
        'nome_administrador',
        'email_administrador',
        'observacoes',
        // ISO 9001 - Avaliacao de fornecedores (configuracao)
        'criterios_avaliacao_fornecedor',
    ];

    protected $casts = [
        'criterios_avaliacao_fornecedor' => 'array',
        'status' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Endereco completo formatado para exibicao em uma linha.
     * Util em PDFs, atas, cabecalhos de relatorios.
     */
    public function getEnderecoCompletoAttribute(): string
    {
        return trim(implode(', ', array_filter([
            $this->logradouro,
            $this->numero,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->estado,
            $this->cep,
        ])));
    }
}
