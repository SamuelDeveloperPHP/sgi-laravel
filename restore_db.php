<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sql = file_get_contents('c:\wamp64\www\adm\meuSGI.sql');
Illuminate\Support\Facades\DB::unprepared($sql);
echo "Database restored successfully.";
