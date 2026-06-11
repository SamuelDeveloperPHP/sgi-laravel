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
        Schema::table('fornecedor_avaliacoes', function (Blueprint $table) {
            $table->dropColumn(['nota_qualidade', 'nota_prazo', 'nota_atendimento']);
            $table->json('criterios')->nullable()->after('avaliador_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fornecedor_avaliacoes', function (Blueprint $table) {
            $table->integer('nota_qualidade')->default(5);
            $table->integer('nota_prazo')->default(5);
            $table->integer('nota_atendimento')->default(5);
            $table->dropColumn('criterios');
        });
    }
};
