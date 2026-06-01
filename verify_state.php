<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Companies ===\n";
$cs = DB::table('companies')->select('id', 'nome_fantasia', 'deleted_at')->get();
foreach ($cs as $c) {
    echo "  id={$c->id} nome={$c->nome_fantasia} deleted_at={$c->deleted_at}\n";
}

echo "\n=== Companies count (com e sem soft-delete) ===\n";
echo "  total (sem filtro): " . DB::table('companies')->count() . "\n";
echo "  ativas (deleted_at IS NULL): " . DB::table('companies')->whereNull('deleted_at')->count() . "\n";

echo "\n=== ID=1 com whereNull deleted_at ===\n";
$row = DB::table('companies')->where('id', 1)->whereNull('deleted_at')->first();
echo "  encontrado: " . ($row ? 'SIM' : 'NAO') . "\n";

echo "\n=== ID=1 sem filtro de soft delete ===\n";
$row2 = DB::table('companies')->where('id', 1)->first();
echo "  encontrado: " . ($row2 ? 'SIM' : 'NAO') . "\n";
if ($row2) echo "  data: " . json_encode($row2) . "\n";
