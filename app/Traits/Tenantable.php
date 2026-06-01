<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;

trait Tenantable
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // 1. Applica o Global Scope automaticamente para todas as queries
        static::addGlobalScope(new TenantScope);

        // 2. Preenche os campos de auditoria e tenant na criação e atualização
        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();
                $model->company_id = $user->company_id;
                $model->user_create = $user->id;
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->user_edit = auth()->id();
                // Impedir que o company_id seja alterado por request malicioso
                if ($model->isDirty('company_id')) {
                    $model->company_id = $model->getOriginal('company_id');
                }
            }
        });
    }
}
