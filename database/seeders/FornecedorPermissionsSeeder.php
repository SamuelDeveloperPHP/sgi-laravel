<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FornecedorPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-fornecedores',
            'manage-fornecedores',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Master Admin
        $roleMasterAdmin = Role::where('name', 'Master Admin')->first();
        if ($roleMasterAdmin) {
            $roleMasterAdmin->givePermissionTo($permissions);
        }

        // Assign to Admin
        $roleAdmin = Role::where('name', 'Admin')->first();
        if ($roleAdmin) {
            $roleAdmin->givePermissionTo($permissions);
        }
    }
}
