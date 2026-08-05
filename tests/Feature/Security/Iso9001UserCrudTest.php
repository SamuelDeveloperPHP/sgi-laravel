<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\Escopo;
use App\Models\MissaoVisaoValores;
use App\Models\NossaHistoria;
use App\Models\ObjetivoQualidade;
use App\Models\PoliticaQualidade;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Iso9001UserCrudTest extends SecurityTestCase
{
    private Company $company;
    private User $user;
    private User $reviewer;
    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);
        $this->reviewer = User::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);
        $this->approver = User::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);

        $permissions = [
            'view-nossa-historia',
            'manage-nossa-historia',
            'view-missao-visao-valores',
            'manage-missao-visao-valores',
            'view-politica-qualidade',
            'manage-politica-qualidade',
            'view-escopo',
            'manage-escopo',
            'view-objetivos-qualidade',
            'manage-objetivos-qualidade',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->user->givePermissionTo($permissions);
        $this->actingAs($this->user);
    }

    public function test_user_can_create_read_update_and_clear_company_history(): void
    {
        $this->get('/nossa-historia')->assertOk();

        $history = NossaHistoria::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->firstOrFail();

        $this->post('/nossa-historia/salvar', [
            'company_id' => $this->company->id,
            'conteudo' => '<h1>História inicial</h1><script>alert(1)</script>',
        ])->assertRedirect();

        $history = NossaHistoria::withoutGlobalScopes()->findOrFail($history->id);
        $this->assertStringContainsString('<h1>História inicial</h1>', $history->conteudo);
        $this->assertStringNotContainsStringIgnoringCase('<script', $history->conteudo);

        $this->get('/nossa-historia')->assertOk();

        $this->post('/nossa-historia/salvar', [
            'company_id' => $this->company->id,
            'conteudo' => '<h2>História atualizada</h2>',
        ])->assertRedirect();

        $this->assertDatabaseHas('sts_nossa_historia', [
            'id' => $history->id,
            'company_id' => $this->company->id,
            'conteudo' => '<h2>História atualizada</h2>',
        ]);

        $this->post('/nossa-historia/salvar', [
            'company_id' => $this->company->id,
            'conteudo' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('sts_nossa_historia', [
            'id' => $history->id,
            'company_id' => $this->company->id,
            'conteudo' => null,
        ]);
    }

    public function test_user_can_create_read_update_and_clear_mission_vision_and_values(): void
    {
        $this->exerciseSingletonDocument(
            '/missao-visao-valores',
            '/missao-visao-valores/salvar-rascunho',
            MissaoVisaoValores::class,
            'sts_missao_visao_valores',
            'Missão, visão e valores'
        );
    }

    public function test_user_can_create_read_update_and_clear_quality_policy(): void
    {
        $this->exerciseSingletonDocument(
            '/politica-qualidade',
            '/politica-qualidade/salvar-rascunho',
            PoliticaQualidade::class,
            'sts_politica_qualidade',
            'Política da qualidade'
        );
    }

    public function test_user_can_create_read_update_and_clear_sgi_scope(): void
    {
        $this->exerciseSingletonDocument(
            '/escopo',
            '/escopo/salvar-rascunho',
            Escopo::class,
            'sts_escopo_sgi',
            'Escopo do SGI'
        );
    }

    public function test_user_can_complete_quality_objective_crud(): void
    {
        $this->get('/objetivos-qualidade')->assertOk();
        $this->get('/objetivos-qualidade/create')->assertOk();

        $this->post('/objetivos-qualidade', [
            'company_id' => $this->company->id,
            'titulo' => 'Objetivo CRUD ISO 9001',
            'descricao' => '<h2>Descrição inicial</h2><script>alert(1)</script>',
            'prazo' => now()->addMonth()->toDateString(),
            'responsaveis' => [$this->user->id],
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertRedirect();

        $objective = ObjetivoQualidade::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('titulo', 'Objetivo CRUD ISO 9001')
            ->firstOrFail();

        $this->assertStringContainsString('<h2>Descrição inicial</h2>', $objective->descricao);
        $this->assertStringNotContainsStringIgnoringCase('<script', $objective->descricao);
        $this->assertTrue($objective->responsaveis()->whereKey($this->user->id)->exists());

        $this->get("/objetivos-qualidade/{$objective->id}")->assertOk();
        $this->get("/objetivos-qualidade/{$objective->id}/edit")->assertOk();

        $this->put("/objetivos-qualidade/{$objective->id}", [
            'titulo' => 'Objetivo CRUD atualizado',
            'descricao' => '<h3>Descrição atualizada</h3>',
            'prazo' => now()->addMonths(2)->toDateString(),
            'responsaveis' => [$this->reviewer->id],
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('sts_objetivos_qualidade', [
            'id' => $objective->id,
            'company_id' => $this->company->id,
            'titulo' => 'Objetivo CRUD atualizado',
            'descricao' => '<h3>Descrição atualizada</h3>',
        ]);
        $this->assertTrue($objective->responsaveis()->whereKey($this->reviewer->id)->exists());
        $this->assertFalse($objective->responsaveis()->whereKey($this->user->id)->exists());

        $this->delete("/objetivos-qualidade/{$objective->id}")->assertRedirect('/objetivos-qualidade');

        $this->assertSoftDeleted('sts_objetivos_qualidade', [
            'id' => $objective->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_user_without_iso_permissions_cannot_access_the_five_modules(): void
    {
        $unauthorized = User::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($unauthorized);

        $this->get('/nossa-historia')->assertForbidden();
        $this->get('/missao-visao-valores')->assertForbidden();
        $this->get('/politica-qualidade')->assertForbidden();
        $this->get('/escopo')->assertForbidden();
        $this->get('/objetivos-qualidade')->assertForbidden();
    }

    public function test_master_can_save_iso_documents_without_explicit_permissions(): void
    {
        $master = User::factory()->create([
            'company_id' => null,
            'is_master_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($master);

        $documents = [
            ['/missao-visao-valores', '/missao-visao-valores/salvar-rascunho', 'sts_missao_visao_valores'],
            ['/politica-qualidade', '/politica-qualidade/salvar-rascunho', 'sts_politica_qualidade'],
            ['/escopo', '/escopo/salvar-rascunho', 'sts_escopo_sgi'],
        ];

        foreach ($documents as [$indexUrl, $saveUrl, $table]) {
            $this->get($indexUrl.'?company_id='.$this->company->id)->assertOk();

            $this->post($saveUrl, [
                'company_id' => $this->company->id,
                'conteudo' => '<h2>Documento salvo pelo master</h2>',
                'revisor_id' => $this->reviewer->id,
                'aprovador_id' => $this->approver->id,
            ])->assertRedirect();

            $this->assertDatabaseHas($table, [
                'company_id' => $this->company->id,
                'conteudo' => '<h2>Documento salvo pelo master</h2>',
            ]);
        }

        $this->post('/objetivos-qualidade', [
            'company_id' => $this->company->id,
            'titulo' => 'Objetivo cadastrado pelo master',
            'descricao' => '<p>Cadastro autorizado pelo Gate.</p>',
            'prazo' => now()->addMonth()->toDateString(),
            'responsaveis' => [$this->reviewer->id],
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('sts_objetivos_qualidade', [
            'company_id' => $this->company->id,
            'titulo' => 'Objetivo cadastrado pelo master',
        ]);
    }

    public function test_user_cannot_use_iso_crud_routes_against_another_company(): void
    {
        $foreignCompany = Company::factory()->create();

        NossaHistoria::withoutEvents(fn () => NossaHistoria::withoutGlobalScopes()->forceCreate([
            'company_id' => $foreignCompany->id,
            'conteudo' => 'História sigilosa da empresa B',
        ]));

        $this->post('/nossa-historia/salvar', [
            'company_id' => $foreignCompany->id,
            'conteudo' => 'Tentativa indevida',
        ])->assertForbidden();

        $this->assertDatabaseHas('sts_nossa_historia', [
            'company_id' => $foreignCompany->id,
            'conteudo' => 'História sigilosa da empresa B',
        ]);

        $singletons = [
            [MissaoVisaoValores::class, 'sts_missao_visao_valores', '/missao-visao-valores/salvar-rascunho'],
            [PoliticaQualidade::class, 'sts_politica_qualidade', '/politica-qualidade/salvar-rascunho'],
            [Escopo::class, 'sts_escopo_sgi', '/escopo/salvar-rascunho'],
        ];

        foreach ($singletons as [$modelClass, $table, $saveUrl]) {
            $modelClass::withoutGlobalScopes()->forceCreate([
                'company_id' => $this->company->id,
                'conteudo' => 'Documento da empresa A',
                'status' => 'rascunho',
            ]);
            $modelClass::withoutEvents(fn () => $modelClass::withoutGlobalScopes()->forceCreate([
                'company_id' => $foreignCompany->id,
                'conteudo' => 'Documento sigiloso da empresa B',
                'status' => 'rascunho',
            ]));

            $this->post($saveUrl, [
                'company_id' => $foreignCompany->id,
                'conteudo' => 'Tentativa indevida',
                'revisor_id' => $this->reviewer->id,
                'aprovador_id' => $this->approver->id,
            ])->assertRedirect();

            $this->assertDatabaseHas($table, [
                'company_id' => $foreignCompany->id,
                'conteudo' => 'Documento sigiloso da empresa B',
            ]);
            $this->assertDatabaseHas($table, [
                'company_id' => $this->company->id,
                'conteudo' => 'Tentativa indevida',
            ]);
        }

        $foreignObjective = ObjetivoQualidade::withoutEvents(fn () => ObjetivoQualidade::withoutGlobalScopes()->forceCreate([
            'company_id' => $foreignCompany->id,
            'titulo' => 'Objetivo sigiloso da empresa B',
            'descricao' => 'Não pode ser acessado pela empresa A',
            'prazo' => now()->addMonth()->toDateString(),
            'status' => 'rascunho',
        ]));

        $this->get("/objetivos-qualidade/{$foreignObjective->id}")->assertNotFound();
        $this->get("/objetivos-qualidade/{$foreignObjective->id}/edit")->assertNotFound();
        $this->put("/objetivos-qualidade/{$foreignObjective->id}", [
            'titulo' => 'Tentativa indevida',
            'descricao' => 'Tentativa indevida',
            'prazo' => now()->addMonths(2)->toDateString(),
            'responsaveis' => [$this->user->id],
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertNotFound();
        $this->delete("/objetivos-qualidade/{$foreignObjective->id}")->assertNotFound();

        $this->assertDatabaseHas('sts_objetivos_qualidade', [
            'id' => $foreignObjective->id,
            'company_id' => $foreignCompany->id,
            'titulo' => 'Objetivo sigiloso da empresa B',
            'deleted_at' => null,
        ]);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function exerciseSingletonDocument(
        string $indexUrl,
        string $saveUrl,
        string $modelClass,
        string $table,
        string $label
    ): void {
        $this->get($indexUrl)->assertOk();

        $document = $modelClass::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->firstOrFail();

        $this->post($saveUrl, [
            'conteudo' => "<h1>{$label}</h1><script>alert(1)</script>",
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertRedirect();

        $document = $modelClass::withoutGlobalScopes()->findOrFail($document->id);
        $this->assertStringContainsString("<h1>{$label}</h1>", $document->conteudo);
        $this->assertStringNotContainsStringIgnoringCase('<script', $document->conteudo);
        $this->assertSame($this->reviewer->id, $document->revisor_id);
        $this->assertSame($this->approver->id, $document->aprovador_id);

        $this->get($indexUrl)->assertOk();

        $this->post($saveUrl, [
            'conteudo' => "<h2>{$label} atualizado</h2>",
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertRedirect();

        $this->assertDatabaseHas($table, [
            'id' => $document->id,
            'company_id' => $this->company->id,
            'conteudo' => "<h2>{$label} atualizado</h2>",
        ]);

        $this->post($saveUrl, [
            'conteudo' => '',
            'revisor_id' => $this->reviewer->id,
            'aprovador_id' => $this->approver->id,
        ])->assertRedirect();

        $this->assertDatabaseHas($table, [
            'id' => $document->id,
            'company_id' => $this->company->id,
            'conteudo' => null,
        ]);
    }
}
