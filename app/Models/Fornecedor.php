<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'fornecedores';

    protected $fillable = [
        'company_id',
        'razao_social',
        'cnpj_cpf',
        'categoria',
        'criticidade',
        'status_homologacao',
        'idf_atual',
        'contato_nome',
        'email',
        'telefone',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'observacoes',
        'created_by',
        'updated_by',
    ];

    public function empresa()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function documentos()
    {
        return $this->hasMany(FornecedorDocumento::class, 'fornecedor_id');
    }

    public function avaliacoes()
    {
        return $this->hasMany(FornecedorAvaliacao::class, 'fornecedor_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
