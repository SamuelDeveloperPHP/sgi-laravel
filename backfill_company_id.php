<?php
/**
 * Backfill one-off script (decisão de negócio: atribuir Empresa ID = 1
 * aos registros legados em sts_projetos, sts_tarefas_projeto, kanban_colunas
 * que ficaram com company_id = NULL após a migration da Fase 1).
 *
 * Executar UMA VEZ:  php backfill_company_id.php
 * Idempotente: só atualiza onde company_id IS NULL.
 *
 * Pode ser removido após execução bem-sucedida.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['sts_projetos', 'sts_tarefas_projeto', 'kanban_colunas'];

// Se ID=1 existir, usa. Senão, cria/recupera uma "Empresa Default" e usa o ID dela.
$defaultCompanyId = 1;
$company1 = DB::table('companies')->where('id', 1)->first();
if (!$company1) {
    echo "Empresa id=1 não existe — criando 'Empresa Default' para receber dados legados...\n";
    $defaultCompanyId = DB::table('companies')->insertGetId([
        'nome_fantasia' => 'Empresa Default',
        'razao_social'  => 'Empresa Default (auto-criada para backfill da Fase 1)',
        'cnpj'          => null,
        'status'        => 1,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
    echo "Empresa Default criada com id={$defaultCompanyId}.\n";
}

echo "=== Backfill company_id = {$defaultCompanyId} ===\n";

$total = 0;
foreach ($tables as $table) {
    $beforeNull = DB::table($table)->whereNull('company_id')->count();
    $totalRows = DB::table($table)->count();
    $affected = DB::table($table)
        ->whereNull('company_id')
        ->update(['company_id' => $defaultCompanyId]);
    $afterNull = DB::table($table)->whereNull('company_id')->count();

    echo sprintf(
        "%-25s total=%d  null antes=%d  atualizados=%d  null depois=%d\n",
        $table,
        $totalRows,
        $beforeNull,
        $affected,
        $afterNull
    );
    $total += $affected;
}

echo "=== Concluído. Total de registros atualizados: {$total} ===\n";
