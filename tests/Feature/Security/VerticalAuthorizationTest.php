<?php

namespace Tests\Feature\Security;

use App\Models\Area;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class VerticalAuthorizationTest extends SecurityTestCase
{
    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'status' => true,
            'registration_status' => 'approved',
        ]);

        $this->user = User::factory()->create();
        $this->user->forceFill([
            'company_id' => $this->company->id,
            'is_master_admin' => false,
            'is_active' => true,
        ])->save();
        $this->user->syncRoles([]);
        $this->user->syncPermissions([]);
    }

    public function test_user_without_permissions_cannot_open_sensitive_modules_by_url(): void
    {
        $paths = [
            '/hr/dashboard',
            '/hr/areas',
            '/hr/cargos',
            '/hr/beneficios',
            '/hr/folha-pagamento',
            '/hr/ferias',
            '/auditorias',
            '/planos-acao',
            '/nao-conformidades',
        ];

        foreach ($paths as $path) {
            $this->actingAs($this->user)->get($path)->assertForbidden();
        }
    }

    public function test_area_crud_requires_a_separate_permission_for_each_action(): void
    {
        $this->actingAs($this->user)
            ->post('/hr/areas', ['nome' => 'Area protegida'])
            ->assertForbidden();
        $this->assertDatabaseMissing('rh_areas', ['nome' => 'Area protegida']);

        $this->grant('view-areas');
        $this->actingAs($this->user)->get('/hr/areas')->assertOk();
        $this->actingAs($this->user)
            ->post('/hr/areas', ['nome' => 'Area protegida'])
            ->assertForbidden();

        $this->grant('create-areas');
        $this->actingAs($this->user)
            ->post('/hr/areas', ['nome' => 'Area protegida'])
            ->assertRedirect(route('admin.hr.areas.index'));

        $area = Area::where('nome', 'Area protegida')->firstOrFail();

        $this->actingAs($this->user)
            ->put('/hr/areas/'.$area->id, ['nome' => 'Area alterada'])
            ->assertForbidden();
        $this->assertDatabaseHas('rh_areas', [
            'id' => $area->id,
            'nome' => 'Area protegida',
        ]);

        $this->grant('edit-areas');
        $this->actingAs($this->user)
            ->put('/hr/areas/'.$area->id, ['nome' => 'Area alterada'])
            ->assertRedirect(route('admin.hr.areas.index'));

        $this->actingAs($this->user)
            ->delete('/hr/areas/'.$area->id)
            ->assertForbidden();
        $this->assertDatabaseHas('rh_areas', ['id' => $area->id]);

        $this->grant('delete-areas');
        $this->actingAs($this->user)
            ->delete('/hr/areas/'.$area->id)
            ->assertRedirect(route('admin.hr.areas.index'));
        $this->assertDatabaseMissing('rh_areas', ['id' => $area->id]);
    }

    public function test_permission_does_not_bypass_company_isolation(): void
    {
        $otherCompany = Company::factory()->create([
            'status' => true,
            'registration_status' => 'approved',
        ]);
        $foreignAreaId = \DB::table('rh_areas')->insertGetId([
            'company_id' => $otherCompany->id,
            'nome' => 'Area de outra empresa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['view-areas', 'edit-areas', 'delete-areas'] as $permission) {
            $this->grant($permission);
        }

        $this->actingAs($this->user)
            ->put('/hr/areas/'.$foreignAreaId, ['nome' => 'Tentativa cross-tenant'])
            ->assertNotFound();
        $this->actingAs($this->user)
            ->delete('/hr/areas/'.$foreignAreaId)
            ->assertNotFound();

        $this->assertDatabaseHas('rh_areas', [
            'id' => $foreignAreaId,
            'company_id' => $otherCompany->id,
            'nome' => 'Area de outra empresa',
        ]);
    }

    private function grant(string $permission): void
    {
        Permission::findOrCreate($permission, 'web');
        $this->user->givePermissionTo($permission);
        $this->user->refresh();
    }
}
