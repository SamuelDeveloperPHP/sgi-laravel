<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_ferias', function (Blueprint $table) {
            $table->id();
            
            // Controle de tenant (empresas)
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Vínculo com o funcionário
            $table->foreignId('funcionario_id')->constrained('rh_funcionarios')->onDelete('cascade');
            
            // Período aquisitivo (Direito as Férias)
            $table->date('periodo_aquisitivo_inicio')->nullable();
            $table->date('periodo_aquisitivo_fim')->nullable();
            $table->integer('dias_direito')->default(30);
            
            // Abono Pecuniário (Venda de férias)
            $table->boolean('opcao_abono')->default(false);
            $table->integer('dias_abono')->default(0);
            
            // Períodos de Gozo (Até 3)
            $table->date('gozo_1_inicio')->nullable();
            $table->date('gozo_1_fim')->nullable();
            $table->date('gozo_2_inicio')->nullable();
            $table->date('gozo_2_fim')->nullable();
            $table->date('gozo_3_inicio')->nullable();
            $table->date('gozo_3_fim')->nullable();
            
            // Faltas não justificadas no período (podem descontar dias)
            $table->integer('faltas')->default(0);

            // Valores Calculados / Lançados Manualmente (conforme solicitado pelo usuário)
            $table->decimal('valor_proventos', 12, 2)->nullable();
            $table->decimal('valor_1_3', 12, 2)->nullable();
            $table->decimal('valor_1_3_abono', 12, 2)->nullable();
            $table->decimal('desconto_inss', 12, 2)->nullable();
            $table->decimal('desconto_irpf', 12, 2)->nullable();
            $table->decimal('valor_liquido', 12, 2)->nullable();
            
            // Status do registro
            $table->string('status')->default('Programada'); // Programada, Em Gozo, Concluída, Cancelada
            
            // Controle de auditoria
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_ferias');
    }
};
