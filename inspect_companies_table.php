<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$info = DB::selectOne(
    "SELECT ENGINE, TABLE_COLLATION
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies'"
);
echo "companies table: " . json_encode($info) . "\n";

$idCol = DB::selectOne(
    "SELECT COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'id'"
);
echo "companies.id column: " . json_encode($idCol) . "\n";

// Now check company_id column on each QSMS table
$tables = ['sts_naoconforme','sts_pa','sts_auditoriainternaqualidade','sts_projetos','sts_tarefas_projeto','kanban_colunas'];
foreach ($tables as $t) {
    $col = DB::selectOne(
        "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'company_id'",
        [$t]
    );
    echo str_pad($t, 35) . " company_id: " . json_encode($col) . "\n";
}
