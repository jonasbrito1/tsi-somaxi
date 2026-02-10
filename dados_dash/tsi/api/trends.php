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

    if (!$ano) {
        $period = detectLatestPeriod($pdo, $table);
        $ano = (int)$period['ano'];
    }

    // Expressões para percentuais
    $antivirusExpr = null;
    foreach (['antivirus_cobertura','antivirus_protecao','antivirus'] as $c) {
        $expr = percentExpr($pdo, $table, $c);
        if ($expr !== 'NULL') { $antivirusExpr = $antivirusExpr ? "COALESCE({$antivirusExpr}, {$expr})" : $expr; }
    }
    if (!$antivirusExpr) $antivirusExpr = 'NULL';

    $patchExpr = null;
    foreach (['gerenciamento_patch_cobertura','gerenciamento_patch_protecao','gerenciamento_patch'] as $c) {
        $expr = percentExpr($pdo, $table, $c);
        if ($expr !== 'NULL') { $patchExpr = $patchExpr ? "COALESCE({$patchExpr}, {$expr})" : $expr; }
    }
    if (!$patchExpr) $patchExpr = 'NULL';

    $webExpr = null;
    foreach (['web_protection_cobertura','web_protection_protecao','web_protection'] as $c) {
        $expr = percentExpr($pdo, $table, $c);
        if ($expr !== 'NULL') { $webExpr = $webExpr ? "COALESCE({$webExpr}, {$expr})" : $expr; }
    }
    if (!$webExpr) $webExpr = 'NULL';

    $dispExpr = percentExpr($pdo, $table, 'disponibilidade_servidor');
    if ($dispExpr === 'NULL') {
        foreach (['disponibilidade','uptime_servidor'] as $alt) {
            $tmp = percentExpr($pdo, $table, $alt);
            if ($tmp !== 'NULL') { $dispExpr = $tmp; break; }
        }
    }
    if ($dispExpr === 'NULL') $dispExpr = 'NULL';

    // Escolhe como agrupar meses
    $useCriado = columnExists($pdo, $table, 'criado_em');
    $useAnoMesCols = columnExists($pdo, $table, 'ano_relatorio') && columnExists($pdo, $table, 'mes_relatorio');

    if (!$useCriado && !$useAnoMesCols) {
        echo json_encode(['error' => 'Não há colunas suficientes para construir a tendência mensal.']);
        exit;
    }

    $where = [];
    $params = [':ano' => $ano];

    if ($cliente) {
        $where[] = '`cliente` = :cliente';
        $params[':cliente'] = $cliente;
    }

    if ($useCriado) {
        $where[] = 'YEAR(`criado_em`) = :ano';
        $monthExpr = 'MONTH(`criado_em`)';
        $yearExpr  = 'YEAR(`criado_em`)';
    } else {
        $where[] = 'CAST(`ano_relatorio` AS UNSIGNED) = :ano';
        $monthExpr = 'CAST(`mes_relatorio` AS UNSIGNED)';
        $yearExpr  = 'CAST(`ano_relatorio` AS UNSIGNED)';
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT 
                {$yearExpr} AS yr,
                {$monthExpr} AS m,
                AVG({$antivirusExpr}) AS antivirus_avg,
                AVG({$patchExpr}) AS patch_avg,
                AVG({$webExpr}) AS web_avg,
                AVG({$dispExpr}) AS disponibilidade_avg
            FROM `{$table}`
            {$whereSql}
            GROUP BY yr, m
            ORDER BY yr ASC, m ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Normaliza meses 1..12
    $out = [
        'ano' => $ano,
        'categories' => ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
        'series' => [
            'antivirus_avg' => array_fill(0, 12, null),
            'patch_avg' => array_fill(0, 12, null),
            'web_avg' => array_fill(0, 12, null),
            'disponibilidade_avg' => array_fill(0, 12, null),
        ],
    ];

    foreach ($rows as $r) {
        $m = (int)$r['m'];
        if ($m < 1 || $m > 12) continue;
        $i = $m - 1;
        $out['series']['antivirus_avg'][$i] = isset($r['antivirus_avg']) ? (float)$r['antivirus_avg'] : null;
        $out['series']['patch_avg'][$i] = isset($r['patch_avg']) ? (float)$r['patch_avg'] : null;
        $out['series']['web_avg'][$i] = isset($r['web_avg']) ? (float)$r['web_avg'] : null;
        $out['series']['disponibilidade_avg'][$i] = isset($r['disponibilidade_avg']) ? (float)$r['disponibilidade_avg'] : null;
    }

    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao montar tendências', 'detail' => $e->getMessage()]);
}
