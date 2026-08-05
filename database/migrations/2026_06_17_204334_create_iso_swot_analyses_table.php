<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('iso_swot_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('titulo');
            $table->date('data_analise');
            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->text('motivo_rejeicao')->nullable();
            
            $table->string('objetivo_estrategico')->nullable();
            
            // JSON fields for the 4 quadrants

            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('opportunities')->nullable();
            $table->json('threats')->nullable();
            
            $table->json('cruzamentos')->nullable();
            $table->json('planos_acao')->nullable();
            
            $table->text('conclusao')->nullable();
            
            $table->foreignId('user_create')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_edit')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Cadastra o módulo na tabela de módulos para o menu dinâmico
        $moduleId = DB::table('modules')->insertGetId([
            'parent_id' => null,
            'name' => 'Análise SWOT',
            'slug' => 'list-analise-swot',
            'route_name' => 'analise-swot.index',
            'icon' => 'Grid',
            'is_active' => true,
            'is_visible_in_menu' => true,
            'order' => 56, // Logo após o Mapa de Risco
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cadastra as permissões Spatie correspondentes ao módulo
        $actionVerbs = ['view', 'list', 'create', 'edit', 'delete', 'manage'];
        foreach ($actionVerbs as $verb) {
            DB::table('permissions')->insertOrIgnore([
                'name' => "{$verb}-analise-swot",
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove módulo e permissões
        DB::table('modules')->where('slug', 'list-analise-swot')->delete();
        DB::table('permissions')->where('name', 'like', '%-analise-swot')->delete();
        
        Schema::dropIfExists('iso_swot_analyses');
    }
};
