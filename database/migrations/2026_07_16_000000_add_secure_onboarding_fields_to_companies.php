<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('dominio_corporativo')->nullable()->after('telefone');
            $table->string('email_recuperacao_secundario')->nullable()->after('email_administrador');
            $table->timestamp('cnpj_verificado_em')->nullable()->after('cnpj');
            $table->index('dominio_corporativo');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['dominio_corporativo']);
            $table->dropColumn([
                'dominio_corporativo',
                'email_recuperacao_secundario',
                'cnpj_verificado_em',
            ]);
        });
    }
};
