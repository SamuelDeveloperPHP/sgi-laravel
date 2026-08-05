<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ferias extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'rh_ferias';

    protected $fillable = [
        'company_id',
        'funcionario_id',
        'periodo_aquisitivo_inicio',
        'periodo_aquisitivo_fim',
        'dias_direito',
        'opcao_abono',
        'dias_abono',
        'gozo_1_inicio',
        'gozo_1_fim',
        'gozo_2_inicio',
        'gozo_2_fim',
        'gozo_3_inicio',
        'gozo_3_fim',
        'faltas',
        'valor_proventos',
        'valor_1_3',
        'valor_1_3_abono',
        'desconto_inss',
        'desconto_irpf',
        'valor_liquido',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'periodo_aquisitivo_inicio' => 'date',
        'periodo_aquisitivo_fim' => 'date',
        'gozo_1_inicio' => 'date',
        'gozo_1_fim' => 'date',
        'gozo_2_inicio' => 'date',
        'gozo_2_fim' => 'date',
        'gozo_3_inicio' => 'date',
        'gozo_3_fim' => 'date',
        'opcao_abono' => 'boolean',
        'valor_proventos' => 'decimal:2',
        'valor_1_3' => 'decimal:2',
        'valor_1_3_abono' => 'decimal:2',
        'desconto_inss' => 'decimal:2',
        'desconto_irpf' => 'decimal:2',
        'valor_liquido' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();
                if (empty($model->company_id) || !$user->is_master_admin) {
                    $model->company_id = $user->company_id;
                }
                $model->created_by = $user->id;
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
                // Impedir que o company_id seja alterado
                if ($model->isDirty('company_id') && !auth()->user()->is_master_admin) {
                    $model->company_id = $model->getOriginal('company_id');
                }
            }
        });
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
