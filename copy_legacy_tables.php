<?php
/**
 * Copia tabelas legadas sts_* (e outras necessarias) do banco
 * sgi_qsms para meusgi.
 *
 * Estrategia:
 *   1. CREATE TABLE IF NOT EXISTS meusgi.X LIKE sgi_qsms.X
 *   2. INSERT INTO meusgi.X SELECT * FROM sgi_qsms.X
 *
 * NAO sobrescreve tabelas que ja existem no meusgi.
 *
 * Tabelas alvo:
 *   - sts_naoconforme, sts_pa, sts_projetos, sts_tarefas_projeto,
 *     sts_auditoriainternaqualidade, sts_escopo,
 *     sts_nossa_historia, sts_artigos, etc.
 */

$pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$srcDb = 'sgi_qsms';
$dstDb = 'meusgi';

// Lista todas as tabelas no source que comecam com 'sts_' ou que
// estao na lista explicita (tabelas legadas necessarias para o app)
$srcTables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = '$srcDb'
    AND (TABLE_NAME LIKE 'sts_%' OR TABLE_NAME IN ('cads_clientes', 'sts_artigos'))
")->fetchAll(PDO::FETCH_COLUMN);

$dstTables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = '$dstDb'")->fetchAll(PDO::FETCH_COLUMN);

echo "Tabelas legadas encontradas em $srcDb: " . count($srcTables) . PHP_EOL;
echo PHP_EOL;

$copied = 0;
$skipped = 0;
foreach ($srcTables as $table) {
    if (in_array($table, $dstTables, true)) {
        echo "  [SKIP] $table - ja existe em $dstDb" . PHP_EOL;
        $skipped++;
        continue;
    }

    try {
        // CREATE TABLE com estrutura
        $pdo->exec("CREATE TABLE `$dstDb`.`$table` LIKE `$srcDb`.`$table`");

        // INSERT dados (ignore caso linhas duplicadas)
        $stmt = $pdo->query("INSERT INTO `$dstDb`.`$table` SELECT * FROM `$srcDb`.`$table`");
        $rows = $stmt->rowCount();

        echo "  [COPY] $table - $rows linhas" . PHP_EOL;
        $copied++;
    } catch (\Throwable $e) {
        echo "  [FAIL] $table - " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL;
echo "Total: $copied copiadas, $skipped puladas" . PHP_EOL;
