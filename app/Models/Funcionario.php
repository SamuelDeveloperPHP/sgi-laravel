<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\TenantScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Funcionario extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'rh_funcionarios';

    protected $fillable = [
        'company_id',
        'nome',
        'cpf',
        'matricula',
        'data_admissao',
        'dependentes',
        'estado_civil',
        'salario_bruto',
        'telefone',
        'email',
        'observacoes',
        'status',
        'estado',
        'created_by',
        'updated_by',
        'area_id',
        'cargo_id',
        'genero',
        'data_demissao',
        'motivo_demissao',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'carga_horaria_mensal',
        'horario_trabalho',
        'data_nascimento',
        'rg',
        'nacionalidade',
        'titulo_eleitor',
        'carteira_reservista',
        'naturalidade',
        'ctps',
        'pis',
        'celular',
        'nome_mae',
        'nome_pai',
        'escolaridade',
        'tipo_sanguineo',
        'banco',
        'agencia',
        'conta_corrente',
        'parcelas_ferias',
        'data_decimo_terceiro',
        'parcelas_decimo_terceiro',
    ];

    protected $appends = ['idade'];

    protected $casts = [
        'data_admissao' => 'date',
        'data_nascimento' => 'date',
        'salario_bruto' => 'decimal:2',
    ];

    public function getIdadeAttribute()
    {
        if ($this->data_nascimento) {
            return $this->data_nascimento->age;
        }
        return null;
    }

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ferias(): HasMany
    {
        return $this->hasMany(Ferias::class, 'funcionario_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
