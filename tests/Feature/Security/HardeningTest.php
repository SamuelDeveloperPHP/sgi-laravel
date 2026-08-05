<?php

namespace Tests\Feature\Security;

/**
 * Suite de hardening — valida que headers HTTP de segurança e rate
 * limiters estão ativos nas respostas e rotas sensíveis.
 *
 * Estes testes existem para REGRESSÃO: se alguém remover o middleware
 * SecurityHeaders ou esquecer um throttle, esta suite quebra.
 */
class HardeningTest extends SecurityTestCase
{
    // ==================================================================
    // SECURITY HEADERS
    // ==================================================================

    /** @test */
    public function response_includes_x_content_type_options_nosniff(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @test */
    public function response_includes_x_frame_options_sameorigin(): void
    {
        // NOTA: Era DENY originalmente, mudado para SAMEORIGIN para
        // permitir preview de PDFs renderizados em iframe na mesma
        // origem (resources/views/pdf/*.blade.php).
        //
        // SAMEORIGIN ainda bloqueia clickjacking de origens externas
        // (que e o ataque que este header protege). Para hardening
        // maximo em producao considere 'frame-ancestors' no CSP, que
        // permite whitelist de origens com mais precisao.
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /** @test */
    public function response_includes_referrer_policy(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /** @test */
    public function response_includes_content_security_policy(): void
    {
        // NOTA: O CSP em ambiente NON-PRODUCTION (testing/local) e
        // permissivo de proposito para permitir Vite hot reload via
        // IPv6 ([::1]:5175) e WebSocket. Em producao, e restritivo
        // com default-src 'self', frame-ancestors 'none', object-src
        // 'none' (ver SecurityHeaders.php).
        //
        // Garantia minima testada aqui (qualquer ambiente): header
        // CSP existe e nao esta vazio. Sem o header, qualquer XSS
        // pode rodar scripts arbitrarios.
        $response = $this->get('/login');
        $response->assertHeader('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp, 'CSP deve estar presente em qualquer ambiente');
        $this->assertStringContainsString('default-src', $csp);
    }

    /** @test */
    public function response_includes_permissions_policy(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('Permissions-Policy');

        $policy = $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }

    /** @test */
    public function response_does_not_leak_tech_stack_via_x_powered_by(): void
    {
        $response = $this->get('/login');
        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    /** @test */
    public function hsts_header_is_absent_in_non_production_environment(): void
    {
        // Em testing/local NÃO deve aplicar HSTS (evita lock-in no browser dev)
        $response = $this->get('/login');
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_header_is_present_in_production_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
    }

    // ==================================================================
    // RATE LIMITING
    // ==================================================================

    /** @test */
    public function login_endpoint_throttles_after_5_failed_attempts(): void
    {
        $email = 'rate-' . uniqid() . '@test.local';

        // 5 tentativas falhas no limite
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'email'    => $email,
                'password' => 'wrong-password',
            ]);
            $this->assertNotEquals(429, $response->status(), "Tentativa {$i} nao deveria ser throttled");
        }

        // 6ª tentativa — DEVE bloquear com 429
        $response = $this->post('/login', [
            'email'    => $email,
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(429);
    }

    /** @test */
    public function register_endpoint_throttles_after_2_attempts_per_minute(): void
    {
        // 1ª tentativa: ok (mesmo que falhe validação, não retorna 429)
        $r1 = $this->post('/register', ['name' => 'A', 'email' => 'a@x.com', 'password' => 'x', 'password_confirmation' => 'x']);
        $this->assertNotEquals(429, $r1->status());

        // 2ª: ok
        $r2 = $this->post('/register', ['name' => 'B', 'email' => 'b@x.com', 'password' => 'x', 'password_confirmation' => 'x']);
        $this->assertNotEquals(429, $r2->status());

        // 3ª: bloqueia
        $r3 = $this->post('/register', ['name' => 'C', 'email' => 'c@x.com', 'password' => 'x', 'password_confirmation' => 'x']);
        $r3->assertStatus(429);
    }
}
