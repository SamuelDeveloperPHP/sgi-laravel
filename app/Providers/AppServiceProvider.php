<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->configureRateLimiters();
    }

    /**
     * Define os rate limiters nomeados usados pelas rotas via middleware
     * throttle:NOME.
     *
     * Em produção considere ainda adicionar uma camada de WAF/CDN
     * (Cloudflare) para mitigar DDoS antes mesmo de chegar ao app.
     */
    protected function configureRateLimiters(): void
    {
        // Login: 5 tentativas por minuto por (email + IP). Bloqueia
        // brute-force sem afetar outros usuários do mesmo IP corporativo.
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            return [
                Limit::perMinute(5)->by(strtolower($email) . '|' . $request->ip()),
                Limit::perMinute(20)->by($request->ip()), // teto por IP
            ];
        });

        // Registro: 2/min por IP. Impede bot signup.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(2)->by($request->ip());
        });

        // Password reset request: 3/hora por email + 10/hora por IP.
        // Previne enumeração de usuários e spam de e-mails.
        RateLimiter::for('password-reset', function (Request $request) {
            $email = (string) $request->input('email', '');
            return [
                Limit::perHour(3)->by(strtolower($email)),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        // Email verification: 6/min — Laravel default mas explícito aqui.
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(6)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // API/Sanctum: 60/min por usuário autenticado, ou IP se anônimo.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Uploads: 10/min por usuário. Previne flood de evidências/anexos.
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}
