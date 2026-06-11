<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('sts_companies')->onDelete('cascade');
            
            // Dados Básicos
            $table->string('razao_social');
            $table->string('cnpj_cpf')->nullable();
            $table->string('categoria')->nullable();
            $table->enum('criticidade', ['baixa', 'media', 'alta'])->default('media');
            
            // Status
            $table->enum('status_homologacao', ['pendente', 'aprovado', 'reprovado', 'inativo'])->default('pendente');
            $table->decimal('idf_atual', 5, 2)->default(0);
            
            // Contato
            $table->string('contato_nome')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            
            // Endereço
            $table->string('cep')->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            
            $table->text('observacoes')->nullable();
            
            // Audit Logs
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
