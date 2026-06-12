<?php
/**
 * Converte todas as tabelas MyISAM do banco meusgi para InnoDB.
 *
 * Beneficios do InnoDB sobre MyISAM:
 *   - Suporta FK constraints (integridade referencial)
 *   - Suporta transactions (ACID)
 *   - Row-level locking (vs table-level no MyISAM)
 *   - Crash recovery automatico
 *   - Mantido ativamente pelo MariaDB/MySQL (MyISAM e legado)
 *
 * Operacao APENAS ALTER TABLE - nao deleta nem altera dados.
 *
 * Algumas tabelas podem falhar conversao se tiverem features
 * MyISAM-only (ex: FULLTEXT em colunas TEXT antes do MySQL 5.6,
 * ou indices muito grandes). Esses casos sao reportados mas nao
 * interrompem o resto da execucao.
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=meusgi', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Lista todas as tabelas MyISAM
$tables = $pdo->query("
    SELECT TABLE_NAME, TABLE_ROWS
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'meusgi'
    AND ENGINE = 'MyISAM'
    ORDER BY TABLE_NAME
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($tables)) {
    echo 'Nenhuma tabela MyISAM encontrada em meusgi. Nada a fazer.' . PHP_EOL;
    exit;
}

echo 'Encontradas ' . count($tables) . ' tabelas MyISAM. Convertendo para InnoDB...' . PHP_EOL;
echo PHP_EOL;

$success = 0;
$failed = [];

foreach ($tables as $t) {
    $name = $t['TABLE_NAME'];
    $rows = $t['TABLE_ROWS'];

    try {
        $start = microtime(true);
        $pdo->exec("ALTER TABLE `$name` ENGINE=InnoDB");
        $elapsed = round((microtime(true) - $start) * 1000);
        printf("  [OK] %-40s %6d linhas  (%4d ms)\n", $name, $rows, $elapsed);
        $success++;
    } catch (\Throwable $e) {
        printf("  [FAIL] %-38s %s\n", $name, substr($e->getMessage(), 0, 80));
        $failed[] = ['name' => $name, 'error' => $e->getMessage()];
    }
}

echo PHP_EOL;
echo "Resultado: $success convertidas, " . count($failed) . ' falhas' . PHP_EOL;

if (!empty($failed)) {
    echo PHP_EOL . 'Falhas detalhadas:' . PHP_EOL;
    foreach ($failed as $f) {
        echo '  - ' . $f['name'] . ': ' . $f['error'] . PHP_EOL;
    }
}

// Verifica estado final
$remaining = $pdo->query("
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'meusgi' AND ENGINE = 'MyISAM'
")->fetchColumn();

echo PHP_EOL . "Tabelas MyISAM restantes em meusgi: $remaining" . PHP_EOL;
