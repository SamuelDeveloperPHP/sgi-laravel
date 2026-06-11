<?php

namespace Tests\Feature\Onboarding;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Testes do fluxo de onboarding obrigatorio com CNPJ.
 *
 * Cobre as 3 regras de negocio confirmadas pelo usuario (ver memoria
 * sgi-laravel-access-rules):
 *
 *   1. Master admin acessa tudo (bypass do RequireCompany middleware)
 *   2. Usuario comum sem company_id e BLOQUEADO de acessar modulos
 *   3. CNPJ unico no sistema (uma empresa por CNPJ)
 *
 * E o fluxo end-to-end:
 *   - Apos onboarding, usuario ganha company_id e pode acessar
 *   - CNPJ invalido (checksum) e rejeitado
 *   - CNPJ duplicado e rejeitado
 *   - Reutilizacao do endpoint apos ja ter company e bloqueada
 *   - Role default "Administrador da Empresa" e atribuida
 *
 * Estende SecurityTestCase para usar MariaDB real (sts_* tables).
 */
class OnboardingFlowTest extends SecurityTestCase
{
    /** @test */
    public function usuario_sem_company_e_redirecionado_para_onboarding(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('onboarding.company'));
    }

    /** @test */
    public function master_admin_sem_company_nao_e_redirecionado(): void
    {
        $masterAdmin = $this->createVerifiedUser([
            'company_id' => null,
            'is_master_admin' => true,
        ]);
        $this->actingAs($masterAdmin);

        $response = $this->get('/dashboard');

        $response->assertOk();
    }

    /** @test */
    public function usuario_com_company_acessa_dashboard_normalmente(): void
    {
        $company = Company::factory()->create();
        $user = $this->createVerifiedUser(['company_id' => $company->id]);
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertOk();
    }

    /** @test */
    public function pagina_de_onboarding_e_acessivel_para_quem_nao_tem_company(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->get('/onboarding/company');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Onboarding/Company'));
    }

    /** @test */
    public function pagina_de_onboarding_redireciona_para_dashboard_se_ja_tem_company(): void
    {
        $company = Company::factory()->create();
        $user = $this->createVerifiedUser(['company_id' => $company->id]);
        $this->actingAs($user);

        $response = $this->get('/onboarding/company');

        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function onboarding_com_cnpj_valido_cria_empresa_e_vincula_usuario(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Teste Indústria',
            'razao_social' => 'Teste Indústria LTDA',
            'cnpj' => '11222333000181', // CNPJ valido
        ]);

        $response->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertNotNull($user->company_id);
        $this->assertDatabaseHas('companies', [
            'id' => $user->company_id,
            'cnpj' => '11222333000181',
            'nome_fantasia' => 'Teste Indústria',
        ]);
    }

    /** @test */
    public function onboarding_rejeita_cnpj_com_checksum_invalido(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Teste',
            'razao_social' => 'Teste LTDA',
            'cnpj' => '11222333000182', // DV errado
        ]);

        $response->assertSessionHasErrors('cnpj');

        $user->refresh();
        $this->assertNull($user->company_id);
    }

    /** @test */
    public function onboarding_rejeita_cnpj_duplicado(): void
    {
        // Empresa ja existente com CNPJ
        Company::factory()->create(['cnpj' => '60872504000123']);

        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Outra empresa',
            'razao_social' => 'Outra LTDA',
            'cnpj' => '60872504000123', // duplicado
        ]);

        $response->assertSessionHasErrors('cnpj');
    }

    /** @test */
    public function onboarding_bloqueia_reuso_se_usuario_ja_tem_company(): void
    {
        $existingCompany = Company::factory()->create();
        $user = $this->createVerifiedUser(['company_id' => $existingCompany->id]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Tentativa de troca',
            'razao_social' => 'Tentativa LTDA',
            'cnpj' => '11222333000181',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Empresa original NAO foi alterada
        $user->refresh();
        $this->assertEquals($existingCompany->id, $user->company_id);

        // Nenhuma empresa nova foi criada
        $this->assertDatabaseMissing('companies', ['cnpj' => '11222333000181']);
    }

    /** @test */
    public function onboarding_rejeita_mass_assignment_de_is_master_admin(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Tentativa de escalada',
            'razao_social' => 'Hacker LTDA',
            'cnpj' => '11222333000181',
            'is_master_admin' => true, // Tentativa de escalada
        ]);

        $response->assertSessionHasErrors('is_master_admin');

        $user->refresh();
        $this->assertFalse((bool) $user->is_master_admin);
    }

    /** @test */
    public function onboarding_rejeita_mass_assignment_de_company_id(): void
    {
        $outraEmpresa = Company::factory()->create();
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Tentativa de pular',
            'razao_social' => 'Bypass LTDA',
            'cnpj' => '11222333000181',
            'company_id' => $outraEmpresa->id, // Tentativa de pegar empresa alheia
        ]);

        $response->assertSessionHasErrors('company_id');
    }

    /** @test */
    public function onboarding_atribui_role_administrador_se_existir(): void
    {
        // BusinessRolesSeeder cria a role 'Administrador' (sem o
        // sufixo 'da Empresa'). Garantimos aqui para o teste.
        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);

        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $this->post(route('onboarding.complete'), [
            'nome_fantasia' => 'Empresa com Role',
            'razao_social' => 'Role LTDA',
            'cnpj' => '11222333000181',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('Administrador'));
    }

    /** @test */
    public function onboarding_salva_dados_completos_da_empresa(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia'     => 'Tech Solutions',
            'razao_social'      => 'Tech Solutions LTDA',
            'cnpj'              => '11222333000181',
            'cep'               => '01310-100',
            'logradouro'        => 'Av. Paulista',
            'numero'            => '1000',
            'complemento'       => 'Sala 200',
            'bairro'            => 'Bela Vista',
            'cidade'            => 'Sao Paulo',
            'estado'            => 'SP',
            'email_corporativo' => 'contato@techsolutions.com.br',
            'telefone'          => '(11) 99999-9999',
            'observacoes'       => 'Empresa de TI',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('companies', [
            'cnpj'                => '11222333000181',
            'logradouro'          => 'Av. Paulista',
            'numero'              => '1000',
            'cidade'              => 'Sao Paulo',
            'estado'              => 'SP',
            'email_corporativo'   => 'contato@techsolutions.com.br',
            'nome_administrador'  => $user->name,
            'email_administrador' => $user->email,
        ]);
    }

    /** @test */
    public function onboarding_rejeita_mass_assignment_de_nome_administrador(): void
    {
        // Defesa em profundidade: o usuario nao pode injetar
        // nome_administrador ou email_administrador via body.
        // Sao setados pelo backend a partir do user autenticado.
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $response = $this->post(route('onboarding.complete'), [
            'nome_fantasia'       => 'Tentativa',
            'razao_social'        => 'Hacker LTDA',
            'cnpj'                => '11222333000181',
            'nome_administrador'  => 'Outro Nome',
            'email_administrador' => 'falso@example.com',
        ]);

        $response->assertSessionHasErrors(['nome_administrador', 'email_administrador']);
    }

    /** @test */
    public function rotas_de_modulos_redirecionam_se_usuario_nao_tem_company(): void
    {
        $user = $this->createVerifiedUser(['company_id' => null]);
        $this->actingAs($user);

        $this->get('/auditorias')->assertRedirect(route('onboarding.company'));
        $this->get('/projetos')->assertRedirect(route('onboarding.company'));
        $this->get('/nao-conformidades')->assertRedirect(route('onboarding.company'));
    }

    /**
     * Cria um usuario verificado com company_id controlavel.
     * Usa forceFill porque company_id e is_master_admin nao estao
     * em $fillable do User (por seguranca).
     */
    private function createVerifiedUser(array $attrs = []): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        if (array_key_exists('company_id', $attrs) || array_key_exists('is_master_admin', $attrs)) {
            $user->forceFill([
                'company_id' => $attrs['company_id'] ?? null,
                'is_master_admin' => $attrs['is_master_admin'] ?? false,
            ])->save();
        }

        return $user->refresh();
    }
}
