<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use App\Notifications\PublicAccountBlocked;
use App\Support\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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

    public function test_public_email_can_register_temporarily_without_cnpj(): void
    {
        Module::create([
            'name' => 'Dashboard',
            'slug' => 'list-dashboard',
            'route_name' => 'dashboard',
            'default_access_policy' => 'public',
        ]);

        $response = $this->post('/register', [
            'registration_type' => 'public',
            'name' => 'Usuario Publico',
            'email' => 'publico@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'publico@gmail.com')->firstOrFail();
        $this->assertTrue($user->is_public_account);
        $this->assertNotNull($user->company_id);
        $this->assertNotNull($user->public_access_expires_at);
        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'cnpj' => null,
            'status' => true,
        ]);
    }

    public function test_expired_public_account_is_blocked_on_login_and_notified(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'email' => 'expirado@gmail.com',
            'email_verified_at' => now(),
        ]);
        $user->forceFill([
            'company_id' => $company->id,
            'is_public_account' => true,
            'is_active' => true,
            'public_access_started_at' => now()->subDays(20),
            'public_access_expires_at' => now()->subDay(),
        ])->save();

        $response = $this->post('/login', [
            'email' => 'expirado@gmail.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertFalse($user->refresh()->is_active);
        Notification::assertSentTo($user, PublicAccountBlocked::class);
    }

    public function test_module_access_policy_blocks_private_module_for_public_user(): void
    {
        $publicModule = Module::create([
            'name' => 'Dashboard',
            'slug' => 'list-dashboard',
            'route_name' => 'dashboard',
            'default_access_policy' => 'public',
        ]);
        $privateModule = Module::create([
            'name' => 'Projetos',
            'slug' => 'list-projetos',
            'route_name' => 'projetos.index',
            'default_access_policy' => 'private',
        ]);
        $company = Company::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill([
            'company_id' => $company->id,
            'is_public_account' => true,
            'is_active' => true,
            'public_access_started_at' => now(),
            'public_access_expires_at' => now()->addDays(15),
        ])->save();

        $this->assertTrue(ModuleAccess::moduleVisibleToUser($user, $publicModule));
        $this->assertFalse(ModuleAccess::moduleVisibleToUser($user, $privateModule));
        $this->assertTrue(ModuleAccess::allowsPermission($user, 'view-dashboard'));
        $this->assertFalse(ModuleAccess::allowsPermission($user, 'view-projetos'));
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
