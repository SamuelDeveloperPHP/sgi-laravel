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
        Schema::table('rh_beneficios', function (Blueprint $table) {
            $table->string('operadora')->nullable()->after('descricao');
            $table->decimal('valor', 10, 2)->nullable()->after('operadora');
            $table->boolean('ativo')->default(true)->after('valor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rh_beneficios', function (Blueprint $table) {
            $table->dropColumn(['operadora', 'valor', 'ativo']);
        });
    }
};
