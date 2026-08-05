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
        Schema::create('sts_atas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_termino');
            $table->string('local', 255);
            $table->string('assunto', 255);
            $table->longText('pautas');
            $table->longText('registro')->nullable();
            
            $table->foreignId('responsavel_id')->constrained('users')->cascadeOnDelete(); // Quem redigiu
            
            $table->enum('status', ['rascunho', 'aguardando_assinaturas', 'concluida'])->default('rascunho');
            
            $table->foreignId('user_edit')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_atas');
    }
};
