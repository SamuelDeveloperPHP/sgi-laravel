<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;

class KanbanColuna extends Model
{
    // Tenantable: aplica TenantScope (filtra por company_id) e auto-stampa
    // company_id/user_create/user_edit.
    use Tenantable;

    protected $fillable = [
        'projeto_id',
        'nome',
        'ordem',
        'cor',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'projeto_id');
    }

    public function tarefas()
    {
        return $this->hasMany(TarefaProjeto::class, 'kanban_coluna_id')->orderBy('ordem');
    }
}
