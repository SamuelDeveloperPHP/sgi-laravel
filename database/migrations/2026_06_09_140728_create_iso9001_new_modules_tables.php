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
        Schema::create('sts_nossa_historia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->longText('conteudo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sts_missao_visao_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->longText('conteudo')->nullable();
            $table->enum('status', ['rascunho', 'aguardando_revisao', 'aguardando_aprovacao', 'aprovada', 'devolvida'])->default('rascunho');
            
            $table->foreignId('elaborador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_elaboracao')->nullable();
            
            $table->foreignId('revisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_revisao')->nullable();
            
            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_aprovacao')->nullable();
            
            $table->string('hash_assinatura')->nullable();
            
            $table->foreignId('user_create')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_edit')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sts_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('codigo')->nullable();
            $table->string('identificacao');
            $table->string('area')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('revisao_atual')->nullable();
            $table->string('ano_ultima_revisao')->nullable();
            $table->string('meio')->nullable();
            $table->string('local_arquivo')->nullable();
            $table->string('indexacao')->nullable();
            $table->string('protecao')->nullable();
            $table->string('tempo_arquivamento')->nullable();
            $table->string('destino_apos_prazo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sts_documento_revisoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('sts_documentos')->onDelete('cascade');
            $table->string('revisao')->nullable();
            $table->date('data_revisao')->nullable();
            $table->text('alteracoes')->nullable();
            
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sts_documento_revisoes');
        Schema::dropIfExists('sts_documentos');
        Schema::dropIfExists('sts_missao_visao_valores');
        Schema::dropIfExists('sts_nossa_historia');
    }
};
