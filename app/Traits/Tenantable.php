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

                // Inherit company_id from parent project if empty
                if (empty($model->company_id) && isset($model->projeto_id)) {
                    $projetoClass = 'App\\Models\\Projeto';
                    if (class_exists($projetoClass)) {
                        $parent = $projetoClass::withoutGlobalScopes()->find($model->projeto_id);
                        if ($parent) {
                            $model->company_id = $parent->company_id;
                        }
                    }
                }

                // Inherit company_id from parent task if empty
                if (empty($model->company_id) && isset($model->tarefa_projeto_id)) {
                    $taskClass = 'App\\Models\\TarefaProjeto';
                    if (class_exists($taskClass)) {
                        $parent = $taskClass::withoutGlobalScopes()->find($model->tarefa_projeto_id);
                        if ($parent) {
                            $model->company_id = $parent->company_id;
                        }
                    }
                }

                // Apenas sobrescreve o company_id se estiver vazio ou se o usuário não for Master Admin
                if (empty($model->company_id) || !$user->is_master_admin) {
                    $model->company_id = $user->company_id;
                }
                if ($model->isFillable('user_create')) {
                    $model->user_create = $user->id;
                } elseif ($model->isFillable('created_by')) {
                    $model->created_by = $user->id;
                }
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                if ($model->isFillable('user_edit')) {
                    $model->user_edit = auth()->id();
                } elseif ($model->isFillable('updated_by')) {
                    $model->updated_by = auth()->id();
                }
                // Impedir que o company_id seja alterado por request malicioso
                if ($model->isDirty('company_id')) {
                    $model->company_id = $model->getOriginal('company_id');
                }
            }
        });
    }
}
