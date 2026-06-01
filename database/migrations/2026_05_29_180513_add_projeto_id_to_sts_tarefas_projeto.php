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
        Schema::table('sts_tarefas_projeto', function (Blueprint $table) {
            $table->unsignedBigInteger('projeto_id')->nullable()->after('id');
            // Can't add standard foreign key because sts_projetos might not be InnoDB or have unsigned big integer id in legacy
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sts_tarefas_projeto', function (Blueprint $table) {
            $table->dropColumn('projeto_id');
        });
    }
};
