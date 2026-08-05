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
        Schema::create('sts_politica_qualidade', function (Blueprint $table) {
            $table->id();
            $table->longText('conteudo')->nullable();
            $table->string('status', 50)->default('rascunho'); // rascunho, aguardando_revisao, aguardando_aprovacao, aprovada, devolvida
            
            // Auditoria
            $table->foreignId('elaborador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_elaboracao')->nullable();
            
            $table->foreignId('revisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_revisao')->nullable();
            
            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_aprovacao')->nullable();
            
            $table->string('hash_assinatura', 255)->nullable();
            
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            
            $table->foreignId('user_create')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_edit')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_politica_qualidade');
    }
};
