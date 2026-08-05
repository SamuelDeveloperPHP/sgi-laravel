<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\DocumentoRegistro;
use App\Models\DocumentoRevisao;
use App\Models\NossaHistoria;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class CrossTenantAssociationTest extends SecurityTestCase
{
    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();
        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email_verified_at' => now(),
        ]);
        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email_verified_at' => now(),
        ]);

        $permissions = [
            'manage-controle-documentos',
            'manage-objetivos-qualidade',
            'manage-missao-visao-valores',
            'manage-mapas-risco',
            'manage-analise-swot',
            'manage-nossa-historia',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->userA->givePermissionTo($permissions);
    }

    public function test_revision_from_another_company_cannot_be_deleted(): void
    {
        $document = DocumentoRegistro::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyB->id,
            'identificacao' => 'Documento confidencial B',
        ]);
        $revision = DocumentoRevisao::forceCreate([
            'documento_id' => $document->id,
            'revisao' => '01',
            'data_revisao' => now()->toDateString(),
        ]);

        $this->actingAs($this->userA)
            ->delete("/controle-documentos/{$document->id}/revisoes/{$revision->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('sts_documento_revisoes', ['id' => $revision->id]);
    }

    public function test_document_revision_rejects_users_from_another_company(): void
    {
        $document = DocumentoRegistro::forceCreate([
            'company_id' => $this->companyA->id,
            'identificacao' => 'Documento A',
        ]);

        $this->actingAs($this->userA)
            ->post("/controle-documentos/{$document->id}/revisoes", [
                'revisao' => '02',
                'data_revisao' => now()->toDateString(),
                'responsavel_id' => $this->userB->id,
                'aprovador_id' => $this->userB->id,
            ])
            ->assertSessionHasErrors(['responsavel_id', 'aprovador_id']);

        $this->assertDatabaseMissing('sts_documento_revisoes', [
            'documento_id' => $document->id,
            'revisao' => '02',
        ]);
    }

    public function test_quality_objective_rejects_cross_company_assignments(): void
    {
        $this->actingAs($this->userA)
            ->post('/objetivos-qualidade', [
                'titulo' => 'Objetivo indevido cross-tenant',
                'prazo' => now()->addMonth()->toDateString(),
                'responsaveis' => [$this->userB->id],
                'revisor_id' => $this->userB->id,
                'aprovador_id' => $this->userB->id,
                'company_id' => $this->companyB->id,
            ])
            ->assertSessionHasErrors(['responsaveis.0', 'revisor_id', 'aprovador_id']);

        $this->assertDatabaseMissing('sts_objetivos_qualidade', [
            'titulo' => 'Objetivo indevido cross-tenant',
        ]);
    }

    public function test_workflow_rejects_foreign_reviewer_and_approver(): void
    {
        $this->actingAs($this->userA)
            ->post('/missao-visao-valores/salvar-rascunho', [
                'conteudo' => 'Conteudo da empresa A',
                'revisor_id' => $this->userB->id,
                'aprovador_id' => $this->userB->id,
            ])
            ->assertSessionHasErrors(['revisor_id', 'aprovador_id']);
    }

    public function test_risk_map_rejects_foreign_approver_and_company_tampering(): void
    {
        $this->actingAs($this->userA)
            ->post('/mapas-risco', [
                'company_id' => $this->companyB->id,
                'titulo' => 'Mapa indevido cross-tenant',
                'setor' => 'Producao',
                'aprovador_id' => $this->userB->id,
                'data_mapeamento' => now()->toDateString(),
                'pontos_risco' => [[
                    'local_detalhado' => 'Linha 1',
                    'grupo_risco' => 'Fisico',
                    'agente_risco' => 'Ruido',
                    'gravidade' => 'Pequeno',
                    'numero_trabalhadores_expostos' => 1,
                ]],
            ])
            ->assertSessionHasErrors(['aprovador_id']);

        $this->assertDatabaseMissing('sts_mapas_risco', [
            'titulo' => 'Mapa indevido cross-tenant',
        ]);
    }

    public function test_swot_rejects_foreign_approver_and_company_tampering(): void
    {
        $this->actingAs($this->userA)
            ->post('/analise-swot', [
                'company_id' => $this->companyB->id,
                'titulo' => 'SWOT indevida cross-tenant',
                'data_analise' => now()->toDateString(),
                'aprovador_id' => $this->userB->id,
            ])
            ->assertSessionHasErrors(['aprovador_id']);

        $this->assertDatabaseMissing('iso_swot_analyses', [
            'titulo' => 'SWOT indevida cross-tenant',
        ]);
    }

    public function test_master_can_save_history_for_selected_company_without_explicit_permission(): void
    {
        $master = User::factory()->create([
            'is_master_admin' => true,
            'company_id' => null,
            'email_verified_at' => now(),
        ]);
        NossaHistoria::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyA->id,
            'conteudo' => 'Conteudo anterior',
        ]);

        $this->actingAs($master)
            ->post('/nossa-historia/salvar', [
                'company_id' => $this->companyA->id,
                'conteudo' => 'Conteudo salvo pelo master',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sts_nossa_historia', [
            'company_id' => $this->companyA->id,
            'conteudo' => 'Conteudo salvo pelo master',
        ]);
    }

    public function test_company_user_cannot_save_history_for_another_company(): void
    {
        NossaHistoria::withoutGlobalScopes()->forceCreate([
            'company_id' => $this->companyB->id,
            'conteudo' => 'Segredo B',
        ]);

        $this->actingAs($this->userA)
            ->post('/nossa-historia/salvar', [
                'company_id' => $this->companyB->id,
                'conteudo' => 'Tentativa da empresa A',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('sts_nossa_historia', [
            'company_id' => $this->companyB->id,
            'conteudo' => 'Segredo B',
        ]);
    }

}
