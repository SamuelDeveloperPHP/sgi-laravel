<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening de produção (Fase P0) — converte tabelas QSMS e companies de
 * MyISAM para InnoDB, adiciona Foreign Keys de company_id e finaliza o
 * backfill iniciado na Fase 1 (para registros que ainda tinham NULL nas
 * tabelas já-Tenantable: sts_naoconforme, sts_pa, sts_auditoriainternaqualidade).
 *
 * Por que isso é crítico para produção:
 *  - MyISAM NÃO suporta Foreign Keys → impossível garantir referencial integrity
 *  - MyISAM NÃO tem transações ACID → rollback de operação multi-tabela é impossível
 *  - MyISAM trava tabela inteira em writes → péssimo para concorrência
 *  - Sem FK explícita: dev pode deletar empresa cuja FK seria órfão para 1000s de linhas
 *
 * Idempotente: checa engine atual e FK antes de operar; pode rodar várias vezes.
 *
 * IMPORTANTE — em produção real:
 *   - ALTER TABLE ENGINE em tabelas grandes (>1M rows) trava por horas
 *   - Use pt-online-schema-change (Percona Toolkit) ou gh-ost para zero-downtime
 *   - Faça em janela de manutenção; tenha backup recente
 */
return new class extends Migration
{
    private array $qsmsTables = [
        'sts_naoconforme',
        'sts_pa',
        'sts_auditoriainternaqualidade',
        'sts_projetos',
        'sts_tarefas_projeto',
        'kanban_colunas',
    ];

    /**
     * Tabelas onde backfill da Fase 1 não chegou (estavam Tenantable
     * desde antes mas com company_id NULL nos registros legados).
     */
    private array $tablesNeedingBackfill = [
        'sts_naoconforme',
        'sts_pa',
        'sts_auditoriainternaqualidade',
    ];

    private int $defaultCompanyId = 1;

    public function up(): void
    {
        // ============================================================
        // ETAPA 1 — Backfill remanescente (precondicao para FK)
        // ============================================================
        $this->assertCompanyExists();

        foreach ($this->tablesNeedingBackfill as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'company_id')) {
                $affected = DB::table($t)
                    ->whereNull('company_id')
                    ->update(['company_id' => $this->defaultCompanyId]);
                if ($affected > 0) {
                    echo "Backfill: {$t} atualizou {$affected} registros.\n";
                }
            }
        }

        // ============================================================
        // ETAPA 2 — Assert: zero NULLs antes de FK
        // ============================================================
        foreach ($this->qsmsTables as $t) {
            if (!Schema::hasTable($t)) continue;
            $nulls = DB::table($t)->whereNull('company_id')->count();
            if ($nulls > 0) {
                throw new \RuntimeException(
                    "ABORT: tabela {$t} ainda tem {$nulls} registros com company_id NULL. "
                  . "FK não pode ser criada. Faça backfill manual antes."
                );
            }
        }

        // ============================================================
        // ETAPA 3 — Assert: nenhum company_id orfao (referenciando empresa inexistente)
        // ============================================================
        foreach ($this->qsmsTables as $t) {
            if (!Schema::hasTable($t)) continue;
            $orphans = DB::table($t)
                ->whereNotIn('company_id', function ($q) {
                    $q->select('id')->from('companies');
                })
                ->count();
            if ($orphans > 0) {
                throw new \RuntimeException(
                    "ABORT: tabela {$t} tem {$orphans} company_id orfaos. "
                  . "FK falharia. Corrija antes."
                );
            }
        }

        // ============================================================
        // ETAPA 4 — Converter companies para InnoDB (FK target)
        // ============================================================
        if ($this->getEngine('companies') === 'MyISAM') {
            DB::statement('ALTER TABLE `companies` ENGINE=InnoDB');
            echo "companies convertida para InnoDB.\n";
        }

        // ============================================================
        // ETAPA 5 — Converter tabelas QSMS para InnoDB
        // ============================================================
        foreach ($this->qsmsTables as $t) {
            if (!Schema::hasTable($t)) continue;
            if ($this->getEngine($t) === 'MyISAM') {
                DB::statement("ALTER TABLE `{$t}` ENGINE=InnoDB");
                echo "{$t} convertida para InnoDB.\n";
            }
        }

        // ============================================================
        // ETAPA 6 — Adicionar FK company_id -> companies(id)
        // ============================================================
        foreach ($this->qsmsTables as $t) {
            if (!Schema::hasTable($t)) continue;
            $constraint = $t . '_company_id_foreign';

            // Drop existing (idempotência)
            if ($this->fkExists($t, $constraint)) {
                DB::statement("ALTER TABLE `{$t}` DROP FOREIGN KEY `{$constraint}`");
            }

            DB::statement(
                "ALTER TABLE `{$t}` "
              . "ADD CONSTRAINT `{$constraint}` "
              . "FOREIGN KEY (`company_id`) "
              . "REFERENCES `companies` (`id`) "
              . "ON DELETE RESTRICT "
              . "ON UPDATE CASCADE"
            );
            echo "FK adicionada: {$t}.company_id -> companies.id\n";
        }
    }

    public function down(): void
    {
        // Drop FKs primeiro (ordem inversa de criação)
        foreach (array_reverse($this->qsmsTables) as $t) {
            if (!Schema::hasTable($t)) continue;
            $constraint = $t . '_company_id_foreign';
            if ($this->fkExists($t, $constraint)) {
                DB::statement("ALTER TABLE `{$t}` DROP FOREIGN KEY `{$constraint}`");
            }
        }

        // Reverter engine para MyISAM
        foreach (array_reverse($this->qsmsTables) as $t) {
            if (Schema::hasTable($t)) {
                DB::statement("ALTER TABLE `{$t}` ENGINE=MyISAM");
            }
        }
        if (Schema::hasTable('companies')) {
            DB::statement('ALTER TABLE `companies` ENGINE=MyISAM');
        }

        // NOTA: backfill (company_id = 1) NAO é revertido — dado de produção
        // não deve ser apagado. Se precisar, faça manualmente.
    }

    private function assertCompanyExists(): void
    {
        $exists = DB::table('companies')->where('id', $this->defaultCompanyId)->exists();
        if (!$exists) {
            throw new \RuntimeException(
                "ABORT: empresa id={$this->defaultCompanyId} (Empresa Default) nao existe. "
              . "Crie a empresa antes de rodar esta migration. "
              . "Veja backfill_company_id.php para script de criação."
            );
        }
    }

    private function getEngine(string $table): ?string
    {
        $row = DB::selectOne(
            "SELECT ENGINE FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        );
        return $row ? $row->ENGINE : null;
    }

    private function fkExists(string $table, string $constraintName): bool
    {
        $row = DB::selectOne(
            "SELECT 1 c FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraintName]
        );
        return $row !== null;
    }
};
