<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "NaoConformidade Count: " . \App\Models\NaoConformidade::count() . "\n";
echo "PlanoAcao Count: " . \App\Models\PlanoAcao::count() . "\n";
echo "AuditoriaInterna Count: " . \App\Models\AuditoriaInterna::count() . "\n";

$nc = \App\Models\NaoConformidade::first();
if ($nc) echo "First NC: " . $nc->id . " - " . $nc->descOcorrencia . "\n";

