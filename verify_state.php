<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=meusgi', 'root', '');

$total = $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
$tests = $pdo->query("SELECT COUNT(*) FROM companies WHERE nome_fantasia LIKE 'TEST %'")->fetchColumn();
$real = $total - $tests;

$users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$testUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE '%@test.local'")->fetchColumn();
$realUsers = $users - $testUsers;

echo "=== meusgi - Diagnostico Final ===\n\n";
echo "COMPANIES: $total total\n";
echo "  - Lixo de teste (TEST %): $tests\n";
echo "  - Aparentes reais (Faker/produção): $real\n\n";

echo "USERS: $users total\n";
echo "  - Lixo de teste (@test.local): $testUsers\n";
echo "  - Aparentes reais: $realUsers\n\n";

echo "DADOS LEGADOS RESTAURADOS de sgi_qsms:\n";
foreach (['sts_naoconforme', 'sts_projetos', 'sts_tarefas_projeto', 'sts_auditoriainternaqualidade', 'sts_pa', 'sts_escopo', 'sts_objetivo'] as $t) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    printf("  %-35s %5d linhas\n", $t, $cnt);
}

echo "\nNOVAS TABELAS (vazias, prontas pra uso):\n";
foreach (['modules', 'roles', 'permissions', 'ata_reuniaos', 'fornecedores', 'controle_calibracaos', 'master_admin_audit_log'] as $t) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    printf("  %-35s %5d linhas\n", $t, $cnt);
}

echo "\nTotal de tabelas no meusgi: " . count($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)) . "\n";
