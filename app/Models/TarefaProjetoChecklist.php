<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarefaProjetoChecklist extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'sts_tarefas_checklists';

    protected $fillable = [
        'tarefa_projeto_id',
        'descricao',
        'concluido',
        'ordem',
        'company_id',
        'user_create',
        'user_edit',
    ];

    protected $casts = [
        'concluido' => 'boolean',
    ];

    public function tarefa()
    {
        return $this->belongsTo(TarefaProjeto::class, 'tarefa_projeto_id');
    }
}
