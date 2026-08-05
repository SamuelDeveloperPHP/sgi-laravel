<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                // No futuro, adicionar a foreign key para a tabela companies.
                $table->index('company_id');
            }
            if (!Schema::hasColumn('users', 'is_master_admin')) {
                $table->boolean('is_master_admin')->default(false)->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('users', 'is_master_admin')) {
                $table->dropColumn('is_master_admin');
            }
        });
    }
};
