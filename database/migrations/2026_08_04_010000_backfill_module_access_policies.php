<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasColumn('modules', 'default_access_policy')) {
            return;
        }

        DB::table('modules')
            ->whereIn('slug', ['list-projetos', 'list-companies', 'list-modules'])
            ->update(['default_access_policy' => Module::ACCESS_PRIVATE]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasColumn('modules', 'default_access_policy')) {
            return;
        }

        DB::table('modules')
            ->whereIn('slug', ['list-projetos', 'list-companies', 'list-modules'])
            ->update(['default_access_policy' => Module::ACCESS_PUBLIC]);
    }
};
