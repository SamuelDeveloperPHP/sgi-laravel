<?php
/**
 * Smoke test: simula uma operação de master admin e confere se o
 * MasterAdminAuditObserver gravou o evento em master_admin_audit_log.
 *
 * Não-destrutivo: faz update e desfaz. Apenas a linha de auditoria
 * fica registrada (proposital — confirma que o observer disparou).
 *
 * Remover após validação.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$before = DB::table('master_admin_audit_log')->count();
echo "Linhas em audit_log ANTES: {$before}\n";

// 1. Loga como master admin (user 1)
auth()->loginUsingId(1);
$user = auth()->user();
if (!$user || !$user->is_master_admin) {
    echo "FALHA: user 1 não é master admin (ou não existe). Auth: " . json_encode($user?->only(['id','name','is_master_admin'])) . "\n";
    exit(1);
}
echo "Autenticado como: {$user->name} (id={$user->id}, master=" . ($user->is_master_admin ? 'true' : 'false') . ")\n";

// 2. Encontra um Projeto qualquer e faz um update inócuo
$projeto = \App\Models\Projeto::withoutGlobalScopes()->first();
if (!$projeto) {
    echo "FALHA: nenhum projeto no banco para testar.\n";
    exit(1);
}

$descOriginal = $projeto->descricao;
$novaDesc = 'audit-smoke-test-' . date('YmdHis');
echo "Atualizando Projeto id={$projeto->id} descricao: '{$descOriginal}' -> '{$novaDesc}'\n";
$projeto->descricao = $novaDesc;
$projeto->save();

// 3. Reverte para não poluir o banco
$projeto->descricao = $descOriginal;
$projeto->save();
echo "Revertido para descricao original.\n";

// 4. Conta o que foi auditado
$after = DB::table('master_admin_audit_log')->count();
$novos = $after - $before;
echo "Linhas em audit_log DEPOIS: {$after}  (delta: +{$novos})\n";

if ($novos < 2) {
    echo "FALHA: esperava +2 linhas (1 update + 1 reverter). Observer pode nao estar registrado.\n";
    exit(1);
}

// 5. Mostra a última linha de auditoria
$ultima = DB::table('master_admin_audit_log')->orderByDesc('id')->first();
echo "Ultima entrada de auditoria:\n";
echo json_encode([
    'id'                => $ultima->id,
    'user_id'           => $ultima->user_id,
    'company_id_target' => $ultima->company_id_target,
    'ability'           => $ultima->ability,
    'model_type'        => $ultima->model_type,
    'model_id'          => $ultima->model_id,
    'ip_address'        => $ultima->ip_address,
    'created_at'        => $ultima->created_at,
    'changes_keys'      => array_keys(json_decode($ultima->changes_json, true) ?? []),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n=== SMOKE TEST PASSOU ===\n";
