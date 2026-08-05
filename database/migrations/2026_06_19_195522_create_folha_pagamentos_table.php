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
        Schema::create('rh_folha_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('competencia', 7); // ex: 2026-06
            $table->foreignId('funcionario_id')->constrained('rh_funcionarios')->onDelete('cascade');
            
            $table->decimal('salario_base', 12, 2)->default(0);
            $table->decimal('total_proventos', 12, 2)->default(0); // salario + ferias + bonus
            $table->decimal('total_descontos', 12, 2)->default(0); // faltas + inss + irrf
            $table->decimal('total_beneficios', 12, 2)->default(0); // soma de todos os beneficios pagos (VT, VR)
            $table->decimal('salario_liquido', 12, 2)->default(0); // proventos - descontos
            $table->decimal('custo_total', 12, 2)->default(0); // proventos + beneficios + impostos (opcional)
            
            $table->json('detalhes')->nullable(); // JSON com detalhes do que compôs os valores
            $table->string('status')->default('Fechado');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['funcionario_id', 'competencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rh_folha_pagamentos');
    }
};
