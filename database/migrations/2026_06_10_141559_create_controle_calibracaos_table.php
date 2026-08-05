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
        Schema::create('controle_calibracaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('equipamento');
            $table->string('local')->nullable();
            $table->string('identificacao')->nullable();
            $table->string('certificado_numero')->nullable();
            $table->integer('frequencia_meses')->nullable();
            $table->date('data_ultima_calibracao')->nullable();
            $table->date('data_proxima_calibracao')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('arquivo_certificado')->nullable(); // Upload do arquivo PDF/Imagem
            
            // Audit Logs
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_calibracaos');
    }
};
