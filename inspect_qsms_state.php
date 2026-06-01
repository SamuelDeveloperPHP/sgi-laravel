<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'sts_naoconforme',
    'sts_pa',
    'sts_auditoriainternaqualidade',
    'sts_projetos',
    'sts_tarefas_projeto',
    'kanban_colunas',
];

$out = [];
foreach ($tables as $t) {
    $info = DB::selectOne(
        'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$t]
    );
    if (!$info) {
        $out[$t] = ['MISSING'];
        continue;
    }
    $nulls = DB::table($t)->whereNull('company_id')->count();
    $total = DB::table($t)->count();
    $hasFk = DB::selectOne(
        "SELECT COUNT(*) c FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
           AND COLUMN_NAME = 'company_id' AND REFERENCED_TABLE_NAME = 'companies'",
        [$t]
    )->c > 0;

    $out[$t] = [
        'engine'           => $info->ENGINE,
        'collation'        => $info->TABLE_COLLATION,
        'total'            => $total,
        'null_company_id'  => $nulls,
        'has_fk_company'   => $hasFk,
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
