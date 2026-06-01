<?php
$users = App\Models\User::all();
foreach($users as $u) {
    $u->assignRole('Master Admin');
}
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "All users are now Master Admin.\n";
