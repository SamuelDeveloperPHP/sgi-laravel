<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Total users e companies AGORA ===\n";
echo "users: " . DB::table('users')->count() . "\n";
echo "companies: " . DB::table('companies')->count() . "\n";

echo "\n=== Companies presentes ===\n";
foreach (DB::table('companies')->select('id', 'nome_fantasia')->get() as $c) {
    echo "  id={$c->id} nome={$c->nome_fantasia}\n";
}

echo "\n=== Users TEST presentes ===\n";
foreach (DB::table('users')->where('email', 'like', '%@test.local')->select('id', 'email', 'company_id', 'is_master_admin')->get() as $u) {
    echo "  id={$u->id} email={$u->email} company_id={$u->company_id} master=" . ($u->is_master_admin ? 'Y' : 'N') . "\n";
}

echo "\n=== Projetos com 'Tentativa de inje' ===\n";
foreach (DB::table('sts_projetos')->where('nomeProjeto', 'like', '%Tentativa%')->get() as $p) {
    echo "  id={$p->id} nome={$p->nomeProjeto} company_id={$p->company_id}\n";
}
