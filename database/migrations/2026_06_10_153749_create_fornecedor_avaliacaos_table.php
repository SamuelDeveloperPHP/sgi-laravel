<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedor_avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->onDelete('cascade');
            
            $table->date('data_avaliacao');
            $table->foreignId('avaliador_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Notas de 1 a 5
            $table->integer('nota_qualidade')->default(5);
            $table->integer('nota_prazo')->default(5);
            $table->integer('nota_atendimento')->default(5);
            
            // Média ou Nota final daquela avaliação
            $table->decimal('nota_geral', 5, 2)->default(5);
            
            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedor_avaliacoes');
    }
};
