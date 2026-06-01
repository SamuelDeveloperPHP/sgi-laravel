<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'sts_auditoriainternaqualidade',
        'sts_pa',
        'sts_naoconforme'
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'company_id')) {
                        $table->unsignedBigInteger('company_id')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'obra_id')) {
                        $table->unsignedBigInteger('obra_id')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'user_create')) {
                        $table->unsignedBigInteger('user_create')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'user_edit')) {
                        $table->unsignedBigInteger('user_edit')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->softDeletes();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'company_id')) {
                        $table->dropIndex([$tableName . '_company_id_index']);
                        $table->dropColumn('company_id');
                    }
                    if (Schema::hasColumn($tableName, 'obra_id')) {
                        $table->dropIndex([$tableName . '_obra_id_index']);
                        $table->dropColumn('obra_id');
                    }
                    if (Schema::hasColumn($tableName, 'user_create')) {
                        $table->dropIndex([$tableName . '_user_create_index']);
                        $table->dropColumn('user_create');
                    }
                    if (Schema::hasColumn($tableName, 'user_edit')) {
                        $table->dropColumn('user_edit');
                    }
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropSoftDeletes();
                    }
                });
            }
        }
    }
};
