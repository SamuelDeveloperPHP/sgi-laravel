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
        Schema::create('sts_mapas_risco', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('titulo');
            $table->string('setor');
            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->text('motivo_rejeicao')->nullable();
            $table->date('data_mapeamento');
            $table->json('pontos_risco')->nullable();
            $table->foreignId('user_create')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_edit')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Cadastra o módulo na tabela de módulos para o menu dinâmico
        $moduleId = DB::table('modules')->insertGetId([
            'parent_id' => null,
            'name' => 'Mapa de Risco',
            'slug' => 'list-mapas-risco',
            'route_name' => 'mapas-risco.index',
            'icon' => 'AlertTriangle',
            'is_active' => true,
            'is_visible_in_menu' => true,
            'order' => 55,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cadastra as permissões Spatie correspondentes ao módulo
        $actionVerbs = ['view', 'list', 'create', 'edit', 'delete', 'manage'];
        foreach ($actionVerbs as $verb) {
            DB::table('permissions')->insertOrIgnore([
                'name' => "{$verb}-mapas-risco",
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
        DB::table('modules')->where('slug', 'list-mapas-risco')->delete();
        DB::table('permissions')->where('name', 'like', '%-mapas-risco')->delete();
        
        Schema::dropIfExists('sts_mapas_risco');
    }
};
