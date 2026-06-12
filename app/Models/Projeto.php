<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projeto extends Model
{
    // Tenantable: aplica TenantScope (filtra por company_id) e auto-stampa
    // company_id/user_create/user_edit.
    use HasFactory, Tenantable;

    protected $table = 'sts_projetos';

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'nomeProjeto',
        'nome_proj_filho',
        'descricao',
        'data_inicio',
        'data_fim',
        'ativo',
        'porc_concluido',
        'nivel_prioridade_id',
        'cor_prioridade_id',
        'ordem',
        'adms_cor_id',
        'adms_sit_id',
        'adms_usuario_id',
        'user_update_id',
        'alt_data_situacao',
        'responsavel_id',
        'privacidade',
        'tags',
        'imagem_capa',
        'arquivos_anexos',
    ];

    protected $casts = [
        'arquivos_anexos' => 'array',
    ];

    public function tarefas()
    {
        return $this->hasMany(TarefaProjeto::class, 'projeto_id')->orderBy('ordem'); 
    }

    public function kanbanColunas()
    {
        return $this->hasMany(KanbanColuna::class, 'projeto_id')->orderBy('ordem');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function membros()
    {
        return $this->belongsToMany(User::class, 'projeto_user', 'projeto_id', 'user_id');
    }
}
