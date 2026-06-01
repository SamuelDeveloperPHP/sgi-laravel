<?php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$permissions = ['view-projetos', 'create-projetos', 'edit-projetos', 'delete-projetos'];
foreach ($permissions as $p) {
    Permission::firstOrCreate(['name' => $p]);
}

$userRole = Role::where('name', 'User')->first();
if ($userRole) {
    $userRole->givePermissionTo('view-projetos');
    $userRole->givePermissionTo('create-projetos');
}

$adminRole = Role::where('name', 'Admin')->first();
if ($adminRole) {
    $adminRole->givePermissionTo($permissions);
}

$masterRole = Role::where('name', 'Master Admin')->first();
if ($masterRole) {
    $masterRole->givePermissionTo($permissions);
}

app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "Permissions added.\n";
