<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarefaProjeto extends Model
{
    // Tenantable: aplica TenantScope (filtra por company_id) e auto-stampa
    // company_id/user_create/user_edit.
    use HasFactory, Tenantable;

    protected $table = 'sts_tarefas_projeto';

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'nome',
        'ordem',
        'progresso',
        'progressByWorklog',
        'relevancia',
        'type',
        'typeId',
        'descricao',
        'code',
        'level',
        'status',
        'dependencias',
        'subEscrever',
        'dt_inicio',
        'tempo_duracao',
        'dt_fim',
        'startIsMilestone',
        'endIsMilestone',
        'collapsed',
        'assigs',
        'hasChild',
        'adms_cor_id',
        'adms_sit_id',
        'adms_usuario_id',
        'user_update_id',
        'alt_data_situacao',
        'cor_prioridade_id',
        'projeto_id',
        'kanban_coluna_id',
        'repetir',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'projeto_id');
    }

    public function kanbanColuna()
    {
        return $this->belongsTo(KanbanColuna::class, 'kanban_coluna_id');
    }

    public function comentarios()
    {
        return $this->hasMany(TarefaProjetoComentario::class, 'tarefa_projeto_id')->orderBy('created_at', 'desc');
    }

    public function anexos()
    {
        return $this->hasMany(TarefaProjetoAnexo::class, 'tarefa_projeto_id')->orderBy('created_at', 'desc');
    }

    public function checklists()
    {
        return $this->hasMany(TarefaProjetoChecklist::class, 'tarefa_projeto_id')->orderBy('ordem', 'asc');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tarefa_projeto_user', 'tarefa_projeto_id', 'user_id');
    }

    public function getRelevanciaAttribute($value)
    {
        switch ($value) {
            case 3:
                return 'high';
            case 1:
                return 'low';
            case 2:
            default:
                return 'medium';
        }
    }

    public function setRelevanciaAttribute($value)
    {
        switch ($value) {
            case 'high':
                $this->attributes['relevancia'] = 3;
                break;
            case 'low':
                $this->attributes['relevancia'] = 1;
                break;
            case 'medium':
            default:
                $this->attributes['relevancia'] = 2;
                break;
        }
    }

    public function getStatusAttribute($value)
    {
        if ($value === 'STATUS_ACTIVE') {
            return 'pending';
        }
        return $value ?: 'pending';
    }
}
