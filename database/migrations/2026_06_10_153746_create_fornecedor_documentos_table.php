<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedor_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->onDelete('cascade');
            
            $table->string('nome_documento');
            $table->string('arquivo'); // path do arquivo (pdf, png, jpg)
            $table->date('data_validade')->nullable(); // se o documento tem vencimento
            
            // Workflow de aprovação
            $table->enum('status_aprovacao', ['pendente', 'aprovado', 'reprovado'])->default('pendente');
            $table->text('motivo_reprovacao')->nullable();
            
            // Quem avaliou
            $table->foreignId('avaliado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('avaliado_em')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedor_documentos');
    }
};
