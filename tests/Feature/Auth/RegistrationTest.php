<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.cnpj.cnpja_url' => 'https://open.cnpja.test/office',
            'services.cnpj.brasilapi_url' => 'https://brasilapi.test/api/cnpj/v1',
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_registration_creates_active_company_administrator_and_tenant_links_atomically(): void
    {
        $this->fakeActiveCnpj();

        $response = $this->post('/register', $this->validPayload());

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $company = Company::where('cnpj', '11222333000181')->firstOrFail();
        $user = User::where('email', 'admin@empresa.com.br')->firstOrFail();

        $this->assertTrue($company->status);
        $this->assertNotNull($company->cnpj_verificado_em);
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->hasRole('Administrador'));
        $this->assertDatabaseHas('company_user', ['company_id' => $company->id, 'user_id' => $user->id]);
        $this->assertSame('recuperacao@empresa.com.br', $company->email_recuperacao_secundario);
    }

    public function test_personal_email_provider_cannot_register(): void
    {
        $this->fakeActiveCnpj();
        $response = $this->post('/register', $this->validPayload(['email' => 'test@gmail.com']));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_administrator_and_recovery_emails_must_match_company_domain(): void
    {
        $this->fakeActiveCnpj();
        $response = $this->post('/register', $this->validPayload([
            'email' => 'admin@outraempresa.com.br',
            'email_recuperacao_secundario' => 'recuperacao@terceira.com.br',
        ]));

        $response->assertSessionHasErrors(['email', 'email_recuperacao_secundario']);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_second_recovery_email_must_differ_from_administrator(): void
    {
        $this->fakeActiveCnpj();
        $response = $this->post('/register', $this->validPayload([
            'email_recuperacao_secundario' => 'admin@empresa.com.br',
        ]));

        $response->assertSessionHasErrors('email_recuperacao_secundario');
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_cpf_is_rejected_and_no_external_lookup_or_partial_record_is_created(): void
    {
        Http::fake();
        $response = $this->post('/register', $this->validPayload(['cnpj' => '52998224725']));

        $response->assertSessionHasErrors('cnpj');
        Http::assertNothingSent();
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unavailable_providers_create_pending_company_and_block_modules(): void
    {
        Http::fake(fn () => Http::response([], 503));
        $this->post('/register', $this->validPayload())->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'admin@empresa.com.br')->firstOrFail();
        $company = $user->company;
        $this->assertFalse($company->status);
        $this->assertNull($company->cnpj_verificado_em);

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('onboarding.pending'));
        $this->get(route('onboarding.pending'))->assertOk();
    }

    public function test_active_cnpj_with_unrelated_official_domain_stays_pending(): void
    {
        Http::fake([
            'open.cnpja.test/*' => Http::response([
                'alias' => 'Empresa Teste',
                'company' => ['name' => 'Empresa Teste LTDA'],
                'status' => ['id' => 2, 'text' => 'Ativa'],
                'emails' => [['address' => 'contato@dominio-oficial.com.br']],
            ]),
        ]);

        $this->post('/register', $this->validPayload());

        $company = Company::where('cnpj', '11222333000181')->firstOrFail();
        $this->assertFalse($company->status);
        $this->assertNull($company->cnpj_verificado_em);
    }

    public function test_privilege_and_tenant_fields_from_request_are_prohibited(): void
    {
        $this->fakeActiveCnpj();
        $response = $this->post('/register', $this->validPayload([
            'is_master_admin' => true,
            'company_id' => 999,
            'status' => true,
        ]));

        $response->assertSessionHasErrors(['is_master_admin', 'company_id', 'status']);
        $this->assertDatabaseCount('companies', 0);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Administrador Teste',
            'email' => 'admin@empresa.com.br',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cnpj' => '11.222.333/0001-81',
            'nome_fantasia' => 'Empresa Teste',
            'razao_social' => 'Empresa Teste LTDA',
            'cep' => '01310-100',
            'logradouro' => 'Avenida Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'telefone' => '1133334444',
            'dominio_corporativo' => 'empresa.com.br',
            'email_corporativo' => 'contato@empresa.com.br',
            'email_recuperacao_secundario' => 'recuperacao@empresa.com.br',
        ], $overrides);
    }

    private function fakeActiveCnpj(): void
    {
        Http::fake([
            'open.cnpja.test/*' => Http::response([
                'alias' => 'Empresa Teste',
                'company' => ['name' => 'Empresa Teste LTDA'],
                'status' => ['id' => 2, 'text' => 'Ativa'],
                'emails' => [['address' => 'contato@empresa.com.br']],
            ]),
        ]);
    }
}
