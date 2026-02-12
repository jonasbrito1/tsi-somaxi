<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Proteger página - requer login
require_login();

$user = get_logged_user();

// Filtros gerais
$filtro_tripulante = isset($_GET['tripulante']) ? trim($_GET['tripulante']) : '';
$filtro_mes = isset($_GET['mes']) ? trim($_GET['mes']) : '';
$filtro_ano = isset($_GET['ano']) ? trim($_GET['ano']) : '';

// Filtros específicos por card
$filtro_mes_tripulantes = isset($_GET['mes_tripulantes']) ? trim($_GET['mes_tripulantes']) : '';
$filtro_ano_tripulantes = isset($_GET['ano_tripulantes']) ? trim($_GET['ano_tripulantes']) : '';

$filtro_mes_dispositivos = isset($_GET['mes_dispositivos']) ? trim($_GET['mes_dispositivos']) : '';
$filtro_ano_dispositivos = isset($_GET['ano_dispositivos']) ? trim($_GET['ano_dispositivos']) : '';

$filtro_mes_distribuicao = isset($_GET['mes_distribuicao']) ? trim($_GET['mes_distribuicao']) : '';
$filtro_ano_distribuicao = isset($_GET['ano_distribuicao']) ? trim($_GET['ano_distribuicao']) : '';

$filtro_mes_backup = isset($_GET['mes_backup']) ? trim($_GET['mes_backup']) : '';
$filtro_ano_backup = isset($_GET['ano_backup']) ? trim($_GET['ano_backup']) : '';

$filtro_mes_cobertura = isset($_GET['mes_cobertura']) ? trim($_GET['mes_cobertura']) : '';
$filtro_ano_cobertura = isset($_GET['ano_cobertura']) ? trim($_GET['ano_cobertura']) : '';

$filtro_mes_protecao = isset($_GET['mes_protecao']) ? trim($_GET['mes_protecao']) : '';
$filtro_ano_protecao = isset($_GET['ano_protecao']) ? trim($_GET['ano_protecao']) : '';

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
    // Mês vigente (atual)
    $mes_atual = date('n'); // 1-12
    $meses_nomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $mes_vigente_nome = $meses_nomes[$mes_atual];
    $ano_atual = date('Y');

    // Buscar o mês mais recente com dados (usado como fallback)
    $sql_mes_recente_global = "SELECT mes, ano FROM tabela_dados_tsi
                                GROUP BY mes, ano
                                HAVING COUNT(*) > 0
                                ORDER BY ano DESC, FIELD(mes, 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                                         'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro') DESC
                                LIMIT 1";
    $stmt_recente_global = $pdo->query($sql_mes_recente_global);
    $mes_recente_com_dados = $stmt_recente_global->fetch(PDO::FETCH_ASSOC);

    // Definir fallback padrão
    if ($mes_recente_com_dados) {
        $mes_fallback = $mes_recente_com_dados['mes'];
        $ano_fallback = $mes_recente_com_dados['ano'];
    } else {
        $mes_fallback = $mes_vigente_nome;
        $ano_fallback = $ano_atual;
    }

    // Função helper para determinar mês/ano de cada card
    function determinar_mes_ano_card($filtro_mes_especifico, $filtro_ano_especifico, $filtro_mes_geral, $filtro_ano_geral, $pdo, $mes_fallback, $ano_fallback) {
        // PRIORIDADE: Filtro específico do card > Filtro geral > Mês mais recente com dados

        $mes_card = '';
        $ano_card = '';

        // 1. Verificar filtro específico do card
        if (!empty($filtro_mes_especifico) || !empty($filtro_ano_especifico)) {
            $mes_card = !empty($filtro_mes_especifico) ? $filtro_mes_especifico : $mes_fallback;
            $ano_card = !empty($filtro_ano_especifico) ? $filtro_ano_especifico : $ano_fallback;
        }
        // 2. Verificar filtro geral
        elseif (!empty($filtro_mes_geral) || !empty($filtro_ano_geral)) {
            $mes_card = !empty($filtro_mes_geral) ? $filtro_mes_geral : $mes_fallback;
            $ano_card = !empty($filtro_ano_geral) ? $filtro_ano_geral : $ano_fallback;
        }
        // 3. Usar mês mais recente com dados (já calculado globalmente)
        else {
            $mes_card = $mes_fallback;
            $ano_card = $ano_fallback;
        }

        return ['mes' => $mes_card, 'ano' => $ano_card];
    }

    // Determinar mês/ano para cada card individualmente
    $periodo_tripulantes = determinar_mes_ano_card($filtro_mes_tripulantes, $filtro_ano_tripulantes, $filtro_mes, $filtro_ano, $pdo, $mes_fallback, $ano_fallback);
    $periodo_dispositivos = determinar_mes_ano_card($filtro_mes_dispositivos, $filtro_ano_dispositivos, $filtro_mes, $filtro_ano, $pdo, $mes_fallback, $ano_fallback);
    $periodo_distribuicao = determinar_mes_ano_card($filtro_mes_distribuicao, $filtro_ano_distribuicao, $filtro_mes, $filtro_ano, $pdo, $mes_fallback, $ano_fallback);
    $periodo_backup = determinar_mes_ano_card($filtro_mes_backup, $filtro_ano_backup, $filtro_mes, $filtro_ano, $pdo, $mes_fallback, $ano_fallback);
    $periodo_cobertura = determinar_mes_ano_card($filtro_mes_cobertura, $filtro_ano_cobertura, $filtro_mes, $filtro_ano, $pdo, $mes_fallback, $ano_fallback);
    $periodo_protecao = determinar_mes_ano_card($filtro_mes_protecao, $filtro_ano_protecao, $filtro_mes, $filtro_ano, $pdo, $mes_fallback, $ano_fallback);

    // Manter compatibilidade com código existente (usa período de tripulantes como padrão)
    $mes_para_exibir = $periodo_tripulantes['mes'];
    $ano_para_exibir = $periodo_tripulantes['ano'];

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

    // Dados de TRIPULANTES do mês específico
    $sql_tripulantes_mes = "SELECT
        COUNT(DISTINCT tripulante) as tripulantes_mes
    FROM tabela_dados_tsi
    WHERE mes = :mes_vigente AND ano = :ano_vigente";

    $stmt_trip_mes = $pdo->prepare($sql_tripulantes_mes);
    $stmt_trip_mes->execute([':mes_vigente' => $periodo_tripulantes['mes'], ':ano_vigente' => $periodo_tripulantes['ano']]);
    $dados_tripulantes_mes = $stmt_trip_mes->fetch(PDO::FETCH_ASSOC);

    if (!$dados_tripulantes_mes || $dados_tripulantes_mes['tripulantes_mes'] === null) {
        $dados_tripulantes_mes = ['tripulantes_mes' => 0];
    }

    // Dados de DISPOSITIVOS do mês específico
    $sql_disp_total_mes = "SELECT
        SUM(tipo_desktop + tipo_notebook + tipo_servidor) as dispositivos_mes
    FROM tabela_dados_tsi
    WHERE mes = :mes_vigente AND ano = :ano_vigente";

    $stmt_disp_total_mes = $pdo->prepare($sql_disp_total_mes);
    $stmt_disp_total_mes->execute([':mes_vigente' => $periodo_dispositivos['mes'], ':ano_vigente' => $periodo_dispositivos['ano']]);
    $dados_dispositivos_total_mes = $stmt_disp_total_mes->fetch(PDO::FETCH_ASSOC);

    if (!$dados_dispositivos_total_mes || $dados_dispositivos_total_mes['dispositivos_mes'] === null) {
        $dados_dispositivos_total_mes = ['dispositivos_mes' => 0];
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

    // Distribuicao de Dispositivos DO MÊS ESPECÍFICO (para card com mês/ano)
    $sql_dispositivos_mes = "SELECT
        SUM(tipo_desktop) as desktops_mes,
        SUM(tipo_notebook) as notebooks_mes,
        SUM(tipo_servidor) as servidores_mes
    FROM tabela_dados_tsi
    WHERE mes = :mes_vigente AND ano = :ano_vigente";

    $stmt_disp_mes = $pdo->prepare($sql_dispositivos_mes);
    $stmt_disp_mes->execute([':mes_vigente' => $periodo_distribuicao['mes'], ':ano_vigente' => $periodo_distribuicao['ano']]);
    $dispositivos_mes = $stmt_disp_mes->fetch(PDO::FETCH_ASSOC);

    foreach ($dispositivos_mes as $key => $value) {
        if ($value === null) $dispositivos_mes[$key] = 0;
    }

    // Dados por mes
    $sql_por_mes = "SELECT
        mes,
        COUNT(DISTINCT tripulante) as tripulantes,
        SUM(total_dispositivos) as dispositivos
    FROM tabela_dados_tsi $where_clause
    GROUP BY mes
    ORDER BY FIELD(mes, 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
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

    // Dados de Backup DO MÊS ESPECÍFICO (para card com mês/ano)
    $sql_backup_mes = "SELECT
        SUM(bkp_completo) as bkp_completo_mes,
        SUM(bkp_com_erro) as bkp_erro_mes,
        SUM(bkp_com_falha) as bkp_falha_mes
    FROM tabela_dados_tsi
    WHERE mes = :mes_vigente AND ano = :ano_vigente";

    $stmt_backup_mes = $pdo->prepare($sql_backup_mes);
    $stmt_backup_mes->execute([':mes_vigente' => $periodo_backup['mes'], ':ano_vigente' => $periodo_backup['ano']]);
    $backup_mes = $stmt_backup_mes->fetch(PDO::FETCH_ASSOC);

    foreach ($backup_mes as $key => $value) {
        if ($value === null) $backup_mes[$key] = 0;
    }

    // Dados de Cobertura de Segurança DO MÊS ESPECÍFICO (para card com mês/ano)
    $sql_cobertura_mes = "SELECT
        ROUND(AVG(cobertura_antivirus), 1) as antivirus_mes,
        ROUND(AVG(cobertura_atualizacao_patches), 1) as patches_mes,
        ROUND(AVG(cobertura_web_protection), 1) as web_protection_mes
    FROM tabela_dados_tsi
    WHERE mes = :mes_vigente AND ano = :ano_vigente";

    $stmt_cobertura_mes = $pdo->prepare($sql_cobertura_mes);
    $stmt_cobertura_mes->execute([':mes_vigente' => $periodo_cobertura['mes'], ':ano_vigente' => $periodo_cobertura['ano']]);
    $cobertura_mes = $stmt_cobertura_mes->fetch(PDO::FETCH_ASSOC);

    foreach ($cobertura_mes as $key => $value) {
        if ($value === null) $cobertura_mes[$key] = 0;
    }

    // Dados de Proteção Web DO MÊS ESPECÍFICO (para card com mês/ano)
    $sql_protecao_mes = "SELECT
        SUM(web_protection_filtradas_bloqueadas) as filtradas_mes,
        SUM(web_protection_mal_intencionadas_bloqueadas) as mal_intencionadas_mes
    FROM tabela_dados_tsi
    WHERE mes = :mes_vigente AND ano = :ano_vigente";

    $stmt_protecao_mes = $pdo->prepare($sql_protecao_mes);
    $stmt_protecao_mes->execute([':mes_vigente' => $periodo_protecao['mes'], ':ano_vigente' => $periodo_protecao['ano']]);
    $protecao_mes = $stmt_protecao_mes->fetch(PDO::FETCH_ASSOC);

    foreach ($protecao_mes as $key => $value) {
        if ($value === null) $protecao_mes[$key] = 0;
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
    <title>Dashboard - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/theme.js"></script>
    <script src="js/user-dropdown.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
</head>

<body>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                        Dashboard TSI
                    </h1>
                    <p class="header-subtitle">Monitoramento de Seguranca e Performance</p>
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
                <a href="index.php" class="active">
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
                <a href="relatorios.php">
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

        <!-- SECAO DE FILTROS MODERNA -->
        <section class="card" style="margin-bottom: 2rem;">
            <div class="card-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <div class="card-title" style="color: white; display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                            <svg class="icon icon-lg" viewBox="0 0 24 24" style="color: white;">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size: var(--font-size-xl); font-weight: 700; margin: 0;">Filtros de Análise</h2>
                            <p style="font-size: var(--font-size-sm); opacity: 0.9; margin: 0.25rem 0 0 0;">Personalize a visualização dos dados</p>
                        </div>
                    </div>
                    <button type="button" id="toggleDashboardFilters" class="toggle-btn" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.5rem 1rem; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 600; transition: all 0.2s;">
                        <span class="toggle-icon" style="font-size: 0.875rem;">▼</span>
                        <span class="toggle-text">Expandir</span>
                    </button>
                </div>
            </div>

            <div class="dashboard-filter-content collapsed" id="dashboardFilterContent" style="padding: 0 2rem; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out, padding 0.3s ease-out;">
                <form method="GET" action="index.php" style="padding: 2rem 0;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">

                        <!-- Filtro por Tripulante -->
                        <div class="filter-group">
                            <label for="tripulante" class="filter-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem; font-size: var(--font-size-sm);">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Tripulante
                            </label>
                            <input type="text"
                                   id="tripulante"
                                   name="tripulante"
                                   class="filter-input"
                                   style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s;"
                                   value="<?php echo htmlspecialchars($filtro_tripulante); ?>"
                                   placeholder="Nome do tripulante..."
                                   list="tripulantesList">
                            <datalist id="tripulantesList">
                                <?php
                                try {
                                    $stmt_trip = $pdo->query("SELECT DISTINCT tripulante FROM tabela_dados_tsi WHERE tripulante IS NOT NULL ORDER BY tripulante");
                                    while ($row = $stmt_trip->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . htmlspecialchars($row['tripulante']) . '">';
                                    }
                                } catch (Exception $e) {}
                                ?>
                            </datalist>
                        </div>

                        <!-- Filtro por Mês -->
                        <div class="filter-group">
                            <label for="mes" class="filter-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem; font-size: var(--font-size-sm);">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Mês
                            </label>
                            <select id="mes"
                                    name="mes"
                                    class="filter-select"
                                    style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s; cursor: pointer; background: white;">
                                <option value="">Todos os meses</option>
                                <?php
                                $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                foreach ($meses as $mes) {
                                    $selected = ($filtro_mes === $mes) ? 'selected' : '';
                                    echo "<option value=\"$mes\" $selected>$mes</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Filtro por Ano -->
                        <div class="filter-group">
                            <label for="ano" class="filter-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem; font-size: var(--font-size-sm);">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                                </svg>
                                Ano
                            </label>
                            <select id="ano"
                                    name="ano"
                                    class="filter-select"
                                    style="width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s; cursor: pointer; background: white;">
                                <option value="">Todos os anos</option>
                                <?php for ($ano = 2024; $ano <= 2030; $ano++):
                                    $selected = ($filtro_ano == $ano) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $ano; ?>" <?php echo $selected; ?>><?php echo $ano; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                    </div>

                    <!-- Botoes de Acao -->
                    <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1rem; border-top: 2px solid var(--gray-100); flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: var(--font-size-base); font-weight: 600; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.2s;">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            Aplicar Filtros
                        </button>
                        <a href="index.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; font-size: var(--font-size-base); font-weight: 600; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; text-decoration: none;">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                <path d="M21 3v5h-5"/>
                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                <path d="M8 16H3v5"/>
                            </svg>
                            Limpar Filtros
                        </a>
                        <a href="consulta.php" class="btn btn-success" style="padding: 0.75rem 1.5rem; font-size: var(--font-size-base); font-weight: 600; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; text-decoration: none;">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            Ver Detalhes
                        </a>
                    </div>

                    <!-- Tags de Filtros Ativos -->
                    <?php if (!empty($filtro_tripulante) || !empty($filtro_mes) || !empty($filtro_ano)): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                        <div style="font-size: var(--font-size-sm); font-weight: 600; color: var(--gray-700); margin-bottom: 0.75rem;">
                            Filtros Ativos:
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if (!empty($filtro_tripulante)): ?>
                            <div style="background: var(--primary-light); color: var(--primary); padding: 0.5rem 1rem; border-radius: var(--radius-md); font-size: var(--font-size-sm); font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Tripulante: <?php echo htmlspecialchars($filtro_tripulante); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($filtro_mes)): ?>
                            <div style="background: var(--success-light); color: var(--success); padding: 0.5rem 1rem; border-radius: var(--radius-md); font-size: var(--font-size-sm); font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Mês: <?php echo htmlspecialchars($filtro_mes); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($filtro_ano)): ?>
                            <div style="background: var(--warning-light); color: var(--warning); padding: 0.5rem 1rem; border-radius: var(--radius-md); font-size: var(--font-size-sm); font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <path d="M8 14h.01M12 14h.01M16 14h.01"/>
                                </svg>
                                Ano: <?php echo htmlspecialchars($filtro_ano); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <style>
            .filter-input:focus, .filter-select:focus {
                outline: none;
                border-color: var(--primary) !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            .toggle-btn:hover {
                background: rgba(255,255,255,0.3) !important;
            }

            .dashboard-filter-content {
                transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            }

            .dashboard-filter-content.collapsed {
                max-height: 0 !important;
                padding: 0 2rem !important;
            }

            .dashboard-filter-content:not(.collapsed) {
                max-height: 1000px !important;
                padding: 2rem !important;
            }
        </style>

        <!-- INDICADORES PRINCIPAIS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
                    <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Tripulantes</div>
                    <div class="stat-value"><?php echo number_format($indicadores['total_tripulantes'], 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success) 0%, #047857 100%);">
                    <svg class="icon icon-lg" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Dispositivos</div>
                    <div class="stat-value"><?php echo number_format($indicadores['total_dispositivos'], 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #b45309 100%);">
                    <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Ameacas Detectadas</div>
                    <div class="stat-value"><?php echo number_format($indicadores['total_ameacas'], 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #0369a1 100%);">
                    <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Integridade Media</div>
                    <div class="stat-value"><?php echo number_format($indicadores['media_integridade'], 1, ',', '.'); ?>%</div>
                </div>
            </div>
        </div>

        <!-- GRID DE GRAFICOS -->
        <div class="charts-grid">
            <!-- Grafico: Tripulantes por Mes -->
            <div class="card chart-card" data-chart-type="tripulantes" onclick="openChartModal('tripulantes')">
                <div class="card-header">
                    <div class="card-title">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        Tripulantes - <?php echo $periodo_tripulantes['mes'] . '/' . $periodo_tripulantes['ano']; ?>
                        <span class="expand-hint">Clique para expandir</span>
                    </div>
                </div>
                <div class="card-body" style="position: relative;">
                    <canvas id="tripulantesMesChart" height="320"></canvas>
                    <div id="tripulantesTotal" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.95); padding: 12px 20px; border-radius: 12px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); pointer-events: none; text-align: center;"></div>
                </div>
            </div>

            <!-- Grafico: Dispositivos por Mes -->
            <div class="card chart-card" data-chart-type="dispositivos" onclick="openChartModal('dispositivos')">
                <div class="card-header">
                    <div class="card-title">
                        <svg class="icon" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        Dispositivos - <?php echo $periodo_dispositivos['mes'] . '/' . $periodo_dispositivos['ano']; ?>
                        <span class="expand-hint">Clique para expandir</span>
                    </div>
                </div>
                <div class="card-body" style="position: relative;">
                    <canvas id="dispositivosMesChart" height="320"></canvas>
                    <div id="dispositivosMesTotal" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.95); padding: 12px 20px; border-radius: 12px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); pointer-events: none; text-align: center;"></div>
                </div>
            </div>

            <!-- Grafico: Distribuicao de Dispositivos -->
            <div class="card chart-card" data-chart-type="distribuicao" onclick="openChartModal('distribuicao')">
                <div class="card-header">
                    <div class="card-title">
                        <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Distribuição de Dispositivos - <?php echo $periodo_distribuicao['mes'] . '/' . $periodo_distribuicao['ano']; ?>
                        <span class="expand-hint">Clique para expandir</span>
                    </div>
                </div>
                <div class="card-body" style="position: relative;">
                    <canvas id="dispositivosChart" height="320"></canvas>
                    <div id="dispositivosDistribuicaoTotal" style="position: absolute; top: 38%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; text-align: center;"></div>
                </div>
            </div>

            <!-- Grafico: Status de Backup -->
            <div class="card chart-card" data-chart-type="backup" onclick="openChartModal('backup')">
                <div class="card-header">
                    <div class="card-title">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Status de Backup - <?php echo $periodo_backup['mes'] . '/' . $periodo_backup['ano']; ?>
                        <span class="expand-hint">Clique para expandir</span>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="backupChart" height="250"></canvas>
                </div>
            </div>

            <!-- Grafico: Cobertura de Seguranca -->
            <div class="card chart-card" data-chart-type="cobertura" onclick="openChartModal('cobertura')">
                <div class="card-header">
                    <div class="card-title">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Cobertura de Segurança - <?php echo $periodo_cobertura['mes'] . '/' . $periodo_cobertura['ano']; ?>
                        <span class="expand-hint">Clique para expandir</span>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="coberturaChart" height="250"></canvas>
                </div>
            </div>

            <!-- Grafico: Protecao Web -->
            <div class="card chart-card" data-chart-type="protecao" onclick="openChartModal('protecao')">
                <div class="card-header">
                    <div class="card-title">
                        <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Proteção Web - Bloqueios - <?php echo $periodo_protecao['mes'] . '/' . $periodo_protecao['ano']; ?>
                        <span class="expand-hint">Clique para expandir</span>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="protecaoChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- MÉTRICAS ADICIONAIS -->
        <div class="section-header" style="margin-bottom: 1.5rem;">
            <h2 style="font-size: var(--font-size-xl); font-weight: 700; color: var(--gray-900); display: flex; align-items: center; gap: 0.75rem;">
                <svg class="icon icon-lg" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Métricas Operacionais
            </h2>
        </div>

        <div class="metrics-grid">
            <!-- Card 1: Alertas Resolvidos -->
            <div class="card" style="border-left: 4px solid var(--success);">
                <div class="card-body" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                    <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--success) 0%, #059669 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="icon" style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: var(--font-size-sm); font-weight: 500; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Alertas Resolvidos</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900);"><?php echo number_format($indicadores['total_alertas_resolvidos'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Patches Instalados -->
            <div class="card" style="border-left: 4px solid var(--primary);">
                <div class="card-body" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                    <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="icon" style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: var(--font-size-sm); font-weight: 500; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Patches Instalados</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900);"><?php echo number_format($indicadores['total_patches_instalados'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Acessos Remotos -->
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <div class="card-body" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                    <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="icon" style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: var(--font-size-sm); font-weight: 500; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Acessos Remotos</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900);"><?php echo number_format($indicadores['total_acessos_remotos'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Backups Completos -->
            <div class="card" style="border-left: 4px solid var(--info);">
                <div class="card-body" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                    <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--info) 0%, #0369a1 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="icon" style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: var(--font-size-sm); font-weight: 500; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Backups Completos</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900);"><?php echo number_format($indicadores['total_bkp_completo'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Card 5: Backups com Falha -->
            <div class="card" style="border-left: 4px solid var(--danger);">
                <div class="card-body" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                    <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--danger) 0%, #b91c1c 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="icon" style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: var(--font-size-sm); font-weight: 500; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Backups com Falha</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--danger);"><?php echo number_format($indicadores['total_bkp_falha'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Card 6: Chamados Fechados -->
            <div class="card" style="border-left: 4px solid var(--warning);">
                <div class="card-body" style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem;">
                    <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg class="icon" style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: var(--font-size-sm); font-weight: 500; color: var(--gray-500); margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Chamados Fechados</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900);"><?php echo number_format($indicadores['total_chamados_fechados'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- MODAL PARA DETALHES DOS GRAFICOS -->
    <div id="chartModal" class="modal-overlay" onclick="closeChartModal(event)">
        <div class="modal-container" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title"></h2>
                <button onclick="closeChartModal()" class="modal-close">
                    <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-filters" id="modalFilters">
                <!-- Filtros individuais do modal -->
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Conteúdo dinâmico será inserido aqui -->
            </div>
        </div>
    </div>

    <style>
        /* Estilos para cards clicáveis */
        .chart-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .expand-hint {
            font-size: 0.75rem;
            color: var(--gray-500);
            font-weight: 400;
            margin-left: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chart-card:hover .expand-hint {
            opacity: 1;
        }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* Modal Container */
        .modal-container {
            background: #ffffff;
            border-radius: var(--radius-xl);
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease;
        }

        /* Dark mode support */
        [data-theme="dark"] .modal-container {
            background: #1f2937;
        }

        /* Modal Header */
        .modal-header {
            padding: 2rem;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            color: white;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* Modal Filters */
        .modal-filters {
            padding: 1.5rem 2rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        [data-theme="dark"] .modal-filters {
            background: #374151;
            border-bottom-color: #4b5563;
        }

        .modal-filter-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .modal-filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
        }

        .modal-filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .modal-filter-select {
            padding: 0.625rem 1rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: white;
            color: var(--gray-900);
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-filter-select:hover {
            border-color: var(--primary);
        }

        .modal-filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        [data-theme="dark"] .modal-filter-select {
            background: #1f2937;
            color: #e5e7eb;
            border-color: #4b5563;
        }

        .modal-filter-btn {
            padding: 0.625rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .modal-filter-btn:active {
            transform: translateY(0);
        }

        /* Modal Body */
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            max-height: calc(90vh - 100px);
        }

        /* Detalhes Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary);
        }

        [data-theme="dark"] .detail-item {
            background: #374151;
        }

        .detail-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        [data-theme="dark"] .detail-label {
            color: #9ca3af;
        }

        .detail-value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }

        [data-theme="dark"] .detail-value {
            color: #f3f4f6;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .modal-container {
                max-width: 100%;
                max-height: 100vh;
                border-radius: 0;
            }

            .modal-header {
                padding: 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .modal-filters {
                padding: 1rem 1.5rem;
            }

            .modal-filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .modal-filter-group {
                width: 100%;
            }

            .modal-filter-btn {
                width: 100%;
                justify-content: center;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .chart-card:hover {
                transform: translateY(-2px);
            }

            .expand-hint {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .charts-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 1.75rem;
            }

            .card-title {
                font-size: 0.875rem;
            }
        }
    </style>

    <script>
        // ==========================================
        // FUNÇÃO DE FORMATAÇÃO BRASILEIRA
        // ==========================================
        function formatarNumeroBR(numero) {
            if (numero === null || numero === undefined) return '0';
            return new Intl.NumberFormat('de-DE', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(numero);
        }

        // ==========================================
        // FUNÇÕES DO MODAL
        // ==========================================
        function openChartModal(chartType) {
            const modal = document.getElementById('chartModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            const modalFilters = document.getElementById('modalFilters');

            // Dados do PHP e períodos específicos de cada card
            const dadosPHP = {
                tripulantes_mes: <?php echo isset($dados_tripulantes_mes['tripulantes_mes']) ? $dados_tripulantes_mes['tripulantes_mes'] : 0; ?>,
                dispositivos_mes: <?php echo isset($dados_dispositivos_total_mes['dispositivos_mes']) ? $dados_dispositivos_total_mes['dispositivos_mes'] : 0; ?>,
                total_tripulantes: <?php echo isset($indicadores['total_tripulantes']) ? $indicadores['total_tripulantes'] : 0; ?>,
                total_dispositivos: <?php echo isset($indicadores['total_dispositivos']) ? $indicadores['total_dispositivos'] : 0; ?>,
                desktops: <?php echo isset($dispositivos_mes['desktops_mes']) ? $dispositivos_mes['desktops_mes'] : 0; ?>,
                notebooks: <?php echo isset($dispositivos_mes['notebooks_mes']) ? $dispositivos_mes['notebooks_mes'] : 0; ?>,
                servidores: <?php echo isset($dispositivos_mes['servidores_mes']) ? $dispositivos_mes['servidores_mes'] : 0; ?>,
                bkp_completo: <?php echo isset($backup_mes['bkp_completo_mes']) ? $backup_mes['bkp_completo_mes'] : 0; ?>,
                bkp_erro: <?php echo isset($backup_mes['bkp_erro_mes']) ? $backup_mes['bkp_erro_mes'] : 0; ?>,
                bkp_falha: <?php echo isset($backup_mes['bkp_falha_mes']) ? $backup_mes['bkp_falha_mes'] : 0; ?>,
                media_antivirus: <?php echo isset($cobertura_mes['antivirus_mes']) ? $cobertura_mes['antivirus_mes'] : 0; ?>,
                media_patches: <?php echo isset($cobertura_mes['patches_mes']) ? $cobertura_mes['patches_mes'] : 0; ?>,
                media_web_protection: <?php echo isset($cobertura_mes['web_protection_mes']) ? $cobertura_mes['web_protection_mes'] : 0; ?>,
                total_filtradas: <?php echo isset($protecao_mes['filtradas_mes']) ? $protecao_mes['filtradas_mes'] : 0; ?>,
                total_mal_intencionadas: <?php echo isset($protecao_mes['mal_intencionadas_mes']) ? $protecao_mes['mal_intencionadas_mes'] : 0; ?>,
                // Períodos específicos por card
                periodos: {
                    tripulantes: { mes: '<?php echo isset($periodo_tripulantes['mes']) ? $periodo_tripulantes['mes'] : 'Janeiro'; ?>', ano: '<?php echo isset($periodo_tripulantes['ano']) ? $periodo_tripulantes['ano'] : date('Y'); ?>' },
                    dispositivos: { mes: '<?php echo isset($periodo_dispositivos['mes']) ? $periodo_dispositivos['mes'] : 'Janeiro'; ?>', ano: '<?php echo isset($periodo_dispositivos['ano']) ? $periodo_dispositivos['ano'] : date('Y'); ?>' },
                    distribuicao: { mes: '<?php echo isset($periodo_distribuicao['mes']) ? $periodo_distribuicao['mes'] : 'Janeiro'; ?>', ano: '<?php echo isset($periodo_distribuicao['ano']) ? $periodo_distribuicao['ano'] : date('Y'); ?>' },
                    backup: { mes: '<?php echo isset($periodo_backup['mes']) ? $periodo_backup['mes'] : 'Janeiro'; ?>', ano: '<?php echo isset($periodo_backup['ano']) ? $periodo_backup['ano'] : date('Y'); ?>' },
                    cobertura: { mes: '<?php echo isset($periodo_cobertura['mes']) ? $periodo_cobertura['mes'] : 'Janeiro'; ?>', ano: '<?php echo isset($periodo_cobertura['ano']) ? $periodo_cobertura['ano'] : date('Y'); ?>' },
                    protecao: { mes: '<?php echo isset($periodo_protecao['mes']) ? $periodo_protecao['mes'] : 'Janeiro'; ?>', ano: '<?php echo isset($periodo_protecao['ano']) ? $periodo_protecao['ano'] : date('Y'); ?>' }
                }
            };

            console.log('Dados PHP:', dadosPHP);

            // Criar filtros do modal com período específico do card
            const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let i = currentYear - 2; i <= currentYear + 1; i++) {
                years.push(i);
            }

            // Obter o período específico deste card
            const periodoCard = dadosPHP.periodos[chartType];

            if (!periodoCard) {
                console.error('Período não encontrado para o card:', chartType);
                return;
            }

            let filtersHTML = `
                <form class="modal-filter-form" onsubmit="applyModalFilter(event, '${chartType}')">
                    <div class="modal-filter-group">
                        <label class="modal-filter-label">Mês</label>
                        <select name="mes" class="modal-filter-select" id="modalFilterMes">
                            <option value="">Todos os meses</option>
                            ${meses.map((mes, index) => `<option value="${mes}" ${mes === periodoCard.mes ? 'selected' : ''}>${mes}</option>`).join('')}
                        </select>
                    </div>
                    <div class="modal-filter-group">
                        <label class="modal-filter-label">Ano</label>
                        <select name="ano" class="modal-filter-select" id="modalFilterAno">
                            <option value="">Todos os anos</option>
                            ${years.map(ano => `<option value="${ano}" ${ano == periodoCard.ano ? 'selected' : ''}>${ano}</option>`).join('')}
                        </select>
                    </div>
                    <div class="modal-filter-group">
                        <button type="submit" class="modal-filter-btn">
                            <svg class="icon icon-sm" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            Aplicar Filtro
                        </button>
                    </div>
                </form>
            `;

            modalFilters.innerHTML = filtersHTML;

            let content = '';

            switch(chartType) {
                case 'tripulantes':
                    modalTitle.textContent = `Tripulantes - ${periodoCard.mes}/${periodoCard.ano}`;
                    content = `
                        <div class="details-grid">
                            <div class="detail-item" style="border-left-color: var(--primary);">
                                <div class="detail-label">Tripulantes no Mês</div>
                                <div class="detail-value" style="color: var(--primary);">${formatarNumeroBR(dadosPHP.tripulantes_mes)}</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--success);">
                                <div class="detail-label">Total Geral</div>
                                <div class="detail-value" style="color: var(--success);">${formatarNumeroBR(dadosPHP.total_tripulantes)}</div>
                            </div>
                        </div>
                        <p style="color: var(--gray-600); font-size: 0.875rem;">
                            <strong>Nota:</strong> Os dados mostram os tripulantes registrados em ${periodoCard.mes}/${periodoCard.ano}.
                            Para visualizar dados de outros períodos, use os filtros acima.
                        </p>
                    `;
                    break;

                case 'dispositivos':
                    modalTitle.textContent = `Dispositivos - ${periodoCard.mes}/${periodoCard.ano}`;
                    content = `
                        <div class="details-grid">
                            <div class="detail-item" style="border-left-color: var(--success);">
                                <div class="detail-label">Dispositivos no Mês</div>
                                <div class="detail-value" style="color: var(--success);">${formatarNumeroBR(dadosPHP.dispositivos_mes)}</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--primary);">
                                <div class="detail-label">Total Geral</div>
                                <div class="detail-value" style="color: var(--primary);">${formatarNumeroBR(dadosPHP.total_dispositivos)}</div>
                            </div>
                        </div>
                        <p style="color: var(--gray-600); font-size: 0.875rem;">
                            <strong>Nota:</strong> Dispositivos monitorados em ${periodoCard.mes}/${periodoCard.ano}.
                        </p>
                    `;
                    break;

                case 'distribuicao':
                    modalTitle.textContent = `Distribuição de Dispositivos - ${periodoCard.mes}/${periodoCard.ano}`;
                    const totalDist = dadosPHP.desktops + dadosPHP.notebooks + dadosPHP.servidores;
                    const percDesktop = ((dadosPHP.desktops / totalDist) * 100).toFixed(1);
                    const percNotebook = ((dadosPHP.notebooks / totalDist) * 100).toFixed(1);
                    const percServidor = ((dadosPHP.servidores / totalDist) * 100).toFixed(1);

                    content = `
                        <div class="details-grid">
                            <div class="detail-item" style="border-left-color: var(--primary);">
                                <div class="detail-label">Desktops</div>
                                <div class="detail-value" style="color: var(--primary);">${formatarNumeroBR(dadosPHP.desktops)}</div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">${percDesktop}% do total</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--info);">
                                <div class="detail-label">Notebooks</div>
                                <div class="detail-value" style="color: var(--info);">${formatarNumeroBR(dadosPHP.notebooks)}</div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">${percNotebook}% do total</div>
                            </div>
                            <div class="detail-item" style="border-left-color: #8b5cf6;">
                                <div class="detail-label">Servidores</div>
                                <div class="detail-value" style="color: #8b5cf6;">${formatarNumeroBR(dadosPHP.servidores)}</div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">${percServidor}% do total</div>
                            </div>
                        </div>
                    `;
                    break;

                case 'backup':
                    modalTitle.textContent = `Status de Backup - ${periodoCard.mes}/${periodoCard.ano}`;
                    const totalBkp = dadosPHP.bkp_completo + dadosPHP.bkp_erro + dadosPHP.bkp_falha;
                    const percCompleto = ((dadosPHP.bkp_completo / totalBkp) * 100).toFixed(1);
                    const percErro = ((dadosPHP.bkp_erro / totalBkp) * 100).toFixed(1);
                    const percFalha = ((dadosPHP.bkp_falha / totalBkp) * 100).toFixed(1);

                    content = `
                        <div class="details-grid">
                            <div class="detail-item" style="border-left-color: var(--success);">
                                <div class="detail-label">Backups Completos</div>
                                <div class="detail-value" style="color: var(--success);">${formatarNumeroBR(dadosPHP.bkp_completo)}</div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">${percCompleto}% do total</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--warning);">
                                <div class="detail-label">Backups com Erro</div>
                                <div class="detail-value" style="color: var(--warning);">${formatarNumeroBR(dadosPHP.bkp_erro)}</div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">${percErro}% do total</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--danger);">
                                <div class="detail-label">Backups com Falha</div>
                                <div class="detail-value" style="color: var(--danger);">${formatarNumeroBR(dadosPHP.bkp_falha)}</div>
                                <div style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">${percFalha}% do total</div>
                            </div>
                        </div>
                    `;
                    break;

                case 'cobertura':
                    modalTitle.textContent = `Cobertura de Segurança - ${periodoCard.mes}/${periodoCard.ano}`;
                    content = `
                        <div class="details-grid">
                            <div class="detail-item" style="border-left-color: var(--primary);">
                                <div class="detail-label">Antivírus</div>
                                <div class="detail-value" style="color: var(--primary);">${dadosPHP.media_antivirus}%</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--info);">
                                <div class="detail-label">Patches</div>
                                <div class="detail-value" style="color: var(--info);">${dadosPHP.media_patches}%</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--success);">
                                <div class="detail-label">Web Protection</div>
                                <div class="detail-value" style="color: var(--success);">${dadosPHP.media_web_protection}%</div>
                            </div>
                        </div>
                        <p style="color: var(--gray-600); font-size: 0.875rem;">
                            <strong>Nota:</strong> Valores representam a cobertura média dos sistemas de segurança.
                        </p>
                    `;
                    break;

                case 'protecao':
                    modalTitle.textContent = `Proteção Web - Bloqueios - ${periodoCard.mes}/${periodoCard.ano}`;
                    const totalBloqueios = dadosPHP.total_filtradas + dadosPHP.total_mal_intencionadas;
                    content = `
                        <div class="details-grid">
                            <div class="detail-item" style="border-left-color: var(--warning);">
                                <div class="detail-label">Páginas Filtradas</div>
                                <div class="detail-value" style="color: var(--warning);">${formatarNumeroBR(dadosPHP.total_filtradas)}</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--danger);">
                                <div class="detail-label">Mal-intencionadas</div>
                                <div class="detail-value" style="color: var(--danger);">${formatarNumeroBR(dadosPHP.total_mal_intencionadas)}</div>
                            </div>
                            <div class="detail-item" style="border-left-color: var(--info);">
                                <div class="detail-label">Total Bloqueios</div>
                                <div class="detail-value" style="color: var(--info);">${formatarNumeroBR(totalBloqueios)}</div>
                            </div>
                        </div>
                        <p style="color: var(--gray-600); font-size: 0.875rem;">
                            <strong>Nota:</strong> Número total de acessos bloqueados pela proteção web.
                        </p>
                    `;
                    break;
            }

            modalBody.innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeChartModal(event) {
            if (!event || event.target === event.currentTarget) {
                const modal = document.getElementById('chartModal');
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        }

        // Aplicar filtro do modal (específico para cada card)
        function applyModalFilter(event, chartType) {
            event.preventDefault();

            const form = event.target;
            const mes = form.querySelector('[name="mes"]').value;
            const ano = form.querySelector('[name="ano"]').value;

            // Construir URL com os filtros
            const params = new URLSearchParams(window.location.search);

            // Definir o nome do parâmetro baseado no tipo de card
            const mesParam = `mes_${chartType}`;
            const anoParam = `ano_${chartType}`;

            // Aplicar filtros específicos do card
            if (mes) {
                params.set(mesParam, mes);
            } else {
                params.delete(mesParam);
            }

            if (ano) {
                params.set(anoParam, ano);
            } else {
                params.delete(anoParam);
            }

            // Redirecionar para a mesma página com os novos filtros
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeChartModal();
            }
        });

        // ==========================================
        // CONFIGURACAO DOS GRAFICOS
        // ==========================================

        // Configurar o plugin datalabels globalmente (desabilitado por padrão)
        Chart.register(ChartDataLabels);
        Chart.defaults.set('plugins.datalabels', {
            display: false
        });

        // Funcao para obter cores do tema atual
        function getThemeColors() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                primary: isDark ? '#60a5fa' : '#3b82f6',
                primaryDark: isDark ? '#3b82f6' : '#1e40af',
                success: isDark ? '#34d399' : '#10b981',
                warning: isDark ? '#fbbf24' : '#f59e0b',
                danger: isDark ? '#f87171' : '#ef4444',
                info: isDark ? '#22d3ee' : '#06b6d4',
                purple: isDark ? '#a78bfa' : '#8b5cf6',
                gray: isDark ? '#9ca3af' : '#6b7280',
                text: isDark ? '#e5e7eb' : '#1f2937',
                gridColor: isDark ? '#374151' : '#e5e7eb',
                background: isDark ? '#1f2937' : '#ffffff'
            };
        }

        // Escutar mudanças de tema
        window.addEventListener('themechange', function() {
            updateChartColors();
        });

        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.plugins.legend.position = 'bottom';

        // Referências dos gráficos
        let tripulantesMesChart, dispositivosMesChart, dispositivosChart, backupChart, coberturaChart, protecaoChart;

        // Cores do tema
        const colors = getThemeColors();

        // Dados dos meses
        const mesesLabels = <?php echo json_encode(array_column($dados_por_mes, 'mes')); ?>;
        const mesesTripulantes = <?php echo json_encode(array_column($dados_por_mes, 'tripulantes')); ?>;
        const mesesDispositivos = <?php echo json_encode(array_column($dados_por_mes, 'dispositivos')); ?>;

        // ==========================================
        // PLUGIN PARA MOSTRAR TOTAIS
        // ==========================================

        // Calcular e exibir total de Tripulantes do Mês Específico
        const totalTripulantesMes = <?php echo $dados_tripulantes_mes['tripulantes_mes']; ?>;

        document.getElementById('tripulantesTotal').innerHTML =
            '<div style="font-size: 11px; color: var(--gray-600); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $periodo_tripulantes["mes"] . "/" . $periodo_tripulantes["ano"]; ?></div>' +
            '<div style="font-size: 28px; color: var(--primary); font-weight: 700; line-height: 1;">' + formatarNumeroBR(totalTripulantesMes) + '</div>' +
            '<div style="font-size: 11px; color: var(--gray-500); margin-top: 2px;">Tripulantes</div>';

        // Grafico: Tripulantes por Mes (mostra apenas mês específico do card)
        tripulantesMesChart = new Chart(document.getElementById('tripulantesMesChart'), {
            type: 'line',
            data: {
                labels: ['<?php echo $periodo_tripulantes["mes"]; ?>'],
                datasets: [{
                    label: 'Tripulantes',
                    data: [<?php echo $dados_tripulantes_mes['tripulantes_mes']; ?>],
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 8,
                    pointHoverRadius: 10,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const value = context.parsed.y;
                                return 'Tripulantes: ' + formatarNumeroBR(value);
                            }
                        }
                    },
                    datalabels: {
                        display: false  // Desabilitar para evitar sobreposição
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                return formatarNumeroBR(value);
                            }
                        }
                    }
                }
            }
        });

        // Calcular e exibir total de Dispositivos do Mês Específico
        const totalDispositivosMesVigente = <?php echo $dados_dispositivos_total_mes['dispositivos_mes']; ?>;

        document.getElementById('dispositivosMesTotal').innerHTML =
            '<div style="font-size: 11px; color: var(--gray-600); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $periodo_dispositivos["mes"] . "/" . $periodo_dispositivos["ano"]; ?></div>' +
            '<div style="font-size: 28px; color: var(--success); font-weight: 700; line-height: 1;">' + formatarNumeroBR(totalDispositivosMesVigente) + '</div>' +
            '<div style="font-size: 11px; color: var(--gray-500); margin-top: 2px;">Dispositivos</div>';

        // Grafico: Dispositivos por Mes (mostra apenas mês específico do card)
        dispositivosMesChart = new Chart(document.getElementById('dispositivosMesChart'), {
            type: 'bar',
            data: {
                labels: ['<?php echo $periodo_dispositivos["mes"]; ?>'],
                datasets: [{
                    label: 'Dispositivos',
                    data: [<?php echo $dados_dispositivos_total_mes['dispositivos_mes']; ?>],
                    backgroundColor: colors.success,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const value = context.parsed.y;
                                return 'Dispositivos: ' + formatarNumeroBR(value);
                            }
                        }
                    },
                    datalabels: {
                        display: false  // Desabilitar para evitar sobreposição
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            callback: function(value) {
                                return formatarNumeroBR(value);
                            }
                        }
                    }
                }
            }
        });

        // Calcular e exibir total no centro do gráfico de Distribuição (DO MÊS)
        const totalDispositivosDistribuicao = <?php echo $dispositivos_mes['desktops_mes'] + $dispositivos_mes['notebooks_mes'] + $dispositivos_mes['servidores_mes']; ?>;

        document.getElementById('dispositivosDistribuicaoTotal').innerHTML =
            '<div style="font-size: 14px; color: var(--gray-600); font-weight: 600; margin-bottom: 4px;"><?php echo $periodo_distribuicao["mes"] . "/" . $periodo_distribuicao["ano"]; ?></div>' +
            '<div style="font-size: 36px; color: var(--primary); font-weight: 700; line-height: 1;">' + formatarNumeroBR(totalDispositivosDistribuicao) + '</div>' +
            '<div style="font-size: 12px; color: var(--gray-500); margin-top: 4px;">Dispositivos</div>';

        // Grafico: Distribuicao de Dispositivos (DO MÊS)
        dispositivosChart = new Chart(document.getElementById('dispositivosChart'), {
            type: 'doughnut',
            data: {
                labels: [
                    'Desktops (<?php echo $dispositivos_mes['desktops_mes']; ?>)',
                    'Notebooks (<?php echo $dispositivos_mes['notebooks_mes']; ?>)',
                    'Servidores (<?php echo $dispositivos_mes['servidores_mes']; ?>)'
                ],
                datasets: [{
                    data: [
                        <?php echo $dispositivos_mes['desktops_mes']; ?>,
                        <?php echo $dispositivos_mes['notebooks_mes']; ?>,
                        <?php echo $dispositivos_mes['servidores_mes']; ?>
                    ],
                    backgroundColor: [colors.primary, colors.info, colors.purple],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14,
                                weight: 600
                            },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 14,
                            boxHeight: 14,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const dataset = data.datasets[0];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);

                                    return data.labels.map((label, i) => {
                                        const value = dataset.data[i];
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                                        return {
                                            text: label.split(' (')[0] + ' - ' + value + ' (' + percentage + '%)',
                                            fillStyle: dataset.backgroundColor[i],
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.85)',
                        padding: 14,
                        titleFont: { size: 15, weight: 'bold' },
                        bodyFont: { size: 14 },
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return 'Quantidade: ' + formatarNumeroBR(value) + ' (' + percentage + '%)';
                            }
                        }
                    },
                    datalabels: {
                        display: false  // Desabilitar para evitar sobreposição com o total no centro
                    }
                }
            }
        });

        // Grafico: Status de Backup
        backupChart = new Chart(document.getElementById('backupChart'), {
            type: 'doughnut',
            data: {
                labels: [
                    'Completo (<?php echo $backup_mes['bkp_completo_mes']; ?>)',
                    'Com Erro (<?php echo $backup_mes['bkp_erro_mes']; ?>)',
                    'Com Falha (<?php echo $backup_mes['bkp_falha_mes']; ?>)'
                ],
                datasets: [{
                    data: [
                        <?php echo $backup_mes['bkp_completo_mes']; ?>,
                        <?php echo $backup_mes['bkp_erro_mes']; ?>,
                        <?php echo $backup_mes['bkp_falha_mes']; ?>
                    ],
                    backgroundColor: [colors.success, colors.warning, colors.danger],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 500
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + formatarNumeroBR(value) + ' (' + percentage + '%)';
                            }
                        }
                    },
                    datalabels: {
                        display: function(context) {
                            // Só exibir se o valor for maior que 0
                            return context.dataset.data[context.dataIndex] > 0;
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: function(value, context) {
                            return formatarNumeroBR(value);
                        },
                        anchor: 'center',
                        align: 'center',
                        offset: 0,
                        padding: 6
                    }
                }
            }
        });

        // Grafico: Cobertura de Seguranca
        coberturaChart = new Chart(document.getElementById('coberturaChart'), {
            type: 'bar',
            data: {
                labels: ['Antivírus', 'Patches', 'Web Protection'],
                datasets: [{
                    label: 'Cobertura (%)',
                    data: [
                        <?php echo $cobertura_mes['antivirus_mes']; ?>,
                        <?php echo $cobertura_mes['patches_mes']; ?>,
                        <?php echo $cobertura_mes['web_protection_mes']; ?>
                    ],
                    backgroundColor: [colors.primary, colors.info, colors.success],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    },
                    datalabels: {
                        display: false  // Desabilitar para melhor visualização
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Grafico: Protecao Web
        protecaoChart = new Chart(document.getElementById('protecaoChart'), {
            type: 'bar',
            data: {
                labels: ['Páginas Filtradas', 'Mal-intencionadas'],
                datasets: [{
                    label: 'Bloqueios',
                    data: [
                        <?php echo $protecao_mes['filtradas_mes']; ?>,
                        <?php echo $protecao_mes['mal_intencionadas_mes']; ?>
                    ],
                    backgroundColor: [colors.warning, colors.danger],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatarNumeroBR(context.parsed.y);
                            }
                        }
                    },
                    datalabels: {
                        display: false  // Desabilitar para melhor visualização
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatarNumeroBR(value);
                            }
                        }
                    }
                }
            }
        });

        // ==========================================
        // FUNCAO PARA ATUALIZAR CORES DOS GRAFICOS
        // ==========================================
        function updateChartColors() {
            const newColors = getThemeColors();

            // Atualizar Chart.js defaults
            Chart.defaults.color = newColors.text;

            // Atualizar Tripulantes por Mes
            if (tripulantesMesChart) {
                tripulantesMesChart.data.datasets[0].borderColor = newColors.primary;
                tripulantesMesChart.data.datasets[0].backgroundColor = newColors.primary + '20';
                tripulantesMesChart.data.datasets[0].pointBackgroundColor = newColors.primary;
                tripulantesMesChart.options.scales.y.grid = { color: newColors.gridColor };
                tripulantesMesChart.options.scales.x.grid = { color: newColors.gridColor };
                tripulantesMesChart.options.scales.y.ticks = { color: newColors.text };
                tripulantesMesChart.options.scales.x.ticks = { color: newColors.text };
                tripulantesMesChart.options.plugins.legend.labels = { color: newColors.text };
                tripulantesMesChart.options.plugins.datalabels.color = newColors.text;
                tripulantesMesChart.update();
            }

            // Atualizar Dispositivos por Mes
            if (dispositivosMesChart) {
                dispositivosMesChart.data.datasets[0].backgroundColor = newColors.success;
                dispositivosMesChart.options.scales.y.grid = { color: newColors.gridColor };
                dispositivosMesChart.options.scales.x.grid = { color: newColors.gridColor };
                dispositivosMesChart.options.scales.y.ticks = { color: newColors.text };
                dispositivosMesChart.options.scales.x.ticks = { color: newColors.text };
                dispositivosMesChart.options.plugins.legend.labels = { color: newColors.text };
                // Datalabels é sempre branco nas barras para contraste
                dispositivosMesChart.update();
            }

            // Atualizar Distribuicao de Dispositivos
            if (dispositivosChart) {
                dispositivosChart.data.datasets[0].backgroundColor = [newColors.primary, newColors.info, newColors.purple];
                dispositivosChart.options.plugins.legend.labels = { color: newColors.text };
                dispositivosChart.update();
            }

            // Atualizar Status de Backup
            if (backupChart) {
                backupChart.data.datasets[0].backgroundColor = [newColors.success, newColors.warning, newColors.danger];
                backupChart.options.plugins.legend.labels = { color: newColors.text };
                backupChart.update();
            }

            // Atualizar Cobertura de Seguranca
            if (coberturaChart) {
                coberturaChart.data.datasets[0].backgroundColor = [newColors.primary, newColors.info, newColors.success];
                coberturaChart.options.scales.y.grid = { color: newColors.gridColor };
                coberturaChart.options.scales.x.grid = { color: newColors.gridColor };
                coberturaChart.options.scales.y.ticks = { color: newColors.text };
                coberturaChart.options.scales.x.ticks = { color: newColors.text };
                coberturaChart.options.plugins.legend.labels = { color: newColors.text };
                // Datalabels é sempre branco nas barras para contraste
                coberturaChart.update();
            }

            // Atualizar Protecao Web
            if (protecaoChart) {
                protecaoChart.data.datasets[0].backgroundColor = [newColors.warning, newColors.danger];
                protecaoChart.options.scales.y.grid = { color: newColors.gridColor };
                protecaoChart.options.scales.x.grid = { color: newColors.gridColor };
                protecaoChart.options.scales.y.ticks = { color: newColors.text };
                protecaoChart.options.scales.x.ticks = { color: newColors.text };
                protecaoChart.options.plugins.legend.labels = { color: newColors.text };
                // Datalabels é sempre branco nas barras para contraste
                protecaoChart.update();
            }
        }

        // === CONTROLE DOS FILTROS DO DASHBOARD ===
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleDashboardFilters');
            const filterContent = document.getElementById('dashboardFilterContent');
            const toggleIcon = toggleButton?.querySelector('.toggle-icon');
            const toggleText = toggleButton?.querySelector('.toggle-text');

            if (!toggleButton || !filterContent) return;

            // Verificar se há filtros ativos
            const urlParams = new URLSearchParams(window.location.search);
            const hasActiveFilters = urlParams.get('tripulante') || urlParams.get('mes') || urlParams.get('ano');

            // Auto-expandir se houver filtros ativos
            if (hasActiveFilters) {
                filterContent.classList.remove('collapsed');
                toggleButton.classList.remove('collapsed');
                if (toggleText) toggleText.textContent = 'Recolher';
            }

            // Alternar visibilidade dos filtros
            toggleButton.addEventListener('click', function() {
                const isCollapsed = filterContent.classList.contains('collapsed');

                if (isCollapsed) {
                    filterContent.classList.remove('collapsed');
                    toggleButton.classList.remove('collapsed');
                    if (toggleText) toggleText.textContent = 'Recolher';
                } else {
                    filterContent.classList.add('collapsed');
                    toggleButton.classList.add('collapsed');
                    if (toggleText) toggleText.textContent = 'Expandir';
                }
            });

            // Animação suave nos inputs
            const filterInputs = document.querySelectorAll('.filter-input, .filter-select');
            filterInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--primary)';
                    this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                });

                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.borderColor = 'var(--gray-300)';
                        this.style.boxShadow = 'none';
                    }
                });
            });

            // Atalhos de teclado
            document.addEventListener('keydown', function(e) {
                // Ctrl + Shift + F para expandir/recolher filtros
                if (e.ctrlKey && e.shiftKey && e.key === 'F') {
                    e.preventDefault();
                    toggleButton.click();
                }

                // Escape para limpar filtros (quando filtros estão expandidos)
                if (e.key === 'Escape' && !filterContent.classList.contains('collapsed')) {
                    if (hasActiveFilters && confirm('Deseja limpar todos os filtros?')) {
                        window.location.href = 'index.php';
                    }
                }
            });

            // Animação nos botões
            const filterButtons = document.querySelectorAll('.card .btn');
            filterButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Loading state ao aplicar filtros
            const filterForm = filterContent.querySelector('form');
            if (filterForm) {
                filterForm.addEventListener('submit', function() {
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = `
                            <svg class="icon icon-sm" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                            </svg>
                            Aplicando...
                        `;
                    }
                });
            }

            // Auto-focus no primeiro campo vazio ao expandir
            toggleButton.addEventListener('click', function() {
                setTimeout(() => {
                    if (!filterContent.classList.contains('collapsed')) {
                        const firstEmptyInput = Array.from(filterInputs).find(input => !input.value);
                        if (firstEmptyInput) {
                            firstEmptyInput.focus();
                        }
                    }
                }, 350);
            });
        });

        // Animação de loading para os cartões
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s both`;
            });
        });

        // Adicionar animação fadeInUp
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            .btn:hover {
                transform: translateY(-2px) !important;
            }

            .btn:active {
                transform: translateY(0) !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
