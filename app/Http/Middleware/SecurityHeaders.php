<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adiciona cabeçalhos HTTP de segurança em TODAS as respostas web.
 *
 * Cobertura:
 *   - HSTS (Strict-Transport-Security): força HTTPS por 2 anos
 *   - X-Content-Type-Options: nosniff (impede MIME-sniffing)
 *   - X-Frame-Options: DENY (impede clickjacking via iframe)
 *   - Referrer-Policy: strict-origin-when-cross-origin
 *   - Permissions-Policy: bloqueia camera/microphone/geolocation por default
 *   - Cross-Origin-Opener-Policy: same-origin
 *   - Cross-Origin-Resource-Policy: same-site
 *   - Content-Security-Policy: permissivo o suficiente para Inertia+Vite+Tailwind
 *
 * NOTA — CSP em produção:
 *   A política atual permite 'unsafe-inline' em script/style por causa
 *   do Tailwind (style) e de scripts inline gerados pelo Inertia para
 *   passar props iniciais. Para hardening máximo (P1 futuro):
 *     1) Migrar para nonces dinâmicos em todos os script tags
 *     2) Pré-compilar CSS sem inline (Tailwind production build já faz)
 *     3) Remover 'unsafe-inline' e 'unsafe-eval' da CSP
 *
 * HSTS preload submission:
 *   Após confirmar HTTPS em produção, registre o domínio em
 *   https://hstspreload.org/ para que browsers nunca tentem HTTP nesse host.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Em ambientes não-produção (local, testing) NÃO ativa HSTS para
        // evitar lock-in de HTTPS no browser em desenvolvimento local.
        $isProduction = app()->environment('production');

        if ($isProduction) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=63072000; includeSubDomains; preload'
            );
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Permitir iframes na mesma origem (necessário para o preview de PDF)
        if (!$response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), interest-cohort=()'
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');

        if ($isProduction) {
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https://fonts.bunny.net",
                "connect-src 'self' ws: wss:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        } else {
            // No ambiente local, liberamos o CSP para não dar conflito com o IPV6 do Vite ([::1]) e Hot Reload
            $response->headers->set('Content-Security-Policy', "default-src * 'unsafe-inline' 'unsafe-eval' data: blob:;");
        }

        // Remove headers que vazam tech-stack
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
