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
        Schema::table('rh_funcionarios', function (Blueprint $table) {
            $table->string('cep', 10)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 50)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();

            $table->unique(['company_id', 'cpf'], 'rh_funcionarios_company_cpf_unique');
            $table->unique(['company_id', 'matricula'], 'rh_funcionarios_company_matricula_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rh_funcionarios', function (Blueprint $table) {
            $table->dropUnique('rh_funcionarios_company_cpf_unique');
            $table->dropUnique('rh_funcionarios_company_matricula_unique');
            
            $table->dropColumn([
                'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado'
            ]);
        });
    }
};
