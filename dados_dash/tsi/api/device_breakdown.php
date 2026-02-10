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

    $cliente = isset($_GET['cliente']) ? trim((string)$_GET['cliente']) : null;
    $ano     = isset($_GET['ano']) ? (int)$_GET['ano'] : null;
    $mes     = isset($_GET['mes']) ? normalizeMes($_GET['mes']) : null;
    $ultimo  = isset($_GET['ultimo']) ? (int)$_GET['ultimo'] : 0;

    if ($ultimo === 1 || (!$ano || !$mes)) {
        $period = detectLatestPeriod($pdo, $table);
        $ano = $ano ?: $period['ano'];
        $mes = $mes ?: $period['mes'];
    }

    // Lista de colunas de dispositivos (usaremos somente as existentes)
    $deviceCols = [
        'dispositivos_servidor'           => 'Servidores',
        'dispositivos_estacoes_trabalho'  => 'Estações de trabalho',
        'dispositivos_perifericos'        => 'Periféricos',
        'dispositivos_outros'             => 'Outros',
        'dispositivos_desktop'            => 'Desktops',
    ];
    $selectParts = [];
    foreach ($deviceCols as $col => $label) {
        if (columnExists($pdo, $table, $col)) {
            $selectParts[] = "SUM(COALESCE(`{$col}`,0)) AS `{$col}`";
        }
    }
    if (empty($selectParts)) {
        echo json_encode(['series' => [], 'periodo' => ['ano'=>$ano,'mes'=>$mes]]);
        exit;
    }

    $where = [];
    $params = [':ano' => (int)$ano, ':mes' => (int)$mes];

    if ($cliente) {
        $where[] = '`cliente` = :cliente';
        $params[':cliente'] = $cliente;
    }

    if (columnExists($pdo, $table, 'criado_em')) {
        $where[] = 'YEAR(`criado_em`) = :ano';
        $where[] = 'MONTH(`criado_em`) = :mes';
    } else {
        if (columnExists($pdo, $table, 'ano_relatorio')) {
            $where[] = 'CAST(`ano_relatorio` AS UNSIGNED) = :ano';
        }
        if (columnExists($pdo, $table, 'mes_relatorio')) {
            $where[] = 'CAST(`mes_relatorio` AS UNSIGNED) = :mes';
        }
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT " . implode(",\n", $selectParts) . " 
            FROM `{$table}`
            {$whereSql}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch() ?: [];

    $series = [];
    foreach ($deviceCols as $col => $label) {
        if (array_key_exists($col, $row)) {
            $series[] = ['name' => $label, 'value' => (int)$row[$col]];
        }
    }

    echo json_encode([
        'periodo' => ['ano' => (int)$ano, 'mes' => (int)$mes],
        'series'  => $series,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao montar distribuição de dispositivos', 'detail' => $e->getMessage()]);
}
