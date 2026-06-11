<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtaReuniao extends Model
{
    use HasFactory;

    protected $table = 'sts_atas';

    protected $fillable = [
        'company_id',
        'data',
        'hora_inicio',
        'hora_termino',
        'local',
        'assunto',
        'pautas',
        'registro',
        'responsavel_id',
        'status',
        'user_edit',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function empresa()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function participantes()
    {
        return $this->hasMany(AtaParticipante::class, 'ata_id');
    }

    public function userEdit()
    {
        return $this->belongsTo(User::class, 'user_edit');
    }
}
