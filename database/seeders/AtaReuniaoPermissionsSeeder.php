<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AtaReuniaoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view-atas-reuniao',
            'manage-atas-reuniao',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roleMasterAdmin = Role::where('name', 'Master Admin')->first();
        if ($roleMasterAdmin) {
            $roleMasterAdmin->givePermissionTo($permissions);
        }
    }
}
