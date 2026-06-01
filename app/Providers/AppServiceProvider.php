<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Schema::defaultStringLength(191);

        // Auditoria de ações de master admin em models tenant-scoped.
        // Por que: master admin bypassa o TenantScope (vê dados de todas
        // as empresas). Toda operação CRUD deve ser rastreável para
        // conformidade ISO 9001/14001/45001 e investigação de incidentes.
        $tenantScopedModels = [
            \App\Models\NaoConformidade::class,
            \App\Models\PlanoAcao::class,
            \App\Models\AuditoriaInterna::class,
            \App\Models\Projeto::class,
            \App\Models\TarefaProjeto::class,
            \App\Models\KanbanColuna::class,
            // Company e User não usam Tenantable mas são alvos sensíveis
            // a operações de master admin — também rastreados.
            \App\Models\Company::class,
            \App\Models\User::class,
        ];
        foreach ($tenantScopedModels as $modelClass) {
            $modelClass::observe(\App\Observers\MasterAdminAuditObserver::class);
        }
    }
}
