<?php
/**
 * One-off cleanup: remove dados de teste que acumularam no banco dev
 * antes da conversão para InnoDB (DatabaseTransactions não fazia rollback
 * em MyISAM).
 *
 * Remove APENAS registros TEST (com email '@test.local' ou nome
 * 'TEST Company%'). Não toca em dados reais.
 *
 * Deletar este script após uso.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Cleanup de pollution de testes ===\n";
echo "ANTES — users: " . DB::table('users')->count()
   . " | companies: " . DB::table('companies')->count() . "\n";

$tablesToCleanByCompany = [
    'sts_naoconforme',
    'sts_pa',
    'sts_auditoriainternaqualidade',
    'sts_projetos',
    'sts_tarefas_projeto',
    'kanban_colunas',
];

// 1. Pegar IDs das empresas de teste
$testCompanyIds = DB::table('companies')
    ->where('nome_fantasia', 'like', 'TEST Company%')
    ->pluck('id');

echo "Test companies a remover: " . $testCompanyIds->count() . "\n";

if ($testCompanyIds->isEmpty()) {
    echo "Nada a fazer. Saindo.\n";
    exit(0);
}

DB::transaction(function () use ($testCompanyIds, $tablesToCleanByCompany) {
    // Desabilitar FK temporariamente (cuidadoso — só nesta sessão)
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        // 2. Apagar QSMS rows que referenciam empresas de teste
        foreach ($tablesToCleanByCompany as $t) {
            $count = DB::table($t)->whereIn('company_id', $testCompanyIds)->delete();
            echo "  {$t}: removidos {$count} registros\n";
        }

        // 3. Apagar pivot projeto_user / company_user (se houver vinculo)
        $pivotProjUser = DB::table('projeto_user')->whereIn('projeto_id', function ($q) use ($testCompanyIds) {
            $q->select('id')->from('sts_projetos')->whereIn('company_id', $testCompanyIds);
        })->delete();
        echo "  projeto_user: {$pivotProjUser}\n";

        // 4. Apagar users de teste
        $testUsersDeleted = DB::table('users')
            ->where('email', 'like', '%@test.local')
            ->delete();
        echo "  users @test.local: {$testUsersDeleted}\n";

        // 5. Apagar company_user vinculados às empresas de teste
        $companyUserDeleted = DB::table('company_user')
            ->whereIn('company_id', $testCompanyIds)
            ->delete();
        echo "  company_user: {$companyUserDeleted}\n";

        // 6. Apagar empresas de teste
        $companiesDeleted = DB::table('companies')
            ->whereIn('id', $testCompanyIds)
            ->delete();
        echo "  companies TEST: {$companiesDeleted}\n";

    } finally {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
});

echo "\nDEPOIS — users: " . DB::table('users')->count()
   . " | companies: " . DB::table('companies')->count() . "\n";

echo "\n=== Cleanup concluído ===\n";
