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
        Schema::create('kanban_colunas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('sts_projetos')->onDelete('cascade');
            $table->string('nome');
            $table->integer('ordem')->default(0);
            $table->string('cor')->nullable(); // Para customização de cores da coluna
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_colunas');
    }
};
