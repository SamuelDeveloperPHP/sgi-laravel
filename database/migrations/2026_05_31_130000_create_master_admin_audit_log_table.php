<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de auditoria de ações realizadas por Master Admins.
 *
 * Como Master Admin bypassa o TenantScope (vê dados de todas as empresas),
 * é crítico registrar TODAS as operações de escrita (create/update/delete)
 * para rastreabilidade — exigência tipica de SGI/SGQ/SST/SGA conforme ISO
 * 9001, 14001, 45001.
 *
 * Populada automaticamente pelo App\Observers\MasterAdminAuditObserver,
 * registrado em AppServiceProvider para os models tenant-scoped.
 *
 * IMPORTANTE: esta tabela cresce sem limite. Implementar política de
 * retenção (ex.: cron mensal que arquiva registros > 2 anos) numa fase
 * futura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_admin_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')
                ->comment('ID do master admin que executou a ação');
            $table->unsignedBigInteger('company_id_target')->nullable()
                ->comment('Tenant do registro afetado (pode ser de outra empresa)');
            $table->string('ability', 30)
                ->comment('created | updated | deleted | restored | forceDeleted');
            // 191 (não 255) para respeitar limite InnoDB de 767/1000 bytes
            // em índices com charset utf8mb4 (4 bytes/char).
            $table->string('model_type', 191)
                ->comment('FQN do model, ex.: App\\Models\\NaoConformidade');
            $table->unsignedBigInteger('model_id');
            $table->json('changes_json')->nullable()
                ->comment('Diff: { before: {...}, after: {...}, dirty: {...} }');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
            $table->index('company_id_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_admin_audit_log');
    }
};
