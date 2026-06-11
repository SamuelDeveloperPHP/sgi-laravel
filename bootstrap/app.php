<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // Adiciona headers de seguranca (HSTS, CSP, X-Frame-Options, etc.)
            // em TODAS as respostas web. Ver app/Http/Middleware/SecurityHeaders.php
            // para detalhes de cada header e notas de hardening futuro.
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Alias 'company.required': bloqueia acesso aos modulos enquanto
        // o usuario nao concluir o onboarding (cadastro de empresa com
        // CNPJ valido). Aplicado em routes/web.php nas rotas de modulos.
        // Master admin bypassa automaticamente. Ver memoria
        // sgi-laravel-access-rules.
        $middleware->alias([
            'company.required' => \App\Http\Middleware\RequireCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 403) {
                return redirect()->back()->with('error', $e->getMessage() ?: 'Acesso não autorizado a este recurso.');
            }
        });
    })->create();
