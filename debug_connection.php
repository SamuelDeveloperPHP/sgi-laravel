<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Default connection: " . DB::getDefaultConnection() . "\n";
echo "Database: " . DB::connection()->getDatabaseName() . "\n";
echo "DSN: " . DB::connection()->getPdo()->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";

$exists = DB::table('companies')->where('id', 1)->exists();
echo "DB::table('companies')->where('id', 1)->exists(): " . ($exists ? 'TRUE' : 'FALSE') . "\n";

$count = DB::table('companies')->count();
echo "Total companies: {$count}\n";

$count2 = DB::table('companies')->where('id', 1)->count();
echo "Count where id=1: {$count2}\n";

$row = DB::table('companies')->where('id', 1)->first();
echo "First where id=1: " . json_encode($row) . "\n";
