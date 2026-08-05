<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FmPasta extends Model
{
    use SoftDeletes;

    protected $table = 'fm_pastas';

    protected $fillable = [
        'company_id', 'parent_id', 'nome', 'is_root', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_root' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(FmPasta::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FmPasta::class, 'parent_id')->orderBy('nome');
    }

    public function arquivos()
    {
        return $this->hasMany(FmArquivo::class, 'pasta_id');
    }

    public function grupos()
    {
        return $this->belongsToMany(FmGrupo::class, 'fm_pasta_grupos', 'pasta_id', 'grupo_id')
            ->withPivot(['pode_visualizar', 'pode_incluir', 'pode_excluir'])
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
