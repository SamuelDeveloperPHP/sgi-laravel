<?php
$u = App\Models\User::first(); 
$u->assignRole('Master Admin'); 
$role = Spatie\Permission\Models\Role::where('name', 'Master Admin')->first();
$role->givePermissionTo('view-projetos'); // Just in case it wasn't added
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo json_encode($u->getAllPermissions()->pluck('name'));
