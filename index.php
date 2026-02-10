<?php
session_start();
require_once 'includes/db.php';

// Filtros
$filtro_tripulante = isset($_GET['tripulante']) ? trim($_GET['tripulante']) : '';
$filtro_mes = isset($_GET['mes']) ? trim($_GET['mes']) : '';
$filtro_ano = isset($_GET['ano']) ? trim($_GET['ano']) : '';

// Construir WHERE clause
$where_clause = "WHERE 1=1";
$params = [];

if (!empty($filtro_tripulante)) {
    $where_clause .= " AND tripulante LIKE :tripulante";
    $params[':tripulante'] = "%$filtro_tripulante%";
}

if (!empty($filtro_mes)) {
    $where_clause .= " AND mes = :mes";
    $params[':mes'] = $filtro_mes;
}

if (!empty($filtro_ano)) {
    $where_clause .= " AND ano = :ano";
    $params[':ano'] = $filtro_ano;
}

try {
    // Indicadores Gerais
    $sql_indicadores = "SELECT
        COUNT(DISTINCT tripulante) as total_tripulantes,
        ROUND(AVG(pontuacao_integridade), 1) as media_integridade,
        SUM(total_dispositivos) as total_dispositivos,
        SUM(num_chamados_abertos) as total_chamados_abertos,
        SUM(num_chamados_fechados) as total_chamados_fechados,
        SUM(ameacas_detectadas) as total_ameacas,
        ROUND(AVG(disponibilidade_servidor), 1) as media_disponibilidade,
        ROUND(AVG(cobertura_antivirus), 1) as media_antivirus,
        ROUND(AVG(cobertura_atualizacao_patches), 1) as media_patches,
        ROUND(AVG(cobertura_web_protection), 1) as media_web_protection,
        SUM(alertas_resolvidos) as total_alertas_resolvidos,
        SUM(patches_instalados) as total_patches_instalados,
        SUM(acessos_remotos) as total_acessos_remotos,
        SUM(bkp_completo) as total_bkp_completo,
        SUM(bkp_com_erro) as total_bkp_erro,
        SUM(bkp_com_falha) as total_bkp_falha
    FROM tabela_dados_tsi $where_clause";

    $stmt = $pdo->prepare($sql_indicadores);
    $stmt->execute($params);
    $indicadores = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach ($indicadores as $key => $value) {
        if ($value === null) $indicadores[$key] = 0;
    }

    // Distribuicao de Dispositivos
    $sql_dispositivos = "SELECT
        SUM(tipo_desktop) as desktops,
        SUM(tipo_notebook) as notebooks,
        SUM(tipo_servidor) as servidores
    FROM tabela_dados_tsi $where_clause";

    $stmt = $pdo->prepare($sql_dispositivos);
    $stmt->execute($params);
    $dispositivos = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach ($dispositivos as $key => $value) {
        if ($value === null) $dispositivos[$key] = 0;
    }

    // Dados por mes
    $sql_por_mes = "SELECT
        mes,
        COUNT(DISTINCT tripulante) as tripulantes,
        SUM(total_dispositivos) as dispositivos
    FROM tabela_dados_tsi $where_clause
    GROUP BY mes
    ORDER BY FIELD(mes, 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho',
                   'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro')";

    $stmt = $pdo->prepare($sql_por_mes);
    $stmt->execute($params);
    $dados_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Seguranca
    $sql_seguranca = "SELECT
        SUM(web_protection_filtradas_bloqueadas) as total_filtradas,
        SUM(web_protection_mal_intencionadas_bloqueadas) as total_mal_intencionadas
    FROM tabela_dados_tsi $where_clause";

    $stmt = $pdo->prepare($sql_seguranca);
    $stmt->execute($params);
    $seguranca = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach ($seguranca as $key => $value) {
        if ($value === null) $seguranca[$key] = 0;
    }

} catch (PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard TSI - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- HEADER -->
        <header class="dashboard-header">
            <div class="header-left">
                <div class="header-title-group">
                    <h1 class="dashboard-title">Dashboard TSI</h1>
                    <p class="dashboard-subtitle">Monitoramento de Seguranca - SOMAXI Group</p>
                </div>
            </div>
            <div class="header-right">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <svg id="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                    <svg id="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span id="theme-text">Escuro</span>
                </button>
                <img src="utils/logo_SOMAXI_GROUP_azul.png" alt="SOMAXI" class="dashboard-logo">
            </div>
        </header>

        <!-- NAVEGACAO -->
        <nav class="dashboard-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="form.php">Novo Registro</a>
            <a href="consulta.php">Consultar</a>
            <a href="relatorios.php">Relatorios</a>
        </nav>

        <!-- LINHA 1: Total Tripulantes + Grafico -->
        <div class="kpi-row">
            <div class="kpi-card">
                <span class="kpi-label">TOTAL TRIPULANTES</span>
                <span class="kpi-value"><?php echo number_format($indicadores['total_tripulantes']); ?></span>
            </div>
            <div class="chart-card">
                <span class="chart-label">TOTAL DE TRIPULANTES POR MES</span>
                <div class="chart-container">
                    <div class="bar-chart-vertical">
                        <div class="bar-y-axis">
                            <span>128</span>
                            <span>64</span>
                            <span>32</span>
                            <span>16</span>
                        </div>
                        <div class="bar-chart-area">
                            <?php
                            $max_trip = 128;
                            if (!empty($dados_por_mes)):
                                foreach ($dados_por_mes as $mes):
                                    $height = min(($mes['tripulantes'] / $max_trip) * 100, 100);
                            ?>
                            <div class="bar-column">
                                <div class="bar-vertical" style="height: <?php echo max($height, 8); ?>%;">
                                    <span class="bar-value-top"><?php echo $mes['tripulantes']; ?></span>
                                </div>
                                <span class="bar-month"><?php echo $mes['mes']; ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="bar-column">
                                <div class="bar-vertical" style="height: <?php echo max(($indicadores['total_tripulantes'] / $max_trip) * 100, 8); ?>%;">
                                    <span class="bar-value-top"><?php echo $indicadores['total_tripulantes']; ?></span>
                                </div>
                                <span class="bar-month">Total</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LINHA 2: Total Dispositivos + Grafico -->
        <div class="kpi-row">
            <div class="kpi-card">
                <span class="kpi-label">TOTAL DE DISPOSITIVOS</span>
                <span class="kpi-value"><?php echo number_format($indicadores['total_dispositivos']); ?></span>
            </div>
            <div class="chart-card">
                <span class="chart-label">TOTAL DE DISPOSITIVOS POR MES</span>
                <div class="chart-container">
                    <div class="bar-chart-vertical">
                        <div class="bar-y-axis">
                            <span>1024</span>
                            <span>512</span>
                            <span>256</span>
                            <span>128</span>
                        </div>
                        <div class="bar-chart-area">
                            <?php
                            $max_disp = 1024;
                            if (!empty($dados_por_mes)):
                                foreach ($dados_por_mes as $mes):
                                    $height = min(($mes['dispositivos'] / $max_disp) * 100, 100);
                            ?>
                            <div class="bar-column">
                                <div class="bar-vertical" style="height: <?php echo max($height, 8); ?>%;">
                                    <span class="bar-value-top"><?php echo $mes['dispositivos']; ?></span>
                                </div>
                                <span class="bar-month"><?php echo $mes['mes']; ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="bar-column">
                                <div class="bar-vertical" style="height: <?php echo max(($indicadores['total_dispositivos'] / $max_disp) * 100, 8); ?>%;">
                                    <span class="bar-value-top"><?php echo $indicadores['total_dispositivos']; ?></span>
                                </div>
                                <span class="bar-month">Total</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LINHA 3: KPIs principais -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-label">PONTUACAO DE INTEGRIDADE - MEDIA</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['media_integridade'], 1); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">ALERTAS RESOLVIDOS</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_alertas_resolvidos']); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">PATCHES INSTALADOS</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_patches_instalados']); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">AMEACAS DETECTADAS</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_ameacas']); ?></span>
            </div>
        </div>

        <!-- LINHA 4: KPIs secundarios -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-label">BKP COMPLETOS</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_bkp_completo']); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">BKP COM FALHA</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_bkp_falha']); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">ACESSOS REMOTOS</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_acessos_remotos']); ?></span>
            </div>
            <div class="kpi-card">
                <span class="kpi-label">TOTAL CHAMADOS FECHADOS</span>
                <span class="kpi-value small"><?php echo number_format($indicadores['total_chamados_fechados']); ?></span>
            </div>
        </div>

        <!-- GRAFICOS CHART.JS -->
        <div class="charts-section">
            <!-- Distribuicao de Dispositivos -->
            <div class="chart-card-lg">
                <div class="chart-title">Distribuicao de Dispositivos</div>
                <div class="chart-wrapper">
                    <canvas id="dispositivosChart"></canvas>
                </div>
            </div>

            <!-- Status de Backup -->
            <div class="chart-card-lg">
                <div class="chart-title">Status de Backup</div>
                <div class="chart-wrapper">
                    <canvas id="backupChart"></canvas>
                </div>
            </div>

            <!-- Cobertura de Seguranca -->
            <div class="chart-card-lg">
                <div class="chart-title">Cobertura de Seguranca (%)</div>
                <div class="chart-wrapper">
                    <canvas id="coberturaChart"></canvas>
                </div>
            </div>

            <!-- Protecao Web -->
            <div class="chart-card-lg">
                <div class="chart-title">Protecao Web - Bloqueios</div>
                <div class="chart-wrapper">
                    <canvas id="protecaoChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle de Tema
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-theme') === 'dark';

            if (isDark) {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                document.getElementById('icon-sun').style.display = 'block';
                document.getElementById('icon-moon').style.display = 'none';
                document.getElementById('theme-text').textContent = 'Escuro';
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                document.getElementById('icon-sun').style.display = 'none';
                document.getElementById('icon-moon').style.display = 'block';
                document.getElementById('theme-text').textContent = 'Claro';
            }

            updateChartColors();
        }

        // Carregar tema salvo
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.getElementById('icon-sun').style.display = 'none';
                document.getElementById('icon-moon').style.display = 'block';
                document.getElementById('theme-text').textContent = 'Claro';
            }
            initCharts();
        });

        // Cores para graficos
        function getChartColors() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                primary: isDark ? '#6b8cff' : '#1e40af',
                secondary: isDark ? '#5a7fff' : '#3b82f6',
                accent: isDark ? '#60a5fa' : '#60a5fa',
                success: isDark ? '#3fb950' : '#059669',
                warning: isDark ? '#d29922' : '#d97706',
                danger: isDark ? '#f85149' : '#dc2626',
                text: isDark ? '#8b949e' : '#64748b',
                grid: isDark ? '#30363d' : '#e2e8f0'
            };
        }

        let dispositivosChart, backupChart, coberturaChart, protecaoChart;

        function initCharts() {
            const colors = getChartColors();

            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = colors.text;

            // Grafico Dispositivos
            const dispositivosCtx = document.getElementById('dispositivosChart').getContext('2d');
            dispositivosChart = new Chart(dispositivosCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Desktops', 'Notebooks', 'Servidores'],
                    datasets: [{
                        data: [<?php echo $dispositivos['desktops']; ?>, <?php echo $dispositivos['notebooks']; ?>, <?php echo $dispositivos['servidores']; ?>],
                        backgroundColor: [colors.primary, colors.secondary, colors.accent],
                        borderWidth: 2,
                        borderColor: getChartColors().grid
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    cutout: '60%'
                }
            });

            // Grafico Backup
            const backupCtx = document.getElementById('backupChart').getContext('2d');
            backupChart = new Chart(backupCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completo', 'Com Erro', 'Com Falha'],
                    datasets: [{
                        data: [<?php echo $indicadores['total_bkp_completo']; ?>, <?php echo $indicadores['total_bkp_erro']; ?>, <?php echo $indicadores['total_bkp_falha']; ?>],
                        backgroundColor: [colors.success, colors.warning, colors.danger],
                        borderWidth: 2,
                        borderColor: getChartColors().grid
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    cutout: '60%'
                }
            });

            // Grafico Cobertura
            const coberturaCtx = document.getElementById('coberturaChart').getContext('2d');
            coberturaChart = new Chart(coberturaCtx, {
                type: 'bar',
                data: {
                    labels: ['Antivirus', 'Patches', 'Web Protection'],
                    datasets: [{
                        label: 'Cobertura (%)',
                        data: [<?php echo $indicadores['media_antivirus']; ?>, <?php echo $indicadores['media_patches']; ?>, <?php echo $indicadores['media_web_protection']; ?>],
                        backgroundColor: [colors.primary, colors.secondary, colors.accent],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: colors.grid }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // Grafico Protecao Web
            const protecaoCtx = document.getElementById('protecaoChart').getContext('2d');
            protecaoChart = new Chart(protecaoCtx, {
                type: 'bar',
                data: {
                    labels: ['Filtradas', 'Mal-intencionadas'],
                    datasets: [{
                        label: 'Bloqueios',
                        data: [<?php echo $seguranca['total_filtradas']; ?>, <?php echo $seguranca['total_mal_intencionadas']; ?>],
                        backgroundColor: [colors.warning, colors.danger],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: colors.grid }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        function updateChartColors() {
            const colors = getChartColors();

            if (dispositivosChart) {
                dispositivosChart.data.datasets[0].backgroundColor = [colors.primary, colors.secondary, colors.accent];
                dispositivosChart.data.datasets[0].borderColor = colors.grid;
                dispositivosChart.options.plugins.legend.labels = { color: colors.text };
                dispositivosChart.update();
            }

            if (backupChart) {
                backupChart.data.datasets[0].backgroundColor = [colors.success, colors.warning, colors.danger];
                backupChart.data.datasets[0].borderColor = colors.grid;
                backupChart.options.plugins.legend.labels = { color: colors.text };
                backupChart.update();
            }

            if (coberturaChart) {
                coberturaChart.data.datasets[0].backgroundColor = [colors.primary, colors.secondary, colors.accent];
                coberturaChart.options.scales.y.grid.color = colors.grid;
                coberturaChart.options.scales.y.ticks = { color: colors.text };
                coberturaChart.options.scales.x.ticks = { color: colors.text };
                coberturaChart.update();
            }

            if (protecaoChart) {
                protecaoChart.data.datasets[0].backgroundColor = [colors.warning, colors.danger];
                protecaoChart.options.scales.y.grid.color = colors.grid;
                protecaoChart.options.scales.y.ticks = { color: colors.text };
                protecaoChart.options.scales.x.ticks = { color: colors.text };
                protecaoChart.update();
            }
        }
    </script>
</body>
</html>
