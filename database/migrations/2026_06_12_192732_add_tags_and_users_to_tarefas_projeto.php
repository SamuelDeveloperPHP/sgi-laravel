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
                $table->json('tags')->nullable()->after('descricao');
            });

            Schema::create('tarefa_projeto_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tarefa_projeto_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->foreign('tarefa_projeto_id')->references('id')->on('sts_tarefas_projeto')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarefa_projeto_user');

        if (Schema::hasTable('sts_tarefas_projeto')) {
            Schema::table('sts_tarefas_projeto', function (Blueprint $table) {
                $table->dropColumn('tags');
            });
        }
    }
};
