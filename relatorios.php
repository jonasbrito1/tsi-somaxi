<?php
// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar a sessão (mantido para compatibilidade)
session_start();

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Proteger página - requer login
require_login();

$user = get_logged_user();

// Definir período padrão (últimos 3 meses)
$periodo_inicio = isset($_GET['periodo_inicio']) ? $_GET['periodo_inicio'] : date('Y-m-01', strtotime('-2 months'));
$periodo_fim = isset($_GET['periodo_fim']) ? $_GET['periodo_fim'] : date('Y-m-t');

// === RELATÓRIOS ===

// 1. Resumo Executivo
$sql_resumo = "SELECT
    COUNT(DISTINCT tripulante) as total_clientes,
    AVG(pontuacao_integridade) as media_integridade,
    AVG(disponibilidade_servidor) as media_disponibilidade,
    SUM(ameacas_detectadas) as total_ameacas,
    SUM(alertas_resolvidos) as total_alertas_resolvidos,
    SUM(patches_instalados) as total_patches,
    SUM(total_dispositivos) as total_dispositivos,
    SUM(num_chamados_fechados) as chamados_resolvidos,
    SUM(num_chamados_abertos) as chamados_pendentes
FROM tabela_dados_tsi
WHERE created_at BETWEEN :inicio AND :fim";

try {
    $stmt = $pdo->prepare($sql_resumo);
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim . ' 23:59:59']);
    $resumo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $resumo = ['total_clientes' => 0, 'media_integridade' => 0, 'media_disponibilidade' => 0,
               'total_ameacas' => 0, 'total_alertas_resolvidos' => 0, 'total_patches' => 0,
               'total_dispositivos' => 0, 'chamados_resolvidos' => 0, 'chamados_pendentes' => 0];
}

// 2. Top 10 Clientes por Performance
$sql_top_performance = "SELECT
    tripulante,
    AVG(pontuacao_integridade) as integridade,
    AVG(disponibilidade_servidor) as disponibilidade,
    AVG(cobertura_antivirus) as antivirus,
    SUM(ameacas_detectadas) as ameacas,
    COUNT(*) as registros
FROM tabela_dados_tsi
WHERE created_at BETWEEN :inicio AND :fim
GROUP BY tripulante
ORDER BY integridade DESC, disponibilidade DESC
LIMIT 10";

try {
    $stmt = $pdo->prepare($sql_top_performance);
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim . ' 23:59:59']);
    $top_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_performance = [];
}

// 3. Clientes com Maior Risco
$sql_maior_risco = "SELECT
    tripulante,
    AVG(pontuacao_integridade) as integridade,
    AVG(disponibilidade_servidor) as disponibilidade,
    SUM(ameacas_detectadas) as total_ameacas,
    SUM(bkp_com_falha) as falhas_backup,
    AVG(cobertura_antivirus) as cobertura_av
FROM tabela_dados_tsi
WHERE created_at BETWEEN :inicio AND :fim
GROUP BY tripulante
HAVING integridade < 85 OR disponibilidade < 90 OR total_ameacas > 10
ORDER BY integridade ASC, total_ameacas DESC
LIMIT 10";

try {
    $stmt = $pdo->prepare($sql_maior_risco);
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim . ' 23:59:59']);
    $maior_risco = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $maior_risco = [];
}

// 4. Evolução Mensal
$sql_evolucao = "SELECT
    DATE_FORMAT(created_at, '%Y-%m') as periodo,
    AVG(pontuacao_integridade) as integridade,
    AVG(disponibilidade_servidor) as disponibilidade,
    SUM(ameacas_detectadas) as ameacas,
    COUNT(DISTINCT tripulante) as clientes_ativos
FROM tabela_dados_tsi
WHERE created_at BETWEEN :inicio AND :fim
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY periodo";

try {
    $stmt = $pdo->prepare($sql_evolucao);
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim . ' 23:59:59']);
    $evolucao = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $evolucao = [];
}

// 5. Estatísticas de Backup
$sql_backup_stats = "SELECT
    tripulante,
    SUM(bkp_completo) as completos,
    SUM(bkp_com_erro) as com_erro,
    SUM(bkp_com_falha) as com_falha,
    (SUM(bkp_completo) / NULLIF(SUM(bkp_completo + bkp_com_erro + bkp_com_falha), 0)) * 100 as taxa_sucesso
FROM tabela_dados_tsi
WHERE created_at BETWEEN :inicio AND :fim
GROUP BY tripulante
HAVING (completos + com_erro + com_falha) > 0
ORDER BY taxa_sucesso ASC";

try {
    $stmt = $pdo->prepare($sql_backup_stats);
    $stmt->execute([':inicio' => $periodo_inicio, ':fim' => $periodo_fim . ' 23:59:59']);
    $backup_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $backup_stats = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorios - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="css/app.css">
    <script src="js/theme.js"></script>
    <script src="js/user-dropdown.js"></script>
</head>

<body>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        Relatorios
                    </h1>
                    <p class="header-subtitle">Analise e exportacao de dados</p>
                </div>
            </div>
            <div class="header-logo">
                <img src="utils/logo_SOMAXI_GROUP_azul.png" alt="SOMAXI GROUP" class="company-logo">
            </div>
        </div>
    </header>

    <!-- NAVEGACAO -->
    <nav class="app-nav">
        <div class="nav-container">
            <div class="nav-links">
                <a href="index.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                    Dashboard
                </a>
                <a href="form.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Novo Registro
                </a>
                <a href="consulta.php">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Consultar Dados
                </a>
                <a href="relatorios.php" class="active">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    Relatorios
                </a>
                <?php if (is_admin()): ?>
                <a href="auth/cadastro_usuario.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    Usuários
                </a>
                <?php endif; ?>
            </div>
            <div class="user-section">
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Alternar tema">
                    <svg id="theme-icon-light" class="icon" viewBox="0 0 24 24" style="display: none;">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg id="theme-icon-dark" class="icon" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle">
                        <div class="user-avatar">
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($user['nome']); ?></span>
                        <svg class="icon icon-sm chevron" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="user-dropdown-menu">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <div class="dropdown-user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                                <div class="dropdown-user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="auth/editar_perfil.php" class="dropdown-item">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span>Editar Perfil</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="auth/logout.php" class="dropdown-item danger">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Sair</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTEUDO PRINCIPAL -->
    <main class="main-content">

        <!-- FILTROS DE PERIODO -->
        <section class="filter-section">
            <div class="filter-header">
                <div class="filter-title">
                    <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Filtro de Periodo
                </div>
            </div>
            <div class="filter-content">
                <form method="GET" action="relatorios.php">
                    <div class="filter-grid">
                        <div class="form-group mb-0">
                            <label class="form-label">Data Inicio</label>
                            <input type="date" class="form-control" id="periodo_inicio" name="periodo_inicio" value="<?php echo htmlspecialchars($periodo_inicio); ?>">
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="periodo_fim" name="periodo_fim" value="<?php echo htmlspecialchars($periodo_fim); ?>">
                        </div>
                    </div>

                    <div class="filter-actions mt-4" style="justify-content: center;">
                        <button type="submit" class="btn btn-primary">
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Atualizar Relatorio
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- PRINT HEADER -->
        <div class="print-only" style="display: none;">
            <h2>Relatorio TSI - Sistema de Gestao SOMAXI</h2>
            <p><strong>Periodo:</strong> <?php echo date('d/m/Y', strtotime($periodo_inicio)); ?> a <?php echo date('d/m/Y', strtotime($periodo_fim)); ?></p>
            <p><strong>Gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($username); ?></p>
        </div>

        <!-- RESUMO EXECUTIVO -->
        <section class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?php echo number_format($resumo['total_clientes'], 0, ',', '.'); ?></div>
                <div class="stat-label">
                    <svg class="icon icon-xs" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Clientes Ativos
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-value"><?php echo number_format($resumo['media_integridade'], 1, ',', '.'); ?>%</div>
                <div class="stat-label">
                    <svg class="icon icon-xs" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Integridade Media
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-value"><?php echo number_format($resumo['media_disponibilidade'], 1, ',', '.'); ?>%</div>
                <div class="stat-label">
                    <svg class="icon icon-xs" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Disponibilidade
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-value"><?php echo number_format($resumo['total_ameacas'], 0, ',', '.'); ?></div>
                <div class="stat-label">
                    <svg class="icon icon-xs" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Ameacas Detectadas
                </div>
            </div>

            <div class="stat-card primary">
                <div class="stat-value"><?php echo number_format($resumo['total_dispositivos'], 0, ',', '.'); ?></div>
                <div class="stat-label">
                    <svg class="icon icon-xs" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Total Dispositivos
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-value">
                    <?php
                    $total = $resumo['chamados_resolvidos'] + $resumo['chamados_pendentes'];
                    echo $total > 0 ? number_format(($resumo['chamados_resolvidos'] / $total) * 100, 1, ',', '.') : 0;
                    ?>%
                </div>
                <div class="stat-label">
                    <svg class="icon icon-xs" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Taxa Resolucao
                </div>
            </div>
        </section>

        <!-- TOP PERFORMANCE -->
        <?php if (!empty($top_performance)): ?>
        <section class="card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    Top 10 - Melhores Performances
                </div>
                <div class="d-flex gap-2">
                    <button onclick="exportTable('top-performance')" class="btn btn-info btn-sm">
                        <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Exportar Excel
                    </button>
                    <button onclick="window.print()" class="btn btn-secondary btn-sm">
                        <svg class="icon icon-sm" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="data-table" id="top-performance">
                        <thead>
                            <tr>
                                <th>Posicao</th>
                                <th>Tripulante</th>
                                <th>Integridade (%)</th>
                                <th>Disponibilidade (%)</th>
                                <th>Cobertura AV (%)</th>
                                <th>Ameacas</th>
                                <th>Registros</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_performance as $index => $cliente): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php
                                        if ($index == 0) echo 'badge-warning';
                                        elseif ($index == 1) echo 'badge-secondary';
                                        elseif ($index == 2) echo 'badge-info';
                                        else echo 'badge-secondary';
                                    ?>">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </td>
                                <td class="font-semibold"><?php echo htmlspecialchars($cliente['tripulante']); ?></td>
                                <td class="<?php echo $cliente['integridade'] >= 90 ? 'text-success' : ($cliente['integridade'] >= 80 ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo number_format($cliente['integridade'], 1, ',', '.'); ?>%
                                </td>
                                <td class="<?php echo $cliente['disponibilidade'] >= 95 ? 'text-success' : ($cliente['disponibilidade'] >= 90 ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo number_format($cliente['disponibilidade'], 1, ',', '.'); ?>%
                                </td>
                                <td><?php echo number_format($cliente['antivirus'], 1, ',', '.'); ?>%</td>
                                <td><?php echo $cliente['ameacas']; ?></td>
                                <td><?php echo $cliente['registros']; ?></td>
                                <td>
                                    <?php if ($cliente['integridade'] >= 90 && $cliente['disponibilidade'] >= 95): ?>
                                        <span class="badge badge-success">Excelente</span>
                                    <?php elseif ($cliente['integridade'] >= 80 && $cliente['disponibilidade'] >= 90): ?>
                                        <span class="badge badge-warning">Bom</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Critico</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CLIENTES DE MAIOR RISCO -->
        <?php if (!empty($maior_risco)): ?>
        <section class="card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Clientes com Maior Risco de Seguranca
                </div>
                <div class="d-flex gap-2">
                    <button onclick="exportTable('maior-risco')" class="btn btn-info btn-sm">
                        <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Exportar Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="data-table" id="maior-risco">
                        <thead>
                            <tr>
                                <th>Tripulante</th>
                                <th>Integridade (%)</th>
                                <th>Disponibilidade (%)</th>
                                <th>Ameacas</th>
                                <th>Falhas Backup</th>
                                <th>Cobertura AV (%)</th>
                                <th>Nivel de Risco</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maior_risco as $cliente): ?>
                            <tr>
                                <td class="font-semibold"><?php echo htmlspecialchars($cliente['tripulante']); ?></td>
                                <td class="text-danger"><?php echo number_format($cliente['integridade'], 1, ',', '.'); ?>%</td>
                                <td class="text-danger"><?php echo number_format($cliente['disponibilidade'], 1, ',', '.'); ?>%</td>
                                <td class="text-danger"><?php echo $cliente['total_ameacas']; ?></td>
                                <td class="text-danger"><?php echo $cliente['falhas_backup']; ?></td>
                                <td><?php echo number_format($cliente['cobertura_av'], 1, ',', '.'); ?>%</td>
                                <td>
                                    <?php
                                    $risco = 0;
                                    if ($cliente['integridade'] < 70) $risco += 3;
                                    elseif ($cliente['integridade'] < 85) $risco += 2;

                                    if ($cliente['total_ameacas'] > 20) $risco += 3;
                                    elseif ($cliente['total_ameacas'] > 10) $risco += 2;

                                    if ($cliente['falhas_backup'] > 5) $risco += 2;

                                    if ($risco >= 5) echo '<span class="badge badge-danger">CRITICO</span>';
                                    elseif ($risco >= 3) echo '<span class="badge badge-warning">ALTO</span>';
                                    else echo '<span class="badge badge-warning">MEDIO</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- EVOLUCAO TEMPORAL -->
        <?php if (!empty($evolucao)): ?>
        <section class="card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    Evolucao Temporal - Indicadores Principais
                </div>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 400px;">
                    <canvas id="evolucaoChart"></canvas>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ESTATISTICAS DE BACKUP -->
        <?php if (!empty($backup_stats)): ?>
        <section class="card mb-4">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Analise de Status de Backup
                </div>
                <div class="d-flex gap-2">
                    <button onclick="exportTable('backup-stats')" class="btn btn-info btn-sm">
                        <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Exportar Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="data-table" id="backup-stats">
                        <thead>
                            <tr>
                                <th>Tripulante</th>
                                <th>Completos</th>
                                <th>Com Erro</th>
                                <th>Com Falha</th>
                                <th>Taxa Sucesso</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_stats as $backup): ?>
                            <tr>
                                <td class="font-semibold"><?php echo htmlspecialchars($backup['tripulante']); ?></td>
                                <td class="text-success"><?php echo $backup['completos']; ?></td>
                                <td class="text-warning"><?php echo $backup['com_erro']; ?></td>
                                <td class="text-danger"><?php echo $backup['com_falha']; ?></td>
                                <td class="<?php echo $backup['taxa_sucesso'] >= 95 ? 'text-success' : ($backup['taxa_sucesso'] >= 80 ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo number_format($backup['taxa_sucesso'], 1, ',', '.'); ?>%
                                </td>
                                <td>
                                    <?php if ($backup['taxa_sucesso'] >= 95): ?>
                                        <span class="badge badge-success">Otimo</span>
                                    <?php elseif ($backup['taxa_sucesso'] >= 80): ?>
                                        <span class="badge badge-warning">Regular</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Critico</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <script>
        // Grafico de Evolucao Temporal
        <?php if (!empty($evolucao)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('evolucaoChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [
                        <?php foreach ($evolucao as $item): ?>
                            '<?php echo date('m/Y', strtotime($item['periodo'] . '-01')); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Integridade (%)',
                        data: [
                            <?php foreach ($evolucao as $item): ?>
                                <?php echo number_format($item['integridade'], 1, ',', '.'); ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }, {
                        label: 'Disponibilidade (%)',
                        data: [
                            <?php foreach ($evolucao as $item): ?>
                                <?php echo number_format($item['disponibilidade'], 1, ',', '.'); ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }, {
                        label: 'Ameacas Detectadas',
                        data: [
                            <?php foreach ($evolucao as $item): ?>
                                <?php echo $item['ameacas']; ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1',
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolucao dos Indicadores de Seguranca e Performance',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: 20
                        },
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#1f2937',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Periodo',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Percentual (%)',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Numero de Ameacas',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        });
        <?php endif; ?>

        // Funcao para exportar tabelas
        function exportTable(tableId) {
            // Implementacao simplificada - em producao usar biblioteca como SheetJS
            const table = document.getElementById(tableId);
            if (!table) {
                alert('Tabela nao encontrada!');
                return;
            }

            // Aqui voce implementaria a exportacao real para Excel
            alert('Funcionalidade de exportacao sera implementada em breve.\n\nPor enquanto, voce pode usar Ctrl+P para imprimir ou copiar os dados manualmente.');

            // Exemplo de implementacao basica com CSV
            /*
            let csv = '';
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const cols = rows[i].querySelectorAll('td, th');
                const rowData = [];

                for (let j = 0; j < cols.length; j++) {
                    rowData.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }

                csv += rowData.join(',') + '\n';
            }

            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', tableId + '_' + new Date().toISOString().split('T')[0] + '.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            */
        }

        // Animacao de contadores no resumo
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-value');

            counters.forEach(counter => {
                const target = parseFloat(counter.textContent.replace(/[^\d.]/g, ''));
                if (target > 0) {
                    let current = 0;
                    const increment = target / 50;
                    const isPercentage = counter.textContent.includes('%');

                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.textContent = isPercentage ?
                                target.toFixed(1) + '%' :
                                Math.round(target).toLocaleString();
                            clearInterval(timer);
                        } else {
                            counter.textContent = isPercentage ?
                                current.toFixed(1) + '%' :
                                Math.round(current).toLocaleString();
                        }
                    }, 50);
                }
            });
        });

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + P para imprimir
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>

    <style>
        /* Estilos especificos para impressao */
        @media print {
            .print-only {
                display: block !important;
                padding: 1rem;
                border-bottom: 2px solid #000;
                margin-bottom: 2rem;
            }
        }
    </style>
</body>
</html>
