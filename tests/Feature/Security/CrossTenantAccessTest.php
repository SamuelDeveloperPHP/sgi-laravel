<?php

namespace Tests\Feature\Security;

use App\Models\AuditoriaInterna;
use App\Models\Company;
use App\Models\KanbanColuna;
use App\Models\NaoConformidade;
use App\Models\PlanoAcao;
use App\Models\Projeto;
use App\Models\TarefaProjeto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Suite de testes ADVERSÁRIOS de isolamento multiempresa.
 *
 * Cada teste simula um usuário da Empresa A tentando acessar/modificar
 * dados da Empresa B via manipulação de URL/payload. Garante que todas
 * as 4 camadas de defesa continuam ativas:
 *
 *   1. TenantScope (global scope filtra queries)
 *   2. Policies (sameTenant check)
 *   3. FormRequests (tenantScopedExists em Rule::exists)
 *   4. FK constraints (banco bloqueia órfãos)
 *
 * IMPORTANTE: estes testes EXISTEM para regressão — se algum deles
 * começar a falhar após uma alteração, é sinal que a alteração reabriu
 * vulnerabilidade. NÃO comentar/skipar testes; SEMPRE corrigir o código.
 */
class CrossTenantAccessTest extends SecurityTestCase
{
    private Company $companyA;
    private Company $companyB;
    private User $userA;       // user comum da empresa A
    private User $userB;       // user comum da empresa B
    private User $masterAdmin; // master admin (vê tudo)

    protected function setUp(): void
    {
        parent::setUp();

        // Bypassa $fillable durante setup para poder setar company_id
        // diretamente (em produção, Tenantable carimba; em testes, controle manual).
        Model::unguard();

        $this->companyA = Company::create([
            'nome_fantasia' => 'TEST Company A ' . uniqid(),
            'razao_social'  => 'Test A Ltda',
            'cnpj'          => null,
            'status'        => 1,
        ]);
        $this->companyB = Company::create([
            'nome_fantasia' => 'TEST Company B ' . uniqid(),
            'razao_social'  => 'Test B Ltda',
            'cnpj'          => null,
            'status'        => 1,
        ]);

        // User factory NÃO inclui company_id no $fillable (removido na F1.3
        // por questões de mass-assignment escalation). Mesmo com unguard(),
        // alguns paths do factory ignoram. Atribuímos via forceFill+save
        // que bypassa $fillable de forma explícita e auditável.
        $this->userA = User::factory()->create(['email' => 'usera-' . uniqid() . '@test.local']);
        $this->userA->forceFill([
            'company_id'      => $this->companyA->id,
            'is_master_admin' => false,
            'is_active'       => true,
        ])->save();
        $this->userA->refresh();

        $this->userB = User::factory()->create(['email' => 'userb-' . uniqid() . '@test.local']);
        $this->userB->forceFill([
            'company_id'      => $this->companyB->id,
            'is_master_admin' => false,
            'is_active'       => true,
        ])->save();
        $this->userB->refresh();

        $this->masterAdmin = User::factory()->create(['email' => 'master-' . uniqid() . '@test.local']);
        $this->masterAdmin->forceFill([
            'company_id'      => null,
            'is_master_admin' => true,
            'is_active'       => true,
        ])->save();
        $this->masterAdmin->refresh();

        Model::reguard();
    }

    // =================================================================
    // Helpers para criar QSMS models em tenants específicos
    // =================================================================

    private function makeProjeto(Company $company, array $extra = []): Projeto
    {
        return Projeto::withoutGlobalScopes()->forceCreate(array_merge([
            'nomeProjeto' => 'TEST Projeto ' . uniqid(),
            'ativo'       => 1,
            'company_id'  => $company->id,
            'user_create' => $company->id === $this->companyA->id ? $this->userA->id : $this->userB->id,
        ], $extra));
    }

    private function makeTarefa(Projeto $projeto): TarefaProjeto
    {
        return TarefaProjeto::withoutGlobalScopes()->forceCreate([
            'nome'        => 'TEST Tarefa ' . uniqid(),
            'projeto_id'  => $projeto->id,
            'ordem'       => 1,
            'status'      => 'pending',
            'company_id'  => $projeto->company_id,
            'user_create' => $projeto->user_create,
        ]);
    }

    private function makeKanban(Projeto $projeto): KanbanColuna
    {
        return KanbanColuna::withoutGlobalScopes()->forceCreate([
            'nome'        => 'TEST Coluna ' . uniqid(),
            'projeto_id'  => $projeto->id,
            'ordem'       => 1,
            'company_id'  => $projeto->company_id,
            'user_create' => $projeto->user_create,
        ]);
    }

    private function makeNC(Company $company): NaoConformidade
    {
        return NaoConformidade::withoutGlobalScopes()->forceCreate([
            'descOcorrencia' => 'TEST NC ' . uniqid(),
            'dataAbertura'   => now(),
            'company_id'     => $company->id,
            'user_create'    => $company->id === $this->companyA->id ? $this->userA->id : $this->userB->id,
        ]);
    }

    // =================================================================
    // TESTES DE ISOLAMENTO — Projeto
    // =================================================================

    /** @test */
    public function user_A_blocked_from_projetos_listing_master_only(): void
    {
        // REGRA ATUALIZADA (2026-06-11, memoria sgi-laravel-access-rules):
        // Modulo Projetos e RESTRITO a master admin. Teste original
        // verificava isolamento de TenantScope entre A e B; agora userA
        // (nao master) e bloqueado antes mesmo de chegar no controller
        // pelo middleware CheckMasterAdmin -> abort(403) -> bootstrap
        // handler converte em redirect com flash 'error'.
        $this->makeProjeto($this->companyA);

        $response = $this->actingAs($this->userA)->get('/projetos');

        // 403 OU redirect (handler de Exception em bootstrap/app.php
        // intercepta HttpException 403 e redireciona com flash).
        $this->assertContains($response->status(), [302, 403]);
    }

    /** @test */
    public function user_A_cannot_view_projeto_from_company_B(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);

        $response = $this->actingAs($this->userA)->get("/projetos/{$projetoB->id}");

        $response->assertNotFound();  // TenantScope faz findOrFail retornar 404
        $this->assertDatabaseHas('sts_projetos', ['id' => $projetoB->id]); // mas existe
    }

    /** @test */
    public function user_A_cannot_update_projeto_from_company_B(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);

        $response = $this->actingAs($this->userA)->put("/projetos/{$projetoB->id}", [
            'nomeProjeto' => 'HACKED',
            'ativo'       => 1,
        ]);

        $response->assertNotFound();
        // Confirma que NÃO foi alterado
        $this->assertDatabaseHas('sts_projetos', [
            'id'          => $projetoB->id,
            'nomeProjeto' => $projetoB->nomeProjeto,
        ]);
    }

    /** @test */
    public function user_A_cannot_delete_projeto_from_company_B(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);

        $response = $this->actingAs($this->userA)->delete("/projetos/{$projetoB->id}");

        $response->assertNotFound();
        // Soft-delete não rolou — registro continua sem deleted_at
        $this->assertDatabaseHas('sts_projetos', [
            'id'         => $projetoB->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function user_A_blocked_from_creating_projeto_master_only(): void
    {
        // Substitui dois testes anteriores (responsavel_id e membros)
        // que validavam tenantScopedExists nos Form Requests. Agora
        // userA (nao-master) e bloqueado pelo middleware ANTES de
        // chegar nas validacoes.
        $response = $this->actingAs($this->userA)->post('/projetos', [
            'nomeProjeto'    => 'TEST Projeto Hack',
            'responsavel_id' => $this->userB->id,
        ]);

        $this->assertContains($response->status(), [302, 403]);
        // Confirma que NAO criou nada
        $this->assertDatabaseMissing('sts_projetos', [
            'nomeProjeto' => 'TEST Projeto Hack',
        ]);
    }

    // =================================================================
    // TESTES DE ISOLAMENTO — TarefaProjeto
    // =================================================================

    /** @test */
    public function user_A_blocked_from_tarefa_create_master_only(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);

        $response = $this->actingAs($this->userA)->post('/tarefas', [
            'projeto_id' => $projetoB->id,
            'nome'       => 'Tarefa Maliciosa',
        ]);

        $this->assertContains($response->status(), [302, 403]);
    }

    /** @test */
    public function user_A_cannot_update_tarefa_from_company_B(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);
        $tarefaB = $this->makeTarefa($projetoB);

        $response = $this->actingAs($this->userA)->put("/tarefas/{$tarefaB->id}", [
            'nome' => 'Hacked Tarefa',
        ]);

        $response->assertNotFound();
    }

    /** @test */
    public function user_A_blocked_from_tarefa_reorder_master_only(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);
        $tarefaB = $this->makeTarefa($projetoB);

        $response = $this->actingAs($this->userA)->post('/tarefas/reorder', [
            'tasks' => [
                ['id' => $tarefaB->id, 'kanban_coluna_id' => null, 'ordem' => 999],
            ],
        ]);

        $this->assertContains($response->status(), [302, 403]);
    }

    // =================================================================
    // TESTES DE ISOLAMENTO — KanbanColuna
    // =================================================================

    /** @test */
    public function user_A_blocked_from_kanban_create_master_only(): void
    {
        $projetoB = $this->makeProjeto($this->companyB);

        $response = $this->actingAs($this->userA)->post('/kanban-colunas', [
            'projeto_id' => $projetoB->id,
            'nome'       => 'Coluna Maliciosa',
        ]);

        $this->assertContains($response->status(), [302, 403]);
    }

    // =================================================================
    // TESTES DE ISOLAMENTO — NaoConformidade
    // =================================================================

    /** @test */
    public function user_A_cannot_view_nc_from_company_B(): void
    {
        $ncB = $this->makeNC($this->companyB);

        $response = $this->actingAs($this->userA)->get("/nao-conformidades/{$ncB->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function user_A_cannot_delete_nc_from_company_B(): void
    {
        $ncB = $this->makeNC($this->companyB);

        $response = $this->actingAs($this->userA)->delete("/nao-conformidades/{$ncB->id}");

        $response->assertNotFound();
    }

    // =================================================================
    // TESTES DE MASS-ASSIGNMENT / ESCALATION
    // =================================================================

    /** @test */
    public function user_A_blocked_from_projeto_create_via_master_only(): void
    {
        // Substitui o teste anterior que validava Tenantable em
        // /projetos. Agora userA (nao-master) sequer chega no
        // Tenantable porque o middleware bloqueia antes.
        // O teste de mass-assignment de company_id ainda existe na
        // forma "user A cannot force company id" para OUTROS modelos
        // (NC, plano-acao, auditoria), nao precisamos repetir aqui.
        $uniqueName = 'Tentativa de injeção ' . uniqid();

        $response = $this->actingAs($this->userA)->post('/projetos', [
            'nomeProjeto' => $uniqueName,
            'company_id'  => $this->companyB->id,
        ]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertDatabaseMissing('sts_projetos', [
            'nomeProjeto' => $uniqueName,
        ]);
    }

    /** @test */
    public function admin_user_create_rejects_is_master_admin_in_body(): void
    {
        $response = $this->actingAs($this->masterAdmin)->post('/admin/users', [
            'name'            => 'Escalator',
            'email'           => 'escalator-' . uniqid() . '@test.local',
            'password'        => 'SuperSecret@123!',
            'is_master_admin' => true,  // tentativa de escalada
        ]);

        $response->assertSessionHasErrors('is_master_admin');
    }

    // =================================================================
    // POSITIVOS — Master admin VÊ tudo
    // =================================================================

    /** @test */
    public function master_admin_sees_projetos_from_all_companies(): void
    {
        $projetoA = $this->makeProjeto($this->companyA);
        $projetoB = $this->makeProjeto($this->companyB);

        $response = $this->actingAs($this->masterAdmin)->get('/projetos');

        $response->assertStatus(200);
        $response->assertSee($projetoA->nomeProjeto);
        $response->assertSee($projetoB->nomeProjeto);
    }

    // =================================================================
    // AUTENTICAÇÃO E VERIFICAÇÃO
    // =================================================================

    /** @test */
    public function unauthenticated_request_is_redirected_to_login(): void
    {
        $response = $this->get('/projetos');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function unverified_user_cannot_access_qsms_routes(): void
    {
        $unverified = User::factory()->unverified()->create([
            'company_id'      => $this->companyA->id,
            'is_master_admin' => false,
        ]);

        $response = $this->actingAs($unverified)->get('/projetos');

        // verified middleware: redireciona para verify-email
        $response->assertStatus(302);
        $response->assertRedirect(route('verification.notice'));
    }
}
