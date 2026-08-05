<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cargo extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'rh_cargos';
    protected $guarded = ['id'];

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
