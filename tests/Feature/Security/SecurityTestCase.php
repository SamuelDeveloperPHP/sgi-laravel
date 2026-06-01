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

    /**
     * Configura o app para usar MariaDB ANTES do boot, em vez do SQLite
     * que phpunit.xml força para os testes default.
     *
     * IMPORTANTE — chamado por TestCase::createApplication() ao boot.
     */
    protected function refreshApplication(): void
    {
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_DATABASE'] = $_ENV['DB_DATABASE_REAL'] ?? 'meusgi';
        putenv('DB_CONNECTION=mysql');
        putenv('DB_DATABASE=' . ($_ENV['DB_DATABASE_REAL'] ?? 'meusgi'));

        parent::refreshApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();
        // NÃO fazer DB::purge() aqui — destrói a transaction que
        // DatabaseTransactions trait iniciou em setUpTraits durante
        // parent::setUp(). Pollution permanece se isso acontecer.
    }
}
