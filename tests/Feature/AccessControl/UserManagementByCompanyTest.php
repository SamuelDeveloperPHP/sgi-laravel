<?php

namespace Tests\Feature\AccessControl;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Valida que a gestao de usuarios em /admin/users respeita os
 * escopos por papel:
 *
 *   - Master admin ve TODOS os usuarios e pode atribuir qualquer
 *     role + qualquer company
 *   - Administrador da empresa ve APENAS usuarios da SUA empresa,
 *     so pode atribuir as 5 roles canonicas, e nao consegue mexer
 *     em master admins
 *
 * Memoria sgi-laravel-access-rules itens 5 e 6.
 */
class UserManagementByCompanyTest extends SecurityTestCase
{
    private Company $companyA;
    private Company $companyB;
    private User $masterAdmin;
    private User $adminA; // Administrador da empresa A

    protected function setUp(): void
    {
        parent::setUp();

        // Garante que as 5 roles do negocio existem
        foreach ([
            'Administrador', 'Analista da Qualidade', 'Tecnico da Qualidade',
            'Enfermeiros', 'Tecnicos de Enfermagem',
        ] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->companyA = Company::factory()->create(['nome_fantasia' => 'TEST A ' . uniqid()]);
        $this->companyB = Company::factory()->create(['nome_fantasia' => 'TEST B ' . uniqid()]);

        $this->masterAdmin = User::factory()->create([
            'email' => 'master-' . uniqid() . '@test.local',
            'email_verified_at' => now(),
        ]);
        $this->masterAdmin->forceFill(['is_master_admin' => true])->save();

        $this->adminA = User::factory()->create([
            'email' => 'admina-' . uniqid() . '@test.local',
            'email_verified_at' => now(),
        ]);
        $this->adminA->forceFill([
            'company_id' => $this->companyA->id,
            'is_master_admin' => false,
        ])->save();
        $this->adminA->assignRole('Administrador');
    }

    /** @test */
    public function admin_da_empresa_acessa_admin_users(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/users');

        // Antes do Sprint 4, era CheckMasterAdmin -> 403. Agora
        // permitido (controller filtra internamente).
        $this->assertNotEquals(403, $response->status(),
            'Administrador da empresa deve acessar /admin/users');
    }

    /** @test */
    public function admin_da_empresa_nao_ve_usuarios_de_outra_empresa(): void
    {
        // Cria um usuario na empresa B (que admin A NAO deveria ver)
        $userB = User::factory()->create([
            'email' => 'userb-' . uniqid() . '@test.local',
            'name'  => 'USER DA EMPRESA B INVISIVEL',
            'email_verified_at' => now(),
        ]);
        $userB->forceFill(['company_id' => $this->companyB->id])->save();

        $response = $this->actingAs($this->adminA)->get('/admin/users');

        $response->assertOk();
        $response->assertDontSee('USER DA EMPRESA B INVISIVEL');
    }

    /** @test */
    public function admin_da_empresa_nao_ve_master_admins_na_listagem(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/users');

        $response->assertOk();
        $response->assertDontSee($this->masterAdmin->email);
    }

    /** @test */
    public function master_admin_ve_todos_os_usuarios(): void
    {
        $userB = User::factory()->create([
            'email' => 'userb-' . uniqid() . '@test.local',
            'name'  => 'USER B VISIVEL PARA MASTER',
            'email_verified_at' => now(),
        ]);
        $userB->forceFill(['company_id' => $this->companyB->id])->save();

        $response = $this->actingAs($this->masterAdmin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('USER B VISIVEL PARA MASTER');
    }

    /** @test */
    public function admin_da_empresa_nao_consegue_editar_usuario_de_outra_empresa(): void
    {
        $userB = User::factory()->create(['email_verified_at' => now()]);
        $userB->forceFill(['company_id' => $this->companyB->id])->save();

        $response = $this->actingAs($this->adminA)->get("/admin/users/{$userB->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_da_empresa_nao_consegue_editar_master_admin(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get("/admin/users/{$this->masterAdmin->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_da_empresa_cria_usuario_e_company_id_e_auto_setado(): void
    {
        $email = 'novo-' . uniqid() . '@test.local';

        $response = $this->actingAs($this->adminA)->post('/admin/users', [
            'name'      => 'Novo Usuario',
            'email'     => $email,
            'password'  => 'SuperSecret@123!',
            'role'      => 'Analista da Qualidade',
            'is_active' => true,
            // Tentativa de injetar company_id de outra empresa
            'companies' => [$this->companyB->id],
        ]);

        // Deve falhar a validacao OU criar com company correto
        if ($response->status() === 302 && !$response->getSession()->has('errors')) {
            // Sucesso: verifica que foi criado na empresa de adminA
            $created = User::where('email', $email)->first();
            $this->assertNotNull($created);
            $this->assertEquals($this->companyA->id, $created->company_id);
        } else {
            // Validacao rejeitou companies.0 — tambem OK (defesa)
            $response->assertSessionHasErrors('companies.0');
        }
    }

    /** @test */
    public function admin_da_empresa_nao_consegue_atribuir_role_nao_canonica(): void
    {
        $response = $this->actingAs($this->adminA)->post('/admin/users', [
            'name'      => 'Hacker',
            'email'     => 'hacker-' . uniqid() . '@test.local',
            'password'  => 'SuperSecret@123!',
            'role'      => 'Super Master Hyper', // role inexistente / nao canonica
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('role');
    }

    /** @test */
    public function admin_da_empresa_nao_consegue_setar_is_master_admin(): void
    {
        $response = $this->actingAs($this->adminA)->post('/admin/users', [
            'name'            => 'Tentativa Escalada',
            'email'           => 'escalada-' . uniqid() . '@test.local',
            'password'        => 'SuperSecret@123!',
            'role'            => 'Administrador',
            'is_active'       => true,
            'is_master_admin' => true, // tentativa de escalada
        ]);

        $response->assertSessionHasErrors('is_master_admin');
    }
}
