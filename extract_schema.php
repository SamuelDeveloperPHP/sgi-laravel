<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = ['sts_naoconforme', 'sts_pa', 'sts_auditoriainternaqualidade'];
foreach($tables as $t) {
    echo "\nTable: $t\n";
    print_r(Illuminate\Support\Facades\Schema::getColumnListing($t));
}
