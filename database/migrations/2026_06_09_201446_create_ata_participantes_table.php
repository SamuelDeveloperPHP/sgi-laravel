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
        Schema::create('sts_ata_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ata_id')->constrained('sts_atas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->boolean('assinado')->default(false);
            $table->timestamp('data_assinatura')->nullable();
            $table->string('hash_assinatura', 255)->nullable();
            
            $table->timestamps();

            // Evitar duplicidade do mesmo usuário na mesma ata
            $table->unique(['ata_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_ata_participantes');
    }
};
