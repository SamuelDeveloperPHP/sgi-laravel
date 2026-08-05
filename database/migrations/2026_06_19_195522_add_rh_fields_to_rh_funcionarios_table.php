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
            $table->foreignId('area_id')->nullable()->constrained('rh_areas')->onDelete('set null');
            $table->foreignId('cargo_id')->nullable()->constrained('rh_cargos')->onDelete('set null');
            
            $table->enum('genero', ['M', 'F', 'O', 'N'])->nullable()->comment('M=Masculino, F=Feminino, O=Outro, N=Não Informado');
            $table->date('data_demissao')->nullable();
            $table->string('motivo_demissao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rh_funcionarios', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['cargo_id']);
            $table->dropColumn(['area_id', 'cargo_id', 'genero', 'data_demissao', 'motivo_demissao']);
        });
    }
};
