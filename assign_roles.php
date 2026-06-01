<?php
App\Models\User::where('is_master_admin', true)->get()->each(function($user) { 
    $user->assignRole('Master Admin'); 
}); 
App\Models\User::where('is_master_admin', false)->get()->each(function($user) { 
    $user->assignRole('User'); 
});
echo "Roles assigned.\n";
