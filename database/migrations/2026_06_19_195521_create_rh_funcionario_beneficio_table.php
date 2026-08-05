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
        Schema::create('rh_funcionario_beneficio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('rh_funcionarios')->onDelete('cascade');
            $table->foreignId('beneficio_id')->constrained('rh_beneficios')->onDelete('cascade');
            $table->decimal('valor', 12, 2)->default(0); // Valor fixo a receber por mês para este benefício
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['funcionario_id', 'beneficio_id'], 'rh_func_beneficio_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rh_funcionario_beneficio');
    }
};
