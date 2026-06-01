<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
$tableNames = array_map('current', $tables);

echo json_encode($tableNames, JSON_PRETTY_PRINT);
