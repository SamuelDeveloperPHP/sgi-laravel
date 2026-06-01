<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sts_projetos', function (Blueprint $table) {
            $table->unsignedBigInteger('responsavel_id')->nullable()->after('adms_usuario_id');
            $table->string('privacidade')->default('Private')->after('responsavel_id');
            $table->json('tags')->nullable()->after('privacidade');

            $table->foreign('responsavel_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sts_projetos', function (Blueprint $table) {
            $table->dropForeign(['responsavel_id']);
            $table->dropColumn(['responsavel_id', 'privacidade', 'tags']);
        });
    }
};
