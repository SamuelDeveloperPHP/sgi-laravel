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
        Schema::table('sts_naoconforme', function (Blueprint $table) {
            $table->json('dados_origem')->nullable();
            $table->json('acao_contencao_grid')->nullable();
            $table->json('cinco_porques')->nullable();
            $table->json('plano_acao_grid')->nullable();
            $table->json('evidencias')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sts_naoconforme', function (Blueprint $table) {
            $table->dropColumn([
                'dados_origem',
                'acao_contencao_grid',
                'cinco_porques',
                'plano_acao_grid',
                'evidencias'
            ]);
        });
    }
};
