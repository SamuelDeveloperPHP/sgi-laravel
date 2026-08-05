<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sts_tarefas_projeto')) {
            Schema::table('sts_tarefas_projeto', function (Blueprint $table) {
                $table->foreignId('kanban_coluna_id')->nullable()->after('projeto_id')
                      ->constrained('kanban_colunas')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sts_tarefas_projeto')) {
            Schema::table('sts_tarefas_projeto', function (Blueprint $table) {
                $table->dropForeign(['kanban_coluna_id']);
                $table->dropColumn('kanban_coluna_id');
            });
        }
    }
};
