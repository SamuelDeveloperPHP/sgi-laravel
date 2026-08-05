<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processos_seletivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->enum('status', ['Em Andamento', 'Concluído', 'Cancelado'])->default('Em Andamento');
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->decimal('custo_planejado', 10, 2)->default(0);
            $table->decimal('custo_realizado', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos_seletivos');
    }
};
