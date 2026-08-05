<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (! Schema::hasColumn('modules', 'default_access_policy')) {
                $table->string('default_access_policy', 30)
                    ->default('public')
                    ->after('is_visible_in_menu')
                    ->index();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_public_account')) {
                $table->boolean('is_public_account')->default(false)->after('is_active')->index();
            }
            if (! Schema::hasColumn('users', 'public_access_started_at')) {
                $table->timestamp('public_access_started_at')->nullable()->after('is_public_account');
            }
            if (! Schema::hasColumn('users', 'public_access_expires_at')) {
                $table->timestamp('public_access_expires_at')->nullable()->after('public_access_started_at')->index();
            }
            if (! Schema::hasColumn('users', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('public_access_expires_at')->index();
            }
            if (! Schema::hasColumn('users', 'blocked_reason')) {
                $table->string('blocked_reason')->nullable()->after('blocked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'blocked_reason',
                'blocked_at',
                'public_access_expires_at',
                'public_access_started_at',
                'is_public_account',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'default_access_policy')) {
                $table->dropColumn('default_access_policy');
            }
        });
    }
};
