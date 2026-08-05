<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Tenantable;

class ControleCalibracao extends Model
{
    use HasFactory, Tenantable;

    protected $fillable = [
        'company_id',
        'equipamento',
        'local',
        'identificacao',
        'certificado_numero',
        'frequencia_meses',
        'data_ultima_calibracao',
        'data_proxima_calibracao',
        'observacoes',
        'arquivo_certificado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data_ultima_calibracao' => 'date',
        'data_proxima_calibracao' => 'date',
    ];

    public function empresa()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
