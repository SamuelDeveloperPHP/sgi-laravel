<?php
/**
 * Script utilitario de recuperacao: sincroniza a tabela `migrations`
 * com o estado atual das tabelas do banco meusgi.
 *
 * Para cada migration file na pasta:
 *   - Se ja registrada -> pula
 *   - Se for CREATE TABLE de uma tabela que JA EXISTE -> marca como run
 *   - Se for CREATE TABLE de uma tabela que NAO existe -> deixa pendente
 *   - Se NAO for CREATE TABLE -> deixa pendente (ALTER sera executado)
 *
 * EXCECAO: 2026_06_11_* (Sprint 1) sempre roda - novas migrations.
 * EXCECAO: create_permission_tables (Spatie) sempre roda se nao tem roles.
 *
 * APENAS INSERT na tabela migrations - nao apaga nem altera estrutura.
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=meusgi', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$registered = array_flip($pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN));
$batch = (int) $pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn() + 1;

$migrationFiles = glob(__DIR__ . '/database/migrations/*.php');
sort($migrationFiles);

$toMark = [];
$toRun = [];

foreach ($migrationFiles as $file) {
    $name = basename($file, '.php');
    if (isset($registered[$name])) continue;

    // Sprint 1 + Spatie sempre rodam
    if (str_starts_with($name, '2026_06_11_') ||
        str_contains($name, 'create_permission_tables')) {
        $toRun[] = $name;
        continue;
    }

    $content = file_get_contents($file);

    // Detecta CREATE TABLE hardcoded
    preg_match_all("/Schema::create\(['\"](\w+)['\"]/m", $content, $matches);
    $createsTables = $matches[1] ?? [];

    if (!empty($createsTables)) {
        // Se TODAS as tabelas que cria ja existem, marca como run
        $allExist = true;
        foreach ($createsTables as $t) {
            if (!in_array($t, $existingTables, true)) {
                $allExist = false;
                break;
            }
        }
        if ($allExist) {
            $toMark[] = $name;
        } else {
            $toRun[] = $name;
        }
    } else {
        // Nao tem CREATE hardcoded - deixa rodar (ALTER ou Spatie-style)
        $toRun[] = $name;
    }
}

echo "A marcar como run: " . count($toMark) . PHP_EOL;
foreach ($toMark as $m) echo "  [MARK] $m" . PHP_EOL;
echo PHP_EOL;
echo "A rodar: " . count($toRun) . PHP_EOL;
foreach ($toRun as $m) echo "  [RUN]  $m" . PHP_EOL;
echo PHP_EOL;

if (($argv[1] ?? '') === '--apply') {
    $stmt = $pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
    foreach ($toMark as $m) {
        try {
            $stmt->execute([$m, $batch]);
        } catch (\Throwable $e) {
            echo "  [SKIP] $m - " . $e->getMessage() . PHP_EOL;
        }
    }
    echo "Aplicado. Rode: php artisan migrate" . PHP_EOL;
}
