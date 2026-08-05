<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class UnusedAdminShowRoutesTest extends SecurityTestCase
{
    public function test_unused_admin_show_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('admin.users.show'));
        $this->assertFalse(Route::has('admin.companies.show'));
        $this->assertFalse(Route::has('admin.modules.show'));
    }

    public function test_direct_admin_show_urls_return_method_not_allowed_instead_of_server_error(): void
    {
        $master = User::factory()->create(['email_verified_at' => now()]);
        $master->forceFill(['is_master_admin' => true])->save();

        $targetUser = User::factory()->create();
        $company = Company::factory()->create();
        $module = Module::create([
            'name' => 'Modulo temporario de teste',
            'slug' => 'test-unused-show-'.uniqid(),
            'order' => 999,
            'is_active' => true,
            'is_visible_in_menu' => false,
        ]);

        $this->actingAs($master)
            ->get('/admin/users/'.$targetUser->id)
            ->assertStatus(405);

        $this->get('/admin/companies/'.$company->id)
            ->assertStatus(405);

        $this->get('/admin/modules/'.$module->id)
            ->assertStatus(405);
    }
}
