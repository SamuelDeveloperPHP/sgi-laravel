<?php

namespace Database\Seeders;

use App\Support\AuthorizationPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = AuthorizationPermissions::all();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create Roles and assign created permissions

        // User (Common)
        $roleUser = Role::firstOrCreate(['name' => 'User']);
        $roleUser->givePermissionTo([
            'view-dashboard',
            'list-dashboard',
            'view-auditorias',
            'list-auditorias',
            'view-naoconformidades',
            'list-naoconformidades',
            'create-naoconformidades',
            'view-planosacao',
            'list-planosacao',
        ]);

        // Admin
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleAdmin->givePermissionTo(Permission::all());
        $roleAdmin->revokePermissionTo(['manage-companies']); // Example restriction

        // Master Admin
        $roleMasterAdmin = Role::firstOrCreate(['name' => 'Master Admin']);
        $roleMasterAdmin->givePermissionTo(Permission::all());
    }
}
