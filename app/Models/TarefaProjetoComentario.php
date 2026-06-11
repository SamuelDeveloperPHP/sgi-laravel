<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarefaProjetoComentario extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'sts_tarefas_comentarios';

    protected $fillable = [
        'tarefa_projeto_id',
        'user_id',
        'mensagem',
        'company_id',
        'user_create',
        'user_edit',
    ];

    public function tarefa()
    {
        return $this->belongsTo(TarefaProjeto::class, 'tarefa_projeto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
