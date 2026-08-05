<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('processo_seletivo_id')->constrained('processos_seletivos')->cascadeOnDelete();
            
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->integer('idade')->nullable();
            $table->string('endereco')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade_estado')->nullable();
            
            $table->string('nivel_ensino')->nullable();
            $table->string('faculdade')->nullable();
            
            $table->integer('experiencia_anos')->nullable();
            $table->string('ultima_empresa')->nullable();
            $table->string('cargo')->nullable();
            $table->integer('tempo_ultimo_emprego')->nullable();
            
            $table->text('avaliacao_geral')->nullable();
            $table->text('referencias')->nullable();
            
            $table->enum('etapa_atual', [
                'Triagem de Currículo', 
                'Teste Prático', 
                'Dinâmica de Grupo', 
                'Entrevista Inicial', 
                'Entrevista com Gerentes', 
                'Entrevista Final', 
                'Aprovado', 
                'Reprovado'
            ])->default('Triagem de Currículo');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};
