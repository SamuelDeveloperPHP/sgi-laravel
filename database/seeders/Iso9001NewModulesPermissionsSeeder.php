<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Iso9001NewModulesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-nossa-historia',
            'manage-nossa-historia',
            'view-missao-visao-valores',
            'manage-missao-visao-valores',
            'view-controle-documentos',
            'manage-controle-documentos',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Super Admin (Master Admin)
        $roleMasterAdmin = Role::where('name', 'Master Admin')->first();
        if ($roleMasterAdmin) {
            $roleMasterAdmin->givePermissionTo($permissions);
        }
    }
}
