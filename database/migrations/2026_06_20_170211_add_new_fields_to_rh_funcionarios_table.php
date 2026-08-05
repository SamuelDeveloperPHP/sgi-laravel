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
            $table->integer('carga_horaria_mensal')->nullable()->after('salario_bruto');
            $table->string('horario_trabalho')->nullable()->after('carga_horaria_mensal');
            $table->date('data_nascimento')->nullable()->after('dependentes');
            $table->string('rg')->nullable()->after('cpf');
            $table->string('nacionalidade')->nullable()->after('rg');
            $table->string('titulo_eleitor')->nullable()->after('nacionalidade');
            $table->string('carteira_reservista')->nullable()->after('titulo_eleitor');
            $table->string('naturalidade')->nullable()->after('carteira_reservista');
            $table->string('ctps')->nullable()->after('naturalidade');
            $table->string('pis')->nullable()->after('ctps');
            $table->string('celular')->nullable()->after('telefone');
            $table->string('nome_mae')->nullable()->after('celular');
            $table->string('nome_pai')->nullable()->after('nome_mae');
            $table->string('escolaridade')->nullable()->after('nome_pai');
            $table->string('tipo_sanguineo')->nullable()->after('escolaridade');
            $table->string('banco')->nullable()->after('tipo_sanguineo');
            $table->string('agencia')->nullable()->after('banco');
            $table->string('conta_corrente')->nullable()->after('agencia');
            $table->integer('parcelas_ferias')->nullable()->after('conta_corrente');
            $table->string('data_decimo_terceiro')->nullable()->after('parcelas_ferias');
            $table->integer('parcelas_decimo_terceiro')->nullable()->after('data_decimo_terceiro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rh_funcionarios', function (Blueprint $table) {
            $table->dropColumn([
                'carga_horaria_mensal',
                'horario_trabalho',
                'data_nascimento',
                'rg',
                'nacionalidade',
                'titulo_eleitor',
                'carteira_reservista',
                'naturalidade',
                'ctps',
                'pis',
                'celular',
                'nome_mae',
                'nome_pai',
                'escolaridade',
                'tipo_sanguineo',
                'banco',
                'agencia',
                'conta_corrente',
                'parcelas_ferias',
                'data_decimo_terceiro',
                'parcelas_decimo_terceiro'
            ]);
        });
    }
};
