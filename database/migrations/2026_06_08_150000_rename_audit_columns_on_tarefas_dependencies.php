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
        if (Schema::hasTable('sts_tarefas_comentarios')) {
            Schema::table('sts_tarefas_comentarios', function (Blueprint $table) {
                $table->renameColumn('user_create_id', 'user_create');
                $table->renameColumn('user_edit_id', 'user_edit');
            });
        }

        if (Schema::hasTable('sts_tarefas_anexos')) {
            Schema::table('sts_tarefas_anexos', function (Blueprint $table) {
                $table->renameColumn('user_create_id', 'user_create');
                $table->renameColumn('user_edit_id', 'user_edit');
            });
        }

        if (Schema::hasTable('sts_tarefas_checklists')) {
            Schema::table('sts_tarefas_checklists', function (Blueprint $table) {
                $table->renameColumn('user_create_id', 'user_create');
                $table->renameColumn('user_edit_id', 'user_edit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sts_tarefas_checklists')) {
            Schema::table('sts_tarefas_checklists', function (Blueprint $table) {
                $table->renameColumn('user_create', 'user_create_id');
                $table->renameColumn('user_edit', 'user_edit_id');
            });
        }

        if (Schema::hasTable('sts_tarefas_anexos')) {
            Schema::table('sts_tarefas_anexos', function (Blueprint $table) {
                $table->renameColumn('user_create', 'user_create_id');
                $table->renameColumn('user_edit', 'user_edit_id');
            });
        }

        if (Schema::hasTable('sts_tarefas_comentarios')) {
            Schema::table('sts_tarefas_comentarios', function (Blueprint $table) {
                $table->renameColumn('user_create', 'user_create_id');
                $table->renameColumn('user_edit', 'user_edit_id');
            });
        }
    }
};
