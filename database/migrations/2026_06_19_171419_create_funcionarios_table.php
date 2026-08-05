<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_funcionarios', function (Blueprint $table) {
            $table->id();
            
            // Controle de tenant (empresas)
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Dados funcionais
            $table->string('nome');
            $table->string('matricula')->nullable();
            $table->date('data_admissao')->nullable();
            
            // Dados pessoais
            $table->integer('dependentes')->default(0);
            $table->string('estado_civil')->nullable();
            
            // Contato e Financeiro
            $table->decimal('salario_bruto', 12, 2)->nullable();
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();
            
            // Outros
            $table->text('observacoes')->nullable();
            $table->string('status')->default('Ativo'); // Ativo, Desligado, Férias, Afastado
            
            // Controle de auditoria
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_funcionarios');
    }
};
