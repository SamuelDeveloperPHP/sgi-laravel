<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Tenantable;

class NossaHistoria extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $table = 'sts_nossa_historia';

    protected $fillable = [
        'company_id',
        'conteudo',
    ];
}
