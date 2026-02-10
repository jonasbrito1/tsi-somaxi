<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$pdo   = connectDB();
$table = envOr('TABLE_NAME', 'rmm_relatorios');


try {
    $pdo = connectDB();

    if (!tableExists($pdo, $table)) {
        http_response_code(500);
        echo json_encode(['error' => "Tabela '{$table}' não encontrada."]);
        exit;
    }

    if (!columnExists($pdo, $table, 'cliente')) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->query("SELECT DISTINCT `cliente`
                         FROM `{$table}`
                         WHERE `cliente` IS NOT NULL AND `cliente` <> ''
                         ORDER BY `cliente` ASC");
    $clients = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo json_encode($clients);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao listar clientes', 'detail' => $e->getMessage()]);
}
