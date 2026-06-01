<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria de segurança multiempresa (Fase 1) — adiciona company_id,
 * obra_id e campos de auditoria/soft-delete nas tabelas de Projetos,
 * Tarefas e Kanban, que estavam expostas a vazamento entre empresas
 * por NÃO possuírem coluna de tenant e portanto não poderem usar a
 * trait App\Traits\Tenantable.
 *
 * Idempotente: checa hasColumn antes de adicionar cada campo. Reversível.
 *
 * IMPORTANTE — backfill:
 *   Registros existentes ficarão com company_id = NULL. Após aplicar a
 *   trait Tenantable nos models, esses registros tornam-se invisíveis
 *   para usuários não-master (TenantScope filtra por company_id = X,
 *   e NULL nunca casa com X). Esse é o comportamento seguro por padrão.
 *   Para tornar dados legados visíveis, o Master Admin deve atribuir
 *   company_id manualmente, p.ex.:
 *     DB::table('sts_projetos')
 *         ->whereNull('company_id')
 *         ->update(['company_id' => <id_da_empresa_legada>]);
 *   Esse passo NÃO está nesta migration por ser decisão de negócio.
 */
return new class extends Migration
{
    private array $tables = [
        'sts_projetos',
        'sts_tarefas_projeto',
        'kanban_colunas',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'obra_id')) {
                    $table->unsignedBigInteger('obra_id')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'user_create')) {
                    $table->unsignedBigInteger('user_create')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'user_edit')) {
                    $table->unsignedBigInteger('user_edit')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'company_id')) {
                    $table->dropIndex([$tableName . '_company_id_index']);
                    $table->dropColumn('company_id');
                }
                if (Schema::hasColumn($tableName, 'obra_id')) {
                    $table->dropIndex([$tableName . '_obra_id_index']);
                    $table->dropColumn('obra_id');
                }
                if (Schema::hasColumn($tableName, 'user_create')) {
                    $table->dropIndex([$tableName . '_user_create_index']);
                    $table->dropColumn('user_create');
                }
                if (Schema::hasColumn($tableName, 'user_edit')) {
                    $table->dropColumn('user_edit');
                }
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
