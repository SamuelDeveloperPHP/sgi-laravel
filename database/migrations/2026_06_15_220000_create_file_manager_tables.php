<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Empresas com acesso ao gerenciador de arquivos (liberado pelo master_admin)
        Schema::create('fm_empresa_acesso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('habilitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('company_id');
        });

        // Pastas (árvore hierárquica por empresa)
        Schema::create('fm_pastas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('fm_pastas')->nullOnDelete();
            $table->string('nome');
            $table->boolean('is_root')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // Arquivos (com soft delete para lixeira de 30 dias)
        Schema::create('fm_arquivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('pasta_id')->nullable()->constrained('fm_pastas')->nullOnDelete();
            $table->string('nome_original');
            $table->string('nome_disco')->unique(); // UUID no storage
            $table->string('tipo_mime')->nullable();
            $table->bigInteger('tamanho')->default(0); // bytes
            $table->string('caminho');
            $table->boolean('is_starred')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // Grupos de acesso
        Schema::create('fm_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('nome');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Membros de grupos com permissões de gerência
        Schema::create('fm_grupo_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('fm_grupos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('pode_adicionar_membros')->default(false);
            $table->boolean('pode_remover_membros')->default(false);
            $table->timestamps();
            $table->unique(['grupo_id', 'user_id']);
        });

        // Permissões de pasta por grupo
        Schema::create('fm_pasta_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasta_id')->constrained('fm_pastas')->onDelete('cascade');
            $table->foreignId('grupo_id')->constrained('fm_grupos')->onDelete('cascade');
            $table->boolean('pode_visualizar')->default(true);
            $table->boolean('pode_incluir')->default(false);
            $table->boolean('pode_excluir')->default(false);
            $table->timestamps();
            $table->unique(['pasta_id', 'grupo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fm_pasta_grupos');
        Schema::dropIfExists('fm_grupo_users');
        Schema::dropIfExists('fm_grupos');
        Schema::dropIfExists('fm_arquivos');
        Schema::dropIfExists('fm_pastas');
        Schema::dropIfExists('fm_empresa_acesso');
    }
};
