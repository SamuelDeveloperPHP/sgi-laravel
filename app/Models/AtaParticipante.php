<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtaParticipante extends Model
{
    use HasFactory;

    protected $table = 'sts_ata_participantes';

    protected $fillable = [
        'ata_id',
        'user_id',
        'assinado',
        'data_assinatura',
        'hash_assinatura',
    ];

    protected $casts = [
        'assinado' => 'boolean',
        'data_assinatura' => 'datetime',
    ];

    public function ata()
    {
        return $this->belongsTo(AtaReuniao::class, 'ata_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
