<?php
/**
 * Smoke test: confirma que HandleInertiaRequests::buildUserPayload e
 * buildTenantPayload retornam apenas os campos da whitelist e o bloco
 * tenant corretamente.
 *
 * Remover após validação.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;

// Reflection para acessar os métodos protegidos
$middleware = new HandleInertiaRequests($app);
$ref = new ReflectionClass($middleware);

$buildUser = $ref->getMethod('buildUserPayload');
$buildUser->setAccessible(true);

$buildTenant = $ref->getMethod('buildTenantPayload');
$buildTenant->setAccessible(true);

echo "=== Test 1: user null ===\n";
$out = $buildUser->invoke($middleware, null);
echo "user payload (null user): " . json_encode($out) . "\n";
echo "tenant payload (null user): " . json_encode($buildTenant->invoke($middleware, null)) . "\n";
if ($out !== null) { echo "FALHA: esperava null\n"; exit(1); }

echo "\n=== Test 2: master admin (user id=1) ===\n";
$user = User::find(1);
$userPayload = $buildUser->invoke($middleware, $user);
$tenantPayload = $buildTenant->invoke($middleware, $user);
echo "user payload keys: " . json_encode(array_keys($userPayload)) . "\n";
echo "tenant payload: " . json_encode($tenantPayload) . "\n";

// Confere whitelist exata
$expectedKeys = ['id', 'name', 'email', 'email_verified_at', 'is_master_admin', 'roles', 'permissions'];
$actualKeys = array_keys($userPayload);
$extra = array_diff($actualKeys, $expectedKeys);
$missing = array_diff($expectedKeys, $actualKeys);

if (!empty($extra)) { echo "FALHA: campos extras vazaram: " . json_encode($extra) . "\n"; exit(1); }
if (!empty($missing)) { echo "FALHA: campos faltando: " . json_encode($missing) . "\n"; exit(1); }

// Confere que campos sensiveis NAO estao presentes
$forbidden = ['company_id', 'legacy_adms_user_id', 'is_active', 'password', 'remember_token', 'created_at', 'updated_at'];
foreach ($forbidden as $field) {
    if (array_key_exists($field, $userPayload)) {
        echo "FALHA: campo proibido '{$field}' vazou para o payload\n";
        exit(1);
    }
}

echo "\n=== Test 3: usuario sem company_id ===\n";
$fakeUser = new User(['name' => 'Fake', 'email' => 'fake@x.com']);
$fakeUser->id = 999;
$fakeUser->company_id = null;
$tenantNull = $buildTenant->invoke($middleware, $fakeUser);
echo "tenant payload (user sem company_id): " . json_encode($tenantNull) . "\n";
if ($tenantNull !== null) { echo "FALHA: esperava null para user sem company_id\n"; exit(1); }

echo "\n=== SMOKE TEST PASSOU ===\n";
echo "Whitelist exata: " . json_encode($expectedKeys) . "\n";
echo "Campos sensiveis NAO vazaram: " . json_encode($forbidden) . "\n";
