<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Se for Master Admin, não aplica o filtro de tenant
            if ($user->is_master_admin) {
                return;
            }

            // Aplica filtro rigoroso por company_id
            $builder->where($model->getTable() . '.company_id', $user->company_id);
        } else {
            // Se ninguém estiver logado, por segurança hard-attack, podemos 
            // opcionalmente forçar um retorno vazio ou ignorar caso estejamos no CLI.
            if (!app()->runningInConsole()) {
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
