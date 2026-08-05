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
        if (Schema::hasTable('sts_projetos')) {
            Schema::table('sts_projetos', function (Blueprint $table) {
                $table->string('imagem_capa')->nullable()->after('tags');
                $table->json('arquivos_anexos')->nullable()->after('imagem_capa');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sts_projetos')) {
            Schema::table('sts_projetos', function (Blueprint $table) {
                $table->dropColumn(['imagem_capa', 'arquivos_anexos']);
            });
        }
    }
};
