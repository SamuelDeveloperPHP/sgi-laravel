<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'cnpj',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
