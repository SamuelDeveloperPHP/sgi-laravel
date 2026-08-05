<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FmGrupo extends Model
{
    protected $table = 'fm_grupos';

    protected $fillable = ['company_id', 'nome', 'created_by'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'fm_grupo_users', 'grupo_id', 'user_id')
            ->withPivot(['pode_adicionar_membros', 'pode_remover_membros'])
            ->withTimestamps();
    }

    public function pastas()
    {
        return $this->belongsToMany(FmPasta::class, 'fm_pasta_grupos', 'grupo_id', 'pasta_id')
            ->withPivot(['pode_visualizar', 'pode_incluir', 'pode_excluir'])
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
