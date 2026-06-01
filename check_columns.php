<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\Schema;

$tables = ['sts_auditoriainternaqualidade', 'sts_pa', 'sts_naoconforme', 'users'];
$result = [];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $result[$table] = Schema::getColumnListing($table);
    } else {
        $result[$table] = 'TABLE NOT FOUND';
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
