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

    // Filtros
    $cliente = isset($_GET['cliente']) ? trim((string)$_GET['cliente']) : null;
    $ano     = isset($_GET['ano']) ? (int)$_GET['ano'] : null;
    $mes     = isset($_GET['mes']) ? normalizeMes($_GET['mes']) : null;
    $ultimo  = isset($_GET['ultimo']) ? (int)$_GET['ultimo'] : 0;

    if ($ultimo === 1 || (!$ano || !$mes)) {
        $period = detectLatestPeriod($pdo, $table);
        $ano = $ano ?: $period['ano'];
        $mes = $mes ?: $period['mes'];
    }

    // Expressões para percentuais (usa o que existir)
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
        // alternativas comuns
        foreach (['disponibilidade','uptime_servidor'] as $alt) {
            $tmp = percentExpr($pdo, $table, $alt);
            if ($tmp !== 'NULL') { $dispExpr = $tmp; break; }
        }
    }
    if ($dispExpr === 'NULL') $dispExpr = 'NULL';

    // Dispositivos (usa o que existir)
    $devicesSum = sumColsExpr($pdo, $table, [
        'dispositivos_servidor',
        'dispositivos_estacoes_trabalho',
        'dispositivos_perifericos',
        'dispositivos_outros',
        'dispositivos_desktop',
    ]);

    // Outras somas úteis
    $acessoRemoto = columnExists($pdo, $table, 'acesso_remoto') ? 'COALESCE(`acesso_remoto`,0)' : '0';
    $falhasLogon  = columnExists($pdo, $table, 'falhas_tentativas_logon') ? 'COALESCE(`falhas_tentativas_logon`,0)' : '0';
    $verifRede    = columnExists($pdo, $table, 'verificacoes_aprovadas_rede') ? 'COALESCE(`verificacoes_aprovadas_rede`,0)' : '0';

    // Filtro de período: preferimos criado_em se existir, senão (ano_relatorio, mes_relatorio)
    $where = [];
    $params = [];

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
    $params[':ano'] = (int)$ano;
    $params[':mes'] = (int)$mes;

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT 
                COUNT(*) AS total_registros,
                AVG({$antivirusExpr}) AS antivirus_avg,
                AVG({$patchExpr}) AS patch_avg,
                AVG({$webExpr}) AS web_avg,
                AVG({$dispExpr}) AS disponibilidade_avg,
                SUM({$devicesSum}) AS dispositivos_total,
                SUM({$acessoRemoto}) AS acesso_remoto_total,
                SUM({$falhasLogon}) AS falhas_tentativas_logon_total,
                SUM({$verifRede}) AS verificacoes_aprovadas_rede_total
            FROM `{$table}`
            {$whereSql}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetch();

    // Monta resposta
    $resp = [
        'periodo' => [
            'ano' => (int)$ano,
            'mes' => (int)$mes,
        ],
        'kpis' => [
            'total_registros' => (int)($data['total_registros'] ?? 0),
            'antivirus_avg' => $data['antivirus_avg'] !== null ? (float)$data['antivirus_avg'] : null,
            'patch_avg' => $data['patch_avg'] !== null ? (float)$data['patch_avg'] : null,
            'web_avg' => $data['web_avg'] !== null ? (float)$data['web_avg'] : null,
            'disponibilidade_avg' => $data['disponibilidade_avg'] !== null ? (float)$data['disponibilidade_avg'] : null,
            'dispositivos_total' => (int)($data['dispositivos_total'] ?? 0),
            'acesso_remoto_total' => (int)($data['acesso_remoto_total'] ?? 0),
            'falhas_tentativas_logon_total' => (int)($data['falhas_tentativas_logon_total'] ?? 0),
            'verificacoes_aprovadas_rede_total' => (int)($data['verificacoes_aprovadas_rede_total'] ?? 0),
        ],
    ];

    echo json_encode($resp);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao calcular KPIs', 'detail' => $e->getMessage()]);
}
