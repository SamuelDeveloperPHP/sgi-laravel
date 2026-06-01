<?php
use Illuminate\Support\Facades\Schema;

Schema::dropIfExists('role_has_permissions');
Schema::dropIfExists('model_has_roles');
Schema::dropIfExists('model_has_permissions');
Schema::dropIfExists('roles');
Schema::dropIfExists('permissions');
echo "Tables dropped.\n";
