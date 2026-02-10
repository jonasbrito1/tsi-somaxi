<?php
// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar a sessão (mantido para compatibilidade)
session_start();

require_once 'includes/db.php';

// Verificar se a coluna 'status' existe na tabela
$coluna_status_existe = false;
try {
    $stmt_check = $pdo->query("SHOW COLUMNS FROM tabela_dados_tsi LIKE 'status'");
    $coluna_status_existe = $stmt_check->rowCount() > 0;
} catch (Exception $e) {
    // Se houver erro na verificação, assumir que não existe
    $coluna_status_existe = false;
}

// Inicializar mensagem de sucesso
$mensagem_sucesso = '';

// Processar ações de arquivamento (apenas se a coluna status existir)
if ($_POST && $coluna_status_existe) {
    try {
        if (isset($_POST['acao_arquivar'])) {
            $acao = $_POST['acao_arquivar'];
            
            if ($acao === 'arquivar_periodo') {
                $mes_arquivar = $_POST['mes_arquivar'] ?? '';
                $ano_arquivar = $_POST['ano_arquivar'] ?? '';
                
                if (!empty($mes_arquivar) && !empty($ano_arquivar)) {
                    $sql_arquivar = "UPDATE tabela_dados_tsi SET status = 'arquivado' WHERE mes = :mes AND ano = :ano AND (status IS NULL OR status = 'ativo')";
                    $stmt_arquivar = $pdo->prepare($sql_arquivar);
                    $stmt_arquivar->execute([':mes' => $mes_arquivar, ':ano' => $ano_arquivar]);
                    $registros_arquivados = $stmt_arquivar->rowCount();
                    $mensagem_sucesso = "{$registros_arquivados} registro(s) de {$mes_arquivar}/{$ano_arquivar} arquivado(s) com sucesso.";
                }
            }

            elseif ($acao === 'restaurar_periodo') {
                $mes_restaurar = $_POST['mes_restaurar'] ?? '';
                $ano_restaurar = $_POST['ano_restaurar'] ?? '';

                if (!empty($mes_restaurar) && !empty($ano_restaurar)) {
                    $sql_restaurar = "UPDATE tabela_dados_tsi SET status = 'ativo' WHERE mes = :mes AND ano = :ano AND status = 'arquivado'";
                    $stmt_restaurar = $pdo->prepare($sql_restaurar);
                    $stmt_restaurar->execute([':mes' => $mes_restaurar, ':ano' => $ano_restaurar]);
                    $registros_restaurados = $stmt_restaurar->rowCount();
                    $mensagem_sucesso = "{$registros_restaurados} registro(s) de {$mes_restaurar}/{$ano_restaurar} restaurado(s).";
                }
            }

            elseif ($acao === 'arquivar_individual') {
                $id_registro = $_POST['id_registro'] ?? '';
                if (!empty($id_registro)) {
                    $sql_arquivar_individual = "UPDATE tabela_dados_tsi SET status = 'arquivado' WHERE id = :id";
                    $stmt_arquivar_individual = $pdo->prepare($sql_arquivar_individual);
                    $stmt_arquivar_individual->execute([':id' => $id_registro]);
                    $mensagem_sucesso = "Registro arquivado com sucesso.";
                }
            }

            elseif ($acao === 'restaurar_individual') {
                $id_registro = $_POST['id_registro'] ?? '';
                if (!empty($id_registro)) {
                    $sql_restaurar_individual = "UPDATE tabela_dados_tsi SET status = 'ativo' WHERE id = :id";
                    $stmt_restaurar_individual = $pdo->prepare($sql_restaurar_individual);
                    $stmt_restaurar_individual->execute([':id' => $id_registro]);
                    $mensagem_sucesso = "Registro restaurado com sucesso.";
                }
            }
        }
    } catch (Exception $e) {
        $mensagem_sucesso = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Inicializar variáveis de filtro
$filtro_tripulante = isset($_GET['tripulante']) ? trim($_GET['tripulante']) : '';
$filtro_mes = isset($_GET['mes']) ? trim($_GET['mes']) : '';
$filtro_ano = isset($_GET['ano']) ? trim($_GET['ano']) : '';
$filtro_status = isset($_GET['status']) ? trim($_GET['status']) : 'ativo';

// Se a coluna status não existe, forçar mostrar todos
if (!$coluna_status_existe) {
    $filtro_status = 'todos';
}

// Obter lista de tripulantes para o datalist
$tripulantes = [];
try {
    $stmt_tripulantes = $pdo->query("SELECT DISTINCT tripulante FROM tabela_dados_tsi WHERE tripulante IS NOT NULL ORDER BY tripulante ASC");
    $tripulantes = $stmt_tripulantes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Em caso de erro, continuar com array vazio
    $tripulantes = [];
}

// Construir a query com filtros
$sql = "SELECT * FROM tabela_dados_tsi WHERE 1=1";
$params = [];

// Filtro de status (apenas se a coluna existir)
if ($coluna_status_existe) {
    if ($filtro_status === 'ativo') {
        $sql .= " AND (status IS NULL OR status = 'ativo')";
    } elseif ($filtro_status === 'arquivado') {
        $sql .= " AND status = 'arquivado'";
    }
    // Se for 'todos', não adiciona filtro de status
}

if (!empty($filtro_tripulante)) {
    $sql .= " AND tripulante LIKE :tripulante";
    $params[':tripulante'] = "%$filtro_tripulante%";
}

if (!empty($filtro_mes)) {
    $sql .= " AND mes = :mes";
    $params[':mes'] = $filtro_mes;
}

if (!empty($filtro_ano)) {
    $sql .= " AND ano = :ano";
    $params[':ano'] = $filtro_ano;
}

$sql .= " ORDER BY created_at DESC";

// Preparar e executar consulta
$registros = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensagem_sucesso = "Erro na consulta: " . $e->getMessage();
    $registros = [];
}

// Obter estatísticas de arquivamento
$stats = ['ativos' => 0, 'arquivados' => 0, 'total' => 0];
try {
    if ($coluna_status_existe) {
        $sql_stats = "SELECT 
            COUNT(CASE WHEN status IS NULL OR status = 'ativo' THEN 1 END) as ativos,
            COUNT(CASE WHEN status = 'arquivado' THEN 1 END) as arquivados,
            COUNT(*) as total
        FROM tabela_dados_tsi";
    } else {
        $sql_stats = "SELECT 
            COUNT(*) as ativos,
            0 as arquivados,
            COUNT(*) as total
        FROM tabela_dados_tsi";
    }
    $stmt_stats = $pdo->query($sql_stats);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Em caso de erro, manter valores padrão
}

// Obter períodos disponíveis para arquivamento
$periodos = [];
try {
    if ($coluna_status_existe) {
        $sql_periodos = "SELECT DISTINCT mes, ano, 
            COUNT(*) as total,
            COUNT(CASE WHEN status IS NULL OR status = 'ativo' THEN 1 END) as ativos,
            COUNT(CASE WHEN status = 'arquivado' THEN 1 END) as arquivados
        FROM tabela_dados_tsi 
        GROUP BY ano, mes 
        ORDER BY ano DESC, 
            CASE mes
                WHEN 'Janeiro' THEN 1 WHEN 'Fevereiro' THEN 2 WHEN 'Março' THEN 3
                WHEN 'Abril' THEN 4 WHEN 'Maio' THEN 5 WHEN 'Junho' THEN 6
                WHEN 'Julho' THEN 7 WHEN 'Agosto' THEN 8 WHEN 'Setembro' THEN 9
                WHEN 'Outubro' THEN 10 WHEN 'Novembro' THEN 11 WHEN 'Dezembro' THEN 12
            END DESC";
    } else {
        $sql_periodos = "SELECT DISTINCT mes, ano, 
            COUNT(*) as total,
            COUNT(*) as ativos,
            0 as arquivados
        FROM tabela_dados_tsi 
        GROUP BY ano, mes 
        ORDER BY ano DESC, 
            CASE mes
                WHEN 'Janeiro' THEN 1 WHEN 'Fevereiro' THEN 2 WHEN 'Março' THEN 3
                WHEN 'Abril' THEN 4 WHEN 'Maio' THEN 5 WHEN 'Junho' THEN 6
                WHEN 'Julho' THEN 7 WHEN 'Agosto' THEN 8 WHEN 'Setembro' THEN 9
                WHEN 'Outubro' THEN 10 WHEN 'Novembro' THEN 11 WHEN 'Dezembro' THEN 12
            END DESC";
    }
    $stmt_periodos = $pdo->query($sql_periodos);
    $periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Em caso de erro, continuar com array vazio
    $periodos = [];
}

// Informações do usuário logado
$username = 'Usuário Público';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Dados - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <style>
        /* Estilos específicos da página de consulta */
        .data-table {
            min-width: 1800px;
        }

        .data-table th {
            text-align: center;
        }

        .col-tripulante {
            text-align: left !important;
            font-weight: 500;
            min-width: 150px;
            position: sticky;
            left: 0;
            background: var(--white);
            z-index: 5;
            color: var(--gray-900);
        }

        .data-table tr:hover .col-tripulante {
            background: var(--gray-50);
        }

        .data-table tr.archived .col-tripulante {
            background: var(--warning-light);
        }

        .col-actions {
            position: sticky;
            right: 0;
            background: var(--white);
            z-index: 5;
            min-width: 100px;
        }

        .data-table tr:hover .col-actions {
            background: var(--gray-50);
        }

        .data-table tr.archived .col-actions {
            background: var(--warning-light);
        }

        .data-table tr.archived {
            background: var(--warning-light);
        }

        .data-table tr.archived:hover {
            background: #fef08a;
        }

        .status-archived, .status-active {
            padding: 0.125rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.6875rem;
            font-weight: 500;
        }

        .status-archived {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-active {
            background: var(--success-light);
            color: var(--success);
        }

        .toggle-icon {
            transition: transform var(--transition-base);
            font-size: 0.75rem;
        }

        .toggle-btn.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .filter-content.collapsed {
            max-height: 0;
            padding: 0 1.5rem;
            opacity: 0;
        }

        .filter-tags {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .message {
            padding: 0.875rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .message.success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .system-alert {
            background: var(--warning-light);
            color: var(--warning);
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            border: 1px solid #fcd34d;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .data-table {
                min-width: 600px;
            }
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Consulta de Dados
                    </h1>
                    <p class="header-subtitle">Visualização e gerenciamento de registros</p>
                </div>
            </div>
            <div class="header-logo">
                <img src="utils/logo_SOMAXI_GROUP_azul.png" alt="SOMAXI GROUP" class="company-logo">
            </div>
        </div>
    </header>

    <!-- NAVEGAÇÃO -->
    <nav class="app-nav">
        <div class="nav-container">
            <div class="nav-links">
                <a href="index.php">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    Dashboard
                </a>
                <a href="form.php">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Novo Registro
                </a>
                <a href="consulta.php" class="active">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Consultar
                </a>
                <a href="relatorios.php">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    Relatórios
                </a>
            </div>
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar">
                        <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="main-content">
        
        <?php if (!empty($mensagem_sucesso)): ?>
        <div class="message <?php echo strpos($mensagem_sucesso, 'Erro') !== false ? 'error' : 'success'; ?>">
            <svg class="icon" viewBox="0 0 24 24">
                <?php if (strpos($mensagem_sucesso, 'Erro') !== false): ?>
                <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>
                <?php else: ?>
                <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                <?php endif; ?>
            </svg>
            <?php echo preg_replace('/[^\p{L}\p{N}\s\.\,\!\?\-\/\(\)]/u', '', $mensagem_sucesso); ?>
        </div>
        <?php endif; ?>

        <?php if (!$coluna_status_existe): ?>
        <div class="system-alert">
            <svg class="icon" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg>
            <div>
                <strong>Sistema de Arquivamento não configurado.</strong>
                Para habilitar as funcionalidades de arquivamento, execute o script SQL fornecido.
            </div>
        </div>
        <?php endif; ?>

        <!-- ESTATÍSTICAS -->
        <section class="stats-grid">
            <div class="stat-card ativos">
                <div class="stat-value"><?php echo number_format($stats['ativos']); ?></div>
                <div class="stat-label">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Registros Ativos
                </div>
            </div>
            <?php if ($coluna_status_existe): ?>
            <div class="stat-card arquivados">
                <div class="stat-value"><?php echo number_format($stats['arquivados']); ?></div>
                <div class="stat-label">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="m21 8-2-3H5L3 8"/><rect x="3" y="8" width="18" height="13" rx="1"/><path d="M10 12h4"/></svg>
                    Arquivados
                </div>
            </div>
            <?php endif; ?>
            <div class="stat-card total">
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">
                    <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    Total
                </div>
            </div>
        </section>

        <!-- FILTROS -->
        <section class="filter-section">
            <div class="filter-header">
                <div class="filter-title">
                    <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary)"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <h3>Filtros</h3>
                </div>
                <button type="button" id="toggleFilters" class="toggle-btn">
                    <span class="toggle-icon">&#9660;</span>
                    <span class="toggle-text">Expandir</span>
                </button>
            </div>

            <div class="filter-content" id="filterContent">
                <form method="GET" action="consulta.php">
                    <div class="filter-grid">
                        <?php if ($coluna_status_existe): ?>
                        <div class="filter-group">
                            <label for="status" class="filter-label">Status</label>
                            <select id="status" name="status" class="filter-select">
                                <option value="ativo" <?php echo ($filtro_status === 'ativo') ? 'selected' : ''; ?>>Apenas Ativos</option>
                                <option value="arquivado" <?php echo ($filtro_status === 'arquivado') ? 'selected' : ''; ?>>Apenas Arquivados</option>
                                <option value="todos" <?php echo ($filtro_status === 'todos') ? 'selected' : ''; ?>>Todos</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="filter-group">
                            <label for="tripulante" class="filter-label">Tripulante</label>
                            <input type="text" id="tripulante" name="tripulante"
                                   class="filter-input"
                                   value="<?php echo htmlspecialchars($filtro_tripulante); ?>"
                                   placeholder="Digite o nome..."
                                   list="tripulantes"
                                   autocomplete="off">
                            <datalist id="tripulantes">
                                <?php foreach ($tripulantes as $tripulante): ?>
                                    <option value="<?php echo htmlspecialchars($tripulante['tripulante']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="filter-group">
                            <label for="mes" class="filter-label">Mês</label>
                            <select id="mes" name="mes" class="filter-select">
                                <option value="">Todos</option>
                                <?php
                                $meses = [
                                    'Janeiro' => '01', 'Fevereiro' => '02', 'Março' => '03',
                                    'Abril' => '04', 'Maio' => '05', 'Junho' => '06',
                                    'Julho' => '07', 'Agosto' => '08', 'Setembro' => '09',
                                    'Outubro' => '10', 'Novembro' => '11', 'Dezembro' => '12'
                                ];
                                foreach ($meses as $mes => $num): ?>
                                    <option value="<?php echo $mes; ?>" <?php echo ($filtro_mes === $mes) ? 'selected' : ''; ?>>
                                        <?php echo $mes; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="ano" class="filter-label">Ano</label>
                            <select id="ano" name="ano" class="filter-select">
                                <option value="">Todos</option>
                                <?php for ($ano = 2024; $ano <= 2030; $ano++): ?>
                                    <option value="<?php echo $ano; ?>" <?php echo ($filtro_ano == $ano) ? 'selected' : ''; ?>>
                                        <?php echo $ano; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Aplicar
                        </button>
                        <a href="consulta.php" class="btn btn-secondary">
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                            Limpar
                        </a>
                        <button type="button" class="btn btn-success" onclick="window.location.href='form.php'">
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            Novo
                        </button>
                    </div>

                    <div class="active-filters" id="activeFilters"></div>
                </form>
            </div>
        </section>

        <!-- TABELA DE DADOS -->
        <section class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                    <?php
                    if ($coluna_status_existe) {
                        if ($filtro_status === 'ativo') echo 'Registros Ativos';
                        elseif ($filtro_status === 'arquivado') echo 'Registros Arquivados';
                        else echo 'Todos os Registros';
                    } else {
                        echo 'Registros do Sistema';
                    }
                    ?>
                </div>
                <div class="results-count">
                    <?php echo count($registros); ?> registro<?php echo count($registros) !== 1 ? 's' : ''; ?>
                </div>
            </div>

            <?php if (count($registros) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if ($coluna_status_existe): ?>
                                <th>Status</th>
                                <?php endif; ?>
                                <th class="col-tripulante">Tripulante</th>
                                <th>Período</th>
                                <th class="group-header" colspan="4">DISPOSITIVOS</th>
                                <th class="group-header" colspan="6">SEGURANÇA</th>
                                <th class="group-header" colspan="3">CHAMADOS</th>
                                <th class="group-header" colspan="4">MONITORAMENTO</th>
                                <th class="group-header" colspan="3">BACKUP</th>
                                <th class="col-actions">Ações</th>
                            </tr>
                            <tr>
                                <?php if ($coluna_status_existe): ?>
                                <th></th>
                                <?php endif; ?>
                                <th class="col-tripulante"></th>
                                <th></th>
                                <!-- Dispositivos -->
                                <th>Total</th>
                                <th>Desktop</th>
                                <th>Notebook</th>
                                <th>Servidor</th>
                                <!-- Segurança -->
                                <th>Alertas OK</th>
                                <th>Ameaças</th>
                                <th>Patches</th>
                                <th>Acesso Remoto</th>
                                <th>Integridade %</th>
                                <th>Falha Logon</th>
                                <!-- Chamados -->
                                <th>Abertos</th>
                                <th>Fechados</th>
                                <th>Proativo</th>
                                <!-- Monitoramento -->
                                <th>Antivirus %</th>
                                <th>Web Protect %</th>
                                <th>Web Filtradas</th>
                                <th>Web Bloqueadas</th>
                                <!-- Backup -->
                                <th>Com Falha</th>
                                <th>Com Erro</th>
                                <th>Completo</th>
                                <th class="col-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $registro): ?>
                            <tr class="<?php echo ($coluna_status_existe && $registro['status'] === 'arquivado') ? 'archived' : ''; ?>">
                                <?php if ($coluna_status_existe): ?>
                                <td>
                                    <?php if (isset($registro['status']) && $registro['status'] === 'arquivado'): ?>
                                        <span class="status-archived">Arquivado</span>
                                    <?php else: ?>
                                        <span class="status-active">Ativo</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                
                                <td class="col-tripulante">
                                    <strong><?php echo htmlspecialchars($registro['tripulante'] ?? ''); ?></strong>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($registro['mes'] ?? ''); ?>/<?php echo htmlspecialchars($registro['ano'] ?? ''); ?>
                                </td>
                                
                                <!-- DISPOSITIVOS -->
                                <td><strong><?php echo htmlspecialchars($registro['total_dispositivos'] ?? 0); ?></strong></td>
                                <td><?php echo htmlspecialchars($registro['tipo_desktop'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($registro['tipo_notebook'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($registro['tipo_servidor'] ?? 0); ?></td>
                                
                                <!-- SEGURANÇA -->
                                <td>
                                    <span class="status-badge status-high">
                                        <?php echo htmlspecialchars($registro['alertas_resolvidos'] ?? 0); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php 
                                        $ameacas = $registro['ameacas_detectadas'] ?? 0;
                                        if ($ameacas == 0) echo 'status-high';
                                        elseif ($ameacas <= 5) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($ameacas); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($registro['patches_instalados'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($registro['acessos_remotos'] ?? 0); ?></td>
                                <td>
                                    <span class="status-badge <?php 
                                        $integridade = $registro['pontuacao_integridade'] ?? 0;
                                        if ($integridade >= 90) echo 'status-high';
                                        elseif ($integridade >= 70) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($integridade); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php 
                                        $falhas = $registro['falha_logon'] ?? 0;
                                        if ($falhas == 0) echo 'status-high';
                                        elseif ($falhas <= 3) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($falhas); ?>
                                    </span>
                                </td>
                                
                                <!-- CHAMADOS -->
                                <td>
                                    <span class="status-badge status-medium">
                                        <?php echo htmlspecialchars($registro['num_chamados_abertos'] ?? 0); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-high">
                                        <?php echo htmlspecialchars($registro['num_chamados_fechados'] ?? 0); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($registro['monitoramento_proativo'] ?? 0); ?></td>
                                
                                <!-- MONITORAMENTO -->
                                <td>
                                    <span class="status-badge <?php 
                                        $antivirus = $registro['cobertura_antivirus'] ?? 0;
                                        if ($antivirus >= 95) echo 'status-high';
                                        elseif ($antivirus >= 85) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($antivirus); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php 
                                        $web_protection = $registro['cobertura_web_protection'] ?? 0;
                                        if ($web_protection >= 95) echo 'status-high';
                                        elseif ($web_protection >= 85) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($web_protection); ?>%
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($registro['web_protection_filtradas_bloqueadas'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($registro['web_protection_mal_intencionadas_bloqueadas'] ?? 0); ?></td>
                                
                                <!-- BACKUP -->
                                <td>
                                    <span class="status-badge <?php 
                                        $bkp_falha = $registro['bkp_com_falha'] ?? 0;
                                        if ($bkp_falha == 0) echo 'status-high';
                                        elseif ($bkp_falha <= 2) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($bkp_falha); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php 
                                        $bkp_erro = $registro['bkp_com_erro'] ?? 0;
                                        if ($bkp_erro == 0) echo 'status-high';
                                        elseif ($bkp_erro <= 2) echo 'status-medium';
                                        else echo 'status-low';
                                    ?>">
                                        <?php echo htmlspecialchars($bkp_erro); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-high">
                                        <?php echo htmlspecialchars($registro['bkp_completo'] ?? 0); ?>
                                    </span>
                                </td>
                                
                                <!-- AÇÕES -->
                                <td class="col-actions">
                                    <div style="display: flex; gap: 0.375rem; justify-content: center;">
                                        <a href="editar.php?id=<?php echo $registro['id']; ?>" class="btn btn-ghost btn-sm" title="Editar">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                        </a>
                                        <a href="deletar.php?id=<?php echo $registro['id']; ?>"
                                           class="btn btn-ghost btn-sm" style="color: var(--danger);"
                                           title="Excluir"
                                           onclick="return confirm('ATENÇÃO: Esta ação irá excluir permanentemente o registro.\n\nTripulante: <?php echo htmlspecialchars($registro['tripulante'] ?? ''); ?>\nPeríodo: <?php echo htmlspecialchars($registro['mes'] ?? ''); ?>/<?php echo htmlspecialchars($registro['ano'] ?? ''); ?>\n\nDeseja continuar?')">
                                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <div class="empty-state-title">Nenhum registro encontrado</div>
                    <div class="empty-state-text">Ajuste os filtros ou adicione um novo registro.</div>
                    <a href="form.php" class="btn btn-primary">
                        <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Novo Registro
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- LEGENDA -->
        <section class="legend">
            <div class="legend-title">Legenda</div>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="status-badge status-high">OK</span>
                    <span>Valores ideais</span>
                </div>
                <div class="legend-item">
                    <span class="status-badge status-medium">Alerta</span>
                    <span>Requer atenção</span>
                </div>
                <div class="legend-item">
                    <span class="status-badge status-low">Crítico</span>
                    <span>Ação necessária</span>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Auto-focus no campo de pesquisa se estiver vazio
        document.addEventListener('DOMContentLoaded', function() {
            const campoPesquisa = document.getElementById('tripulante');
            if (campoPesquisa && !campoPesquisa.value) {
                campoPesquisa.focus();
            }

            // Animação de contadores
            setTimeout(animateCounters, 300);
        });

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + F para focar no campo de pesquisa
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('tripulante').focus();
            }
            
            // Escape para limpar filtros
            if (e.key === 'Escape') {
                window.location.href = 'consulta.php';
            }
        });

        // Animação de contadores
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-value');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
                if (target > 0) {
                    let current = 0;
                    const increment = Math.max(1, target / 30);
                    
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.textContent = target.toLocaleString();
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.floor(current).toLocaleString();
                        }
                    }, 50);
                }
            });
        }

        // Destacar texto pesquisado
        function destacarTextoPesquisa() {
            const filtroTripulante = '<?php echo htmlspecialchars($filtro_tripulante); ?>';
            if (filtroTripulante) {
                const celulas = document.querySelectorAll('.col-tripulante strong');
                celulas.forEach(celula => {
                    const texto = celula.textContent;
                    const regex = new RegExp(`(${filtroTripulante})`, 'gi');
                    celula.innerHTML = texto.replace(regex, '<mark style="background: #fef3c7; padding: 0.125rem;">$1</mark>');
                });
            }
        }

        // Executar destaque após carregamento
        document.addEventListener('DOMContentLoaded', destacarTextoPesquisa);

        // Adicionar informações de tooltip
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar tooltips para explicar as colunas
            const headers = document.querySelectorAll('th');
            const tooltips = {
                'Total': 'Número total de dispositivos gerenciados',
                'Desktop': 'Quantidade de computadores desktop',
                'Notebook': 'Quantidade de notebooks/laptops',
                'Servidor': 'Quantidade de servidores',
                'Alertas OK': 'Alertas de segurança resolvidos',
                'Ameaças': 'Ameaças de segurança detectadas',
                'Patches': 'Patches de segurança instalados',
                'Integridade %': 'Percentual de integridade do sistema',
                'Abertos': 'Chamados técnicos em aberto',
                'Fechados': 'Chamados técnicos resolvidos',
                'Antivirus %': 'Cobertura do antivirus',
                'Web Protect %': 'Cobertura da proteção web',
                'Com Falha': 'Backups que falharam',
                'Com Erro': 'Backups com erros',
                'Completo': 'Backups executados com sucesso'
            };

            headers.forEach(header => {
                const text = header.textContent.trim();
                if (tooltips[text]) {
                    header.title = tooltips[text];
                    header.style.cursor = 'help';
                }
            });
        });

        // === FUNCIONALIDADES MODERNIZADAS DOS FILTROS ===
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleFilters');
            const filterContent = document.getElementById('filterContent');
            const toggleIcon = toggleButton.querySelector('.toggle-icon');
            const toggleText = toggleButton.querySelector('.toggle-text');

            // Função para alternar filtros
            toggleButton.addEventListener('click', function() {
                const isCollapsed = filterContent.classList.contains('collapsed');
                
                if (isCollapsed) {
                    filterContent.classList.remove('collapsed');
                    toggleButton.classList.remove('collapsed');
                    toggleText.textContent = 'Recolher';
                } else {
                    filterContent.classList.add('collapsed');
                    toggleButton.classList.add('collapsed');
                    toggleText.textContent = 'Expandir';
                }
            });

            // Mostrar filtros ativos
            function displayActiveFilters() {
                const activeFiltersContainer = document.getElementById('activeFilters');
                const urlParams = new URLSearchParams(window.location.search);
                
                let activeFilters = [];
                
                // Verificar cada filtro
                const tripulante = urlParams.get('tripulante');
                const mes = urlParams.get('mes');
                const ano = urlParams.get('ano');
                const status = urlParams.get('status');

                if (tripulante) {
                    activeFilters.push({ label: 'Tripulante', value: tripulante, param: 'tripulante' });
                }

                if (mes) {
                    activeFilters.push({ label: 'Mês', value: mes, param: 'mes' });
                }

                if (ano) {
                    activeFilters.push({ label: 'Ano', value: ano, param: 'ano' });
                }

                if (status && status !== 'ativo') {
                    const statusLabels = { 'arquivado': 'Arquivados', 'todos': 'Todos' };
                    activeFilters.push({ label: 'Status', value: statusLabels[status] || status, param: 'status' });
                }

                // Renderizar filtros ativos
                if (activeFilters.length > 0) {
                    activeFiltersContainer.style.display = 'flex';
                    activeFiltersContainer.innerHTML = activeFilters.map(filter => `
                        <div class="filter-tag">
                            ${filter.label}: ${filter.value}
                            <span class="filter-tag-remove" onclick="removeFilter('${filter.param}')">&times;</span>
                        </div>
                    `).join('');
                } else {
                    activeFiltersContainer.style.display = 'none';
                }
            }
            
            // Função global para remover filtro
            window.removeFilter = function(param) {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.delete(param);
                
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.location.href = newUrl;
            };

            // Inicializar filtros ativos
            displayActiveFilters();

            // Auto-expandir filtros se houver filtros ativos
            const urlParams = new URLSearchParams(window.location.search);
            const hasActiveFilters = urlParams.get('tripulante') || urlParams.get('mes') || 
                                   urlParams.get('ano') || (urlParams.get('status') && urlParams.get('status') !== 'ativo');
            
            if (hasActiveFilters) {
                filterContent.classList.remove('collapsed');
                toggleButton.classList.remove('collapsed');
                toggleText.textContent = 'Recolher';
            } else {
                filterContent.classList.add('collapsed');
                toggleButton.classList.add('collapsed');
                toggleText.textContent = 'Expandir';
            }

            // Melhorar experiência do usuário nos campos de entrada
            const filterInputs = document.querySelectorAll('.filter-input, .filter-select');
            filterInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });

            // Atalho de teclado para expandir/recolher filtros (Ctrl + Shift + F)
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && e.key === 'F') {
                    e.preventDefault();
                    toggleButton.click();
                }
            });

            // === MELHORIAS DE EXPERIÊNCIA DA TABELA ===
            
            // Destacar coluna ao passar o mouse
            const tableHeaders = document.querySelectorAll('.data-table th');
            const tableCells = document.querySelectorAll('.data-table td');
            
            tableHeaders.forEach((header, index) => {
                header.addEventListener('mouseenter', function() {
                    // Destacar toda a coluna
                    const columnCells = document.querySelectorAll(`.data-table tr td:nth-child(${index + 1})`);
                    columnCells.forEach(cell => {
                        cell.style.background = 'rgba(59, 130, 246, 0.1)';
                    });
                });
                
                header.addEventListener('mouseleave', function() {
                    // Remover destaque da coluna
                    const columnCells = document.querySelectorAll(`.data-table tr td:nth-child(${index + 1})`);
                    columnCells.forEach(cell => {
                        cell.style.background = '';
                    });
                });
            });

            // Tooltip para status badges
            const statusBadges = document.querySelectorAll('.status-badge');
            statusBadges.forEach(badge => {
                if (badge.classList.contains('status-high')) {
                    badge.title = 'Valor dentro do esperado';
                } else if (badge.classList.contains('status-medium')) {
                    badge.title = 'Requer atenção';
                } else if (badge.classList.contains('status-low')) {
                    badge.title = 'Valor crítico';
                }
            });

            // Smooth scroll para tabela quando há muitos dados
            const tableContainer = document.querySelector('.table-container');
            if (tableContainer) {
                tableContainer.style.scrollBehavior = 'smooth';
            }

            // Animação de entrada para as linhas da tabela
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'slideInFromLeft 0.5s ease-out';
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observar linhas da tabela para animação
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 50}ms`;
                observer.observe(row);
            });
        });
    </script>
</body>
</html>
