<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'view-dashboard',
            'view-auditorias',
            'create-auditorias',
            'edit-auditorias',
            'delete-auditorias',
            'view-naoconformidades',
            'create-naoconformidades',
            'edit-naoconformidades',
            'delete-naoconformidades',
            'view-planosacao',
            'create-planosacao',
            'edit-planosacao',
            'delete-planosacao',
            'view-companies',
            'manage-companies',
            'view-users',
            'manage-users',
            'view-politica-qualidade',
            'manage-politica-qualidade',
            'view-escopo',
            'manage-escopo',
            'view-objetivos-qualidade',
            'manage-objetivos-qualidade',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and assign created permissions

        // User (Common)
        $roleUser = Role::firstOrCreate(['name' => 'User']);
        $roleUser->givePermissionTo([
            'view-dashboard',
            'view-auditorias',
            'view-naoconformidades',
            'create-naoconformidades',
            'view-planosacao'
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
