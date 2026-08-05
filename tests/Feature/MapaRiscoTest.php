<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\MapaRisco;
use Tests\Feature\Security\SecurityTestCase;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MapaRiscoNotification;

class MapaRiscoTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup base permissions
        Permission::findOrCreate('view-mapas-risco', 'web');
        Permission::findOrCreate('manage-mapas-risco', 'web');
    }

    public function test_guest_user_cannot_access_mapas_risco()
    {
        $response = $this->get(route('mapas-risco.index'));
        $response->assertRedirect('/login');
    }

    public function test_authorized_user_can_list_mapas_risco_of_their_company()
    {
        $company = Company::factory()->create(['nome_fantasia' => 'Company A']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('view-mapas-risco');

        $mapa1 = MapaRisco::create([
            'company_id' => $company->id,
            'titulo' => 'Mapa Teste 1',
            'setor' => 'Producao',
            'data_mapeamento' => '2026-06-15',
            'status' => 'draft',
            'pontos_risco' => []
        ]);

        $response = $this->actingAs($user)->get(route('mapas-risco.index'));
        $response->assertOk();
    }

    public function test_tenant_isolation_on_listing_mapas_risco()
    {
        $companyA = Company::factory()->create(['nome_fantasia' => 'Company A']);
        $companyB = Company::factory()->create(['nome_fantasia' => 'Company B']);

        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userA->givePermissionTo('view-mapas-risco');

        $mapaA = MapaRisco::create([
            'company_id' => $companyA->id,
            'titulo' => 'Mapa Company A',
            'setor' => 'Setor A',
            'data_mapeamento' => '2026-06-15',
            'status' => 'draft',
            'pontos_risco' => []
        ]);

        $mapaB = MapaRisco::create([
            'company_id' => $companyB->id,
            'titulo' => 'Mapa Company B',
            'setor' => 'Setor B',
            'data_mapeamento' => '2026-06-15',
            'status' => 'draft',
            'pontos_risco' => []
        ]);

        // Access as User A
        $response = $this->actingAs($userA)->get(route('mapas-risco.index'));
        
        $mapasShared = $response->original->getData()['page']['props']['mapas']['data'] ?? [];
        
        $this->assertTrue(collect($mapasShared)->contains('id', $mapaA->id));
        $this->assertFalse(collect($mapasShared)->contains('id', $mapaB->id));
    }

    public function test_master_admin_can_bypass_tenant_isolation_and_see_all_mapas_risco()
    {
        $companyA = Company::factory()->create(['nome_fantasia' => 'Company A']);
        $companyB = Company::factory()->create(['nome_fantasia' => 'Company B']);

        $masterAdmin = User::factory()->create();
        $masterAdmin->is_master_admin = true;
        $masterAdmin->save();
        $masterAdmin->givePermissionTo('view-mapas-risco');

        $mapaA = MapaRisco::create([
            'company_id' => $companyA->id,
            'titulo' => 'Mapa Company A',
            'setor' => 'Setor A',
            'data_mapeamento' => '2026-06-15',
            'status' => 'draft',
            'pontos_risco' => []
        ]);

        $mapaB = MapaRisco::create([
            'company_id' => $companyB->id,
            'titulo' => 'Mapa Company B',
            'setor' => 'Setor B',
            'data_mapeamento' => '2026-06-15',
            'status' => 'draft',
            'pontos_risco' => []
        ]);

        // Access as Master Admin without company filter (defaults to first company or all)
        $response = $this->actingAs($masterAdmin)->get(route('mapas-risco.index', ['company_id' => $companyA->id]));
        $mapasShared = $response->original->getData()['page']['props']['mapas']['data'] ?? [];
        $this->assertTrue(collect($mapasShared)->contains('id', $mapaA->id));

        $response = $this->actingAs($masterAdmin)->get(route('mapas-risco.index', ['company_id' => $companyB->id]));
        $mapasShared = $response->original->getData()['page']['props']['mapas']['data'] ?? [];
        $this->assertTrue(collect($mapasShared)->contains('id', $mapaB->id));
    }

    public function test_authorized_user_can_create_mapa_risco()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('manage-mapas-risco');

        $aprovador = User::factory()->create(['company_id' => $company->id]);

        $payload = [
            'company_id' => $company->id,
            'titulo' => 'Novo Mapa',
            'setor' => 'Almoxarifado',
            'aprovador_id' => $aprovador->id,
            'data_mapeamento' => '2026-06-15',
            'pontos_risco' => [
                [
                    'local_detalhado' => 'Corredor A',
                    'grupo_risco' => 'Físico',
                    'agente_risco' => 'Ruído',
                    'gravidade' => 'Grande',
                    'numero_trabalhadores_expostos' => 5,
                    'medidas_preventivas' => 'Uso de protetor auricular'
                ]
            ],
            'user_create' => $user->id
        ];

        $response = $this->actingAs($user)->post(route('mapas-risco.store'), $payload);
        $response->assertRedirect(route('mapas-risco.index', ['company_id' => $company->id]));

        $this->assertDatabaseHas('sts_mapas_risco', [
            'titulo' => 'Novo Mapa',
            'setor' => 'Almoxarifado',
            'status' => 'draft'
        ]);
    }

    public function test_user_can_send_mapa_risco_for_approval_and_dispatches_notification()
    {
        Notification::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('manage-mapas-risco');

        $aprovador = User::factory()->create(['company_id' => $company->id]);

        $mapa = MapaRisco::create([
            'company_id' => $company->id,
            'titulo' => 'Mapa Pendente',
            'setor' => 'Logística',
            'aprovador_id' => $aprovador->id,
            'data_mapeamento' => '2026-06-15',
            'status' => 'draft',
            'user_create' => $user->id,
            'pontos_risco' => []
        ]);

        $response = $this->actingAs($user)->post(route('mapas-risco.enviar-aprovacao', $mapa->id));
        $response->assertRedirect();

        $this->assertEquals('pending_approval', $mapa->fresh()->status);

        Notification::assertSentTo(
            $aprovador,
            MapaRiscoNotification::class,
            function ($notification, $channels) use ($mapa, $aprovador) {
                return $notification->toArray($aprovador)['mapa_risco_id'] === $mapa->id;
            }
        );
    }

    public function test_only_assigned_aprovador_can_approve_or_reject()
    {
        Notification::fake();

        $company = Company::factory()->create();
        $creator = User::factory()->create(['company_id' => $company->id]);
        $aprovador = User::factory()->create(['company_id' => $company->id]);
        $otherUser = User::factory()->create(['company_id' => $company->id]);

        $mapa = MapaRisco::create([
            'company_id' => $company->id,
            'titulo' => 'Mapa Decisão',
            'setor' => 'Química',
            'aprovador_id' => $aprovador->id,
            'data_mapeamento' => '2026-06-15',
            'status' => 'pending_approval',
            'user_create' => $creator->id,
            'pontos_risco' => []
        ]);

        // Outro usuario nao pode aprovar, mesmo conhecendo a URL.
        $response = $this->actingAs($otherUser)->post(route('mapas-risco.aprovar', $mapa->id));
        $response->assertForbidden();

        // Aprovador approves -> success
        $response = $this->actingAs($aprovador)->post(route('mapas-risco.aprovar', $mapa->id));
        $response->assertRedirect();
        $this->assertEquals('approved', $mapa->fresh()->status);

        Notification::assertSentTo(
            $creator,
            MapaRiscoNotification::class
        );
    }

    public function test_aprovador_can_reject_with_reason()
    {
        Notification::fake();

        $company = Company::factory()->create();
        $creator = User::factory()->create(['company_id' => $company->id]);
        $aprovador = User::factory()->create(['company_id' => $company->id]);

        $mapa = MapaRisco::create([
            'company_id' => $company->id,
            'titulo' => 'Mapa Decisão Rejeição',
            'setor' => 'Operações',
            'aprovador_id' => $aprovador->id,
            'data_mapeamento' => '2026-06-15',
            'status' => 'pending_approval',
            'user_create' => $creator->id,
            'pontos_risco' => []
        ]);

        // Try to reject without a reason
        $response = $this->actingAs($aprovador)->post(route('mapas-risco.rejeitar', $mapa->id), [
            'motivo_rejeicao' => ''
        ]);
        $response->assertSessionHasErrors('motivo_rejeicao');

        // Reject with a reason
        $response = $this->actingAs($aprovador)->post(route('mapas-risco.rejeitar', $mapa->id), [
            'motivo_rejeicao' => 'Desenho dos círculos está incorreto.'
        ]);
        $response->assertRedirect();

        $mapa = $mapa->fresh();
        $this->assertEquals('rejected', $mapa->status);
        $this->assertEquals('Desenho dos círculos está incorreto.', $mapa->motivo_rejeicao);

        Notification::assertSentTo($creator, MapaRiscoNotification::class);
    }
}
