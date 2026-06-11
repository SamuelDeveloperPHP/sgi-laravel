<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ControleCalibracaoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-controle-calibracoes',
            'manage-controle-calibracoes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Super Admin (Master Admin)
        $roleMasterAdmin = Role::where('name', 'Master Admin')->first();
        if ($roleMasterAdmin) {
            $roleMasterAdmin->givePermissionTo($permissions);
        }

        // Assign to Admin (Local Admin)
        $roleAdmin = Role::where('name', 'Admin')->first();
        if ($roleAdmin) {
            $roleAdmin->givePermissionTo($permissions);
        }
    }
}
