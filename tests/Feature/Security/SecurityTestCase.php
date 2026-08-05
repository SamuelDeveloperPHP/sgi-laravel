<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Base class para testes de segurança que precisam rodar contra o banco
 * MariaDB REAL (não SQLite :memory: do phpunit.xml).
 *
 * Por quê: as tabelas legadas sts_naoconforme, sts_pa, sts_projetos,
 * sts_tarefas_projeto, sts_auditoriainternaqualidade só existem como
 * dump SQL — não há Schema::create() migration que recrie em SQLite
 * fresh. Migração completa para Schema::create() é trabalho separado.
 *
 * DatabaseTransactions envolve cada teste em transação que ROLLBACK
 * no tearDown — nenhum dado de teste polui o banco.
 *
 * EXECUTAR:
 *   php artisan test --testsuite=Security
 * ou
 *   php artisan test tests/Feature/Security/
 */
abstract class SecurityTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * Lista de conexões que o trait DatabaseTransactions deve cobrir.
     */
    protected $connectionsToTransact = ['mysql'];

    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        // Override config dynamically to run against the real MariaDB/MySQL database.
        // This avoids global $_ENV/putenv pollution, so other tests still use SQLite.
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => env('DB_DATABASE_REAL') ?: 'meusgi']);
    }
}
