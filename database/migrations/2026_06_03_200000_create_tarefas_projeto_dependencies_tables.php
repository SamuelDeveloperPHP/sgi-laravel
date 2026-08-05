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
            // 1. Tabela de Comentários
            Schema::create('sts_tarefas_comentarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tarefa_projeto_id');
                $table->unsignedBigInteger('user_id');
                $table->text('mensagem');
                $table->timestamps();

                // Tenantable / Audit
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('user_create_id')->nullable();
                $table->unsignedBigInteger('user_edit_id')->nullable();
                
                $table->foreign('tarefa_projeto_id')->references('id')->on('sts_tarefas_projeto')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            // 2. Tabela de Anexos
            Schema::create('sts_tarefas_anexos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tarefa_projeto_id');
                $table->unsignedBigInteger('user_id');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('file_type')->nullable();
                $table->timestamps();

                // Tenantable / Audit
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('user_create_id')->nullable();
                $table->unsignedBigInteger('user_edit_id')->nullable();
                
                $table->foreign('tarefa_projeto_id')->references('id')->on('sts_tarefas_projeto')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            // 3. Tabela de Checklists
            Schema::create('sts_tarefas_checklists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tarefa_projeto_id');
                $table->string('descricao');
                $table->boolean('concluido')->default(false);
                $table->integer('ordem')->default(0);
                $table->timestamps();

                // Tenantable / Audit
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('user_create_id')->nullable();
                $table->unsignedBigInteger('user_edit_id')->nullable();
                
                $table->foreign('tarefa_projeto_id')->references('id')->on('sts_tarefas_projeto')->onDelete('cascade');
            });

            // 4. Adiciona campo de repetir na tarefa
            Schema::table('sts_tarefas_projeto', function (Blueprint $table) {
                if (!Schema::hasColumn('sts_tarefas_projeto', 'repetir')) {
                    $table->string('repetir')->nullable()->after('dt_fim');
                }
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
                if (Schema::hasColumn('sts_tarefas_projeto', 'repetir')) {
                    $table->dropColumn('repetir');
                }
            });
        }

        Schema::dropIfExists('sts_tarefas_checklists');
        Schema::dropIfExists('sts_tarefas_anexos');
        Schema::dropIfExists('sts_tarefas_comentarios');
    }
};
