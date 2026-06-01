<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Engines e FKs após migration ===\n";
$tables = ['companies','sts_naoconforme','sts_pa','sts_auditoriainternaqualidade','sts_projetos','sts_tarefas_projeto','kanban_colunas'];
foreach ($tables as $t) {
    $info = DB::selectOne(
        "SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$t]
    );
    $fks = DB::select(
        "SELECT kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                rc.DELETE_RULE, rc.UPDATE_RULE
         FROM information_schema.KEY_COLUMN_USAGE kcu
         JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
              ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
             AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
         WHERE kcu.TABLE_SCHEMA = DATABASE() AND kcu.TABLE_NAME = ?
           AND kcu.REFERENCED_TABLE_NAME IS NOT NULL",
        [$t]
    );
    echo str_pad($t, 35) . " engine={$info->ENGINE} fks=" . count($fks);
    foreach ($fks as $fk) {
        echo " [{$fk->CONSTRAINT_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME} ON DELETE {$fk->DELETE_RULE}]";
    }
    echo "\n";
}

echo "\n=== Teste ativo: tentar DELETE em companies.id=1 (deve FALHAR pela FK) ===\n";
try {
    DB::table('companies')->where('id', 1)->delete();
    echo "FALHA: o delete FOI realizado — FK não está funcionando!\n";
    exit(1);
} catch (\Throwable $e) {
    if (str_contains($e->getMessage(), 'foreign key') || str_contains($e->getMessage(), '1451')) {
        echo "OK: FK bloqueou o delete corretamente.\n";
        echo "Erro: " . substr($e->getMessage(), 0, 250) . "\n";
    } else {
        echo "ERRO INESPERADO: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Teste ativo: INSERT com company_id=999 inexistente (deve FALHAR pela FK) ===\n";
try {
    DB::table('kanban_colunas')->insert([
        'projeto_id' => 1,
        'nome' => 'Teste FK',
        'ordem' => 0,
        'company_id' => 999,  // ID inexistente
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "FALHA: INSERT com company_id orfão foi aceito!\n";
    DB::table('kanban_colunas')->where('nome', 'Teste FK')->delete();
    exit(1);
} catch (\Throwable $e) {
    if (str_contains($e->getMessage(), 'foreign key') || str_contains($e->getMessage(), '1452')) {
        echo "OK: FK bloqueou o INSERT orfão corretamente.\n";
    } else {
        echo "ERRO INESPERADO: " . $e->getMessage() . "\n";
    }
}

echo "\n=== TUDO OK ===\n";
