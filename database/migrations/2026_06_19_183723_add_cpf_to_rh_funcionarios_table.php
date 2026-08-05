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
            $table->string('cpf', 14)->nullable()->after('matricula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rh_funcionarios', function (Blueprint $table) {
            $table->dropColumn('cpf');
        });
    }
};
