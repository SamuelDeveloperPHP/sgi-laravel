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
        Schema::create('sts_escopo_sgi', function (Blueprint $table) {
            $table->id();
            $table->longText('conteudo')->nullable();
            $table->enum('status', ['rascunho', 'aguardando_revisao', 'aguardando_aprovacao', 'aprovada', 'devolvida'])->default('rascunho');
            
            $table->unsignedBigInteger('elaborador_id')->nullable();
            $table->timestamp('data_elaboracao')->nullable();
            
            $table->unsignedBigInteger('revisor_id')->nullable();
            $table->timestamp('data_revisao')->nullable();
            
            $table->unsignedBigInteger('aprovador_id')->nullable();
            $table->timestamp('data_aprovacao')->nullable();
            
            $table->string('hash_assinatura')->nullable();

            // Relações Multi-empresa e Auditoria (Tenantable)
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedBigInteger('user_create')->nullable();
            $table->unsignedBigInteger('user_edit')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('elaborador_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('revisor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('aprovador_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('user_create')->references('id')->on('users')->nullOnDelete();
            $table->foreign('user_edit')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_escopo');
    }
};
