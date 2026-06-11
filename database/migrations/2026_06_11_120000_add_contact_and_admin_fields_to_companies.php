<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expande a tabela companies para suportar onboarding completo com
 * dados de contato (similar ao cadastro de Fornecedor) e rastreamento
 * do administrador que cadastrou a empresa.
 *
 * Campos adicionados:
 *   - cep, logradouro, numero, complemento, bairro, cidade, estado
 *     -> endereco completo
 *   - email_corporativo, telefone -> contato da empresa
 *   - nome_administrador, email_administrador -> quem cadastrou
 *     (rastreabilidade + LGPD)
 *   - observacoes -> texto livre
 *
 * Idempotente: checa hasColumn antes de cada add.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Endereco
            if (!Schema::hasColumn('companies', 'cep')) {
                $table->string('cep', 10)->nullable()->after('cnpj');
            }
            if (!Schema::hasColumn('companies', 'logradouro')) {
                $table->string('logradouro')->nullable()->after('cep');
            }
            if (!Schema::hasColumn('companies', 'numero')) {
                $table->string('numero', 20)->nullable()->after('logradouro');
            }
            if (!Schema::hasColumn('companies', 'complemento')) {
                $table->string('complemento')->nullable()->after('numero');
            }
            if (!Schema::hasColumn('companies', 'bairro')) {
                $table->string('bairro')->nullable()->after('complemento');
            }
            if (!Schema::hasColumn('companies', 'cidade')) {
                $table->string('cidade')->nullable()->after('bairro');
            }
            if (!Schema::hasColumn('companies', 'estado')) {
                $table->string('estado', 2)->nullable()->after('cidade');
            }

            // Contato corporativo
            if (!Schema::hasColumn('companies', 'email_corporativo')) {
                $table->string('email_corporativo')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('companies', 'telefone')) {
                $table->string('telefone', 20)->nullable()->after('email_corporativo');
            }

            // Dados do administrador que cadastrou (rastreabilidade)
            if (!Schema::hasColumn('companies', 'nome_administrador')) {
                $table->string('nome_administrador')->nullable()->after('telefone');
            }
            if (!Schema::hasColumn('companies', 'email_administrador')) {
                $table->string('email_administrador')->nullable()->after('nome_administrador');
            }

            // Observacoes
            if (!Schema::hasColumn('companies', 'observacoes')) {
                $table->text('observacoes')->nullable()->after('email_administrador');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = [
                'cep', 'logradouro', 'numero', 'complemento',
                'bairro', 'cidade', 'estado',
                'email_corporativo', 'telefone',
                'nome_administrador', 'email_administrador',
                'observacoes',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
