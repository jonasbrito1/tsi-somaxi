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

// Obter o ID do registro a ser editado
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$registro = null;
$erro = '';

// Preservar filtros para retorno
$query_params = [];
if (isset($_GET['tripulante'])) $query_params['tripulante'] = $_GET['tripulante'];
if (isset($_GET['mes'])) $query_params['mes'] = $_GET['mes'];
if (isset($_GET['ano'])) $query_params['ano'] = $_GET['ano'];
if (isset($_GET['status'])) $query_params['status'] = $_GET['status'];
if (isset($_GET['pagina'])) $query_params['pagina'] = $_GET['pagina'];
$query_string_return = !empty($query_params) ? '?' . http_build_query($query_params) : '';
$query_string_form = !empty($query_params) ? '&' . http_build_query($query_params) : '';

if ($id > 0) {
    // Buscar dados para edição
    try {
        $sql = "SELECT * FROM tabela_dados_tsi WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            $erro = 'Registro não encontrado.';
        }
    } catch (Exception $e) {
        $erro = 'Erro ao buscar registro: ' . $e->getMessage();
    }
} else {
    $erro = 'ID inválido.';
}

// Obter lista de tripulantes para o select
$tripulantes = [];
try {
    $stmt_tripulantes = $pdo->query("SELECT DISTINCT tripulante FROM tabela_dados_tsi WHERE tripulante IS NOT NULL ORDER BY tripulante ASC");
    $tripulantes = $stmt_tripulantes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Em caso de erro, continuar com array vazio
    $tripulantes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Dados - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/theme.js"></script>
    <script src="js/user-dropdown.js"></script>
    <style>
        /* Melhorias visuais da página de edição */
        .edit-progress {
            position: sticky;
            top: 0;
            z-index: 100;
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .progress-bar-container {
            width: 100%;
            height: 6px;
            background: var(--gray-200);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }

        .section-nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .section-nav-btn {
            padding: 0.5rem 1rem;
            background: var(--gray-100);
            border: 2px solid transparent;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s;
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        .section-nav-btn:hover {
            background: var(--gray-200);
        }

        .section-nav-btn.active {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .section-nav-btn.completed {
            background: var(--success-light);
            color: var(--success);
        }

        .form-section {
            scroll-margin-top: 150px;
            padding: 2rem;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .form-group.required .form-label::after {
            content: ' *';
            color: var(--danger);
            font-weight: bold;
        }

        .form-group {
            position: relative;
            transition: transform 0.2s ease;
        }

        .form-group.valid .form-control {
            border-color: var(--success);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2310b981' stroke-width='2'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }

        .form-group.invalid .form-control {
            border-color: var(--danger);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23ef4444' stroke-width='2'%3E%3Cline x1='18' y1='6' x2='6' y2='18'%3E%3C/line%3E%3Cline x1='6' y1='6' x2='18' y2='18'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
        }

        .form-help {
            font-size: var(--font-size-xs);
            color: var(--gray-500);
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .floating-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            z-index: 1000;
        }

        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }

        .floating-btn-save {
            background: var(--success);
            color: white;
        }

        .floating-btn-top {
            background: var(--primary);
            color: white;
        }

        .loading-overlay-edit {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay-edit.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
            max-width: 300px;
        }

        .loading-spinner-edit {
            width: 50px;
            height: 50px;
            border: 4px solid var(--gray-300);
            border-top-color: var(--success);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .field-counter {
            position: absolute;
            right: 0.75rem;
            bottom: 0.75rem;
            font-size: var(--font-size-xs);
            color: var(--gray-400);
            pointer-events: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-section {
            animation: slideIn 0.5s ease-out;
        }

        @media (max-width: 768px) {
            .floating-actions {
                bottom: 1rem;
                right: 1rem;
            }

            .floating-btn {
                width: 50px;
                height: 50px;
            }

            .section-nav {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
        }

        .auto-save-indicator {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            background: var(--info);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-lg);
            font-size: var(--font-size-sm);
            display: none;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        .auto-save-indicator.show {
            display: flex;
            animation: slideIn 0.3s ease-out;
        }

        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }

        .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: var(--gray-900);
            color: white;
            text-align: center;
            border-radius: var(--radius-md);
            padding: 0.5rem;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: var(--font-size-xs);
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay-edit" id="loadingOverlayEdit">
        <div class="loading-content">
            <div class="loading-spinner-edit"></div>
            <h3 style="margin: 0 0 0.5rem 0; color: var(--gray-900);">Salvando alterações</h3>
            <p style="margin: 0; color: var(--gray-600); font-size: var(--font-size-sm);">Por favor, aguarde...</p>
        </div>
    </div>

    <!-- Auto-save Indicator -->
    <div class="auto-save-indicator" id="autoSaveIndicator">
        <svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
        </svg>
        <span id="autoSaveText">Rascunho salvo</span>
    </div>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        Editar Registro
                    </h1>
                    <p class="header-subtitle">Atualizacao de dados</p>
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
                <a href="index.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="form.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Novo Registro
                </a>
                <a href="consulta.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    Consultar Dados
                </a>
                <a href="relatorios.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
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

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" style="animation: slideInDown 0.4s ease-out;">
            <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            Erro ao salvar: <?php echo htmlspecialchars($_GET['message'] ?? 'Erro desconhecido'); ?>
        </div>
        <?php endif; ?>

        <?php if ($erro): ?>
        <div class="alert alert-error">
            <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php echo htmlspecialchars($erro); ?>
        </div>
        <div style="text-align: center; padding: 2rem;">
            <a href="consulta.php<?php echo $query_string_return; ?>" class="btn btn-secondary">
                <svg class="icon" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Voltar para Consulta
            </a>
        </div>
        <?php else: ?>

        <!-- CARTAO DE INFORMACOES -->
        <div class="info-card" style="margin-bottom: 2rem; animation: slideIn 0.5s ease-out;">
            <div class="info-icon">
                <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="info-content">
                <h2>Editando: <?php echo htmlspecialchars($registro['tripulante']); ?></h2>
                <p>Período: <?php echo htmlspecialchars($registro['mes']); ?>/<?php echo htmlspecialchars($registro['ano']); ?> | ID: #<?php echo $registro['id']; ?></p>
            </div>
        </div>

        <!-- BARRA DE PROGRESSO E NAVEGACAO -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-body">
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progressBar"></div>
                </div>
                <div class="progress-info">
                    <span>Progresso do preenchimento</span>
                    <span id="progressText">0% completo</span>
                </div>
                <div class="section-nav">
                    <button type="button" class="section-nav-btn" data-section="0">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        </svg>
                        Básicas
                    </button>
                    <button type="button" class="section-nav-btn" data-section="1">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle;">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        </svg>
                        Dispositivos
                    </button>
                    <button type="button" class="section-nav-btn" data-section="2">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle;">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        Segurança
                    </button>
                    <button type="button" class="section-nav-btn" data-section="3">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle;">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/>
                        </svg>
                        Chamados
                    </button>
                    <button type="button" class="section-nav-btn" data-section="4">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle;">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                        </svg>
                        Cobertura
                    </button>
                    <button type="button" class="section-nav-btn" data-section="5">
                        <svg class="icon icon-sm" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        </svg>
                        Backup
                    </button>
                </div>
            </div>
        </div>

        <!-- FORMULARIO -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Formulario de Edicao
                </h2>
                <p class="card-subtitle">Atualize as informacoes do registro selecionado</p>
            </div>

            <form action="editar_submit.php?id=<?php echo $registro['id']; ?><?php echo $query_string_form; ?>" method="POST" id="editForm">
                <div class="card-body">
                    <div class="form-grid">

                        <!-- INFORMACOES BASICAS -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                Informacoes Basicas
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="tripulante">Tripulante</label>
                                    <select id="tripulante" name="tripulante" class="form-control" required>
                                        <option value="" disabled>Selecionar tripulante</option>
                                        <?php foreach ($tripulantes as $tripulante): ?>
                                            <option value="<?php echo htmlspecialchars($tripulante['tripulante']); ?>"
                                                    <?php echo ($registro['tripulante'] === $tripulante['tripulante']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tripulante['tripulante']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="mes">Mes</label>
                                    <select id="mes" name="mes" class="form-control" required>
                                        <option value="" disabled>Selecionar mes</option>
                                        <?php
                                        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                                                 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                        foreach ($meses as $mes): ?>
                                            <option value="<?php echo $mes; ?>"
                                                    <?php echo ($registro['mes'] === $mes) ? 'selected' : ''; ?>>
                                                <?php echo $mes; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="ano">Ano</label>
                                    <select id="ano" name="ano" class="form-control" required>
                                        <option value="" disabled>Selecionar ano</option>
                                        <?php for ($ano = 2024; $ano <= 2030; $ano++): ?>
                                            <option value="<?php echo $ano; ?>"
                                                    <?php echo ($registro['ano'] == $ano) ? 'selected' : ''; ?>>
                                                <?php echo $ano; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DISPOSITIVOS -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                Dispositivos
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="total_dispositivos">Total de Dispositivos</label>
                                    <input type="number" id="total_dispositivos" name="total_dispositivos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['total_dispositivos']); ?>"
                                           min="0" required>
                                    <div class="form-help">Numero total de dispositivos gerenciados</div>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="tipo_desktop">Desktop</label>
                                    <input type="number" id="tipo_desktop" name="tipo_desktop" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['tipo_desktop']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="tipo_notebook">Notebook</label>
                                    <input type="number" id="tipo_notebook" name="tipo_notebook" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['tipo_notebook']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="tipo_servidor">Servidor</label>
                                    <input type="number" id="tipo_servidor" name="tipo_servidor" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['tipo_servidor']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- SEGURANCA -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Seguranca
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="alertas_resolvidos">Alertas Resolvidos</label>
                                    <input type="number" id="alertas_resolvidos" name="alertas_resolvidos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['alertas_resolvidos']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="ameacas_detectadas">Ameacas Detectadas</label>
                                    <input type="number" id="ameacas_detectadas" name="ameacas_detectadas" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['ameacas_detectadas']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="patches_instalados">Patches Instalados</label>
                                    <input type="number" id="patches_instalados" name="patches_instalados" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['patches_instalados']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="acessos_remotos">Acessos Remotos</label>
                                    <input type="number" id="acessos_remotos" name="acessos_remotos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['acessos_remotos']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="pontuacao_integridade">Pontuacao de Integridade (%)</label>
                                    <input type="number" id="pontuacao_integridade" name="pontuacao_integridade" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['pontuacao_integridade']); ?>"
                                           min="0" max="100" required>
                                    <div class="form-help">Valor entre 0 e 100</div>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="falha_logon">Falhas de Logon</label>
                                    <input type="number" id="falha_logon" name="falha_logon" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['falha_logon']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- CHAMADOS -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Chamados
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="num_chamados_abertos">Chamados Abertos</label>
                                    <input type="number" id="num_chamados_abertos" name="num_chamados_abertos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['num_chamados_abertos']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="num_chamados_fechados">Chamados Fechados</label>
                                    <input type="number" id="num_chamados_fechados" name="num_chamados_fechados" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['num_chamados_fechados']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="monitoramento_proativo">Monitoramento Proativo</label>
                                    <input type="number" id="monitoramento_proativo" name="monitoramento_proativo" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['monitoramento_proativo']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- COBERTURA -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                                Cobertura e Monitoramento
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="cobertura_antivirus">Cobertura Antivirus (%)</label>
                                    <input type="number" id="cobertura_antivirus" name="cobertura_antivirus" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['cobertura_antivirus']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="cobertura_web_protection">Cobertura Web Protection (%)</label>
                                    <input type="number" id="cobertura_web_protection" name="cobertura_web_protection" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['cobertura_web_protection']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="cobertura_atualizacao_patches">Cobertura Atualizacao Patches (%)</label>
                                    <input type="number" id="cobertura_atualizacao_patches" name="cobertura_atualizacao_patches" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['cobertura_atualizacao_patches']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="disponibilidade_servidor">Disponibilidade Servidor (%)</label>
                                    <input type="number" id="disponibilidade_servidor" name="disponibilidade_servidor" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['disponibilidade_servidor']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="web_protection_filtradas_bloqueadas">Web Protection - Filtradas/Bloqueadas</label>
                                    <input type="number" id="web_protection_filtradas_bloqueadas" name="web_protection_filtradas_bloqueadas" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['web_protection_filtradas_bloqueadas']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="web_protection_mal_intencionadas_bloqueadas">Web Protection - Mal Intencionadas/Bloqueadas</label>
                                    <input type="number" id="web_protection_mal_intencionadas_bloqueadas" name="web_protection_mal_intencionadas_bloqueadas" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['web_protection_mal_intencionadas_bloqueadas']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- BACKUP -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Backup
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="bkp_completo">Backup Completo</label>
                                    <input type="number" id="bkp_completo" name="bkp_completo" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['bkp_completo']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="bkp_com_erro">Backup com Erro</label>
                                    <input type="number" id="bkp_com_erro" name="bkp_com_erro" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['bkp_com_erro']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="bkp_com_falha">Backup com Falha</label>
                                    <input type="number" id="bkp_com_falha" name="bkp_com_falha" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['bkp_com_falha']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ACOES DO FORMULARIO -->
                <div class="card-footer">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success btn-lg">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Salvar Alterações
                        </button>
                        <a href="consulta.php<?php echo $query_string_return; ?>" class="btn btn-secondary btn-lg">
                            <svg class="icon" viewBox="0 0 24 24"><path d="m19 12-7-7-7 7M12 5v14"/></svg>
                            Voltar para Consulta
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- BOTOES FLUTUANTES -->
        <div class="floating-actions">
            <button type="button" class="floating-btn floating-btn-save" id="floatingSaveBtn" title="Salvar (Ctrl+S)">
                <svg class="icon icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
            </button>
            <button type="button" class="floating-btn floating-btn-top" id="scrollToTopBtn" title="Voltar ao topo" style="display: none;">
                <svg class="icon icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"/>
                    <polyline points="5 12 12 5 19 12"/>
                </svg>
            </button>
        </div>

        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editForm');
            const sections = document.querySelectorAll('.form-section');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const sectionNavBtns = document.querySelectorAll('.section-nav-btn');
            const inputs = document.querySelectorAll('input[required], select[required]');
            const loadingOverlay = document.getElementById('loadingOverlayEdit');
            const autoSaveIndicator = document.getElementById('autoSaveIndicator');
            const floatingSaveBtn = document.getElementById('floatingSaveBtn');
            const scrollToTopBtn = document.getElementById('scrollToTopBtn');

            let formModificado = false;
            let autoSaveTimeout;

            // === CALCULO AUTOMATICO DO TOTAL DE DISPOSITIVOS ===
            function calcularTotal() {
                const desktop = parseInt(document.getElementById('tipo_desktop').value) || 0;
                const notebook = parseInt(document.getElementById('tipo_notebook').value) || 0;
                const servidor = parseInt(document.getElementById('tipo_servidor').value) || 0;
                const total = desktop + notebook + servidor;
                document.getElementById('total_dispositivos').value = total;
            }

            ['tipo_desktop', 'tipo_notebook', 'tipo_servidor'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', calcularTotal);
                }
            });

            // === VALIDACAO DE PERCENTUAIS ===
            function validarPercentual(input) {
                let valor = parseInt(input.value);
                if (isNaN(valor) || valor < 0) input.value = 0;
                if (valor > 100) input.value = 100;
            }

            ['pontuacao_integridade', 'cobertura_antivirus', 'cobertura_web_protection',
             'cobertura_atualizacao_patches', 'disponibilidade_servidor'].forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('blur', () => validarPercentual(input));
                    input.addEventListener('input', () => {
                        const field = input.closest('.form-group');
                        if (parseInt(input.value) > 100) {
                            field.classList.add('invalid');
                            field.classList.remove('valid');
                        }
                    });
                }
            });

            // === CALCULO DE PROGRESSO ===
            function calcularProgresso() {
                let preenchidos = 0;
                inputs.forEach(input => {
                    if (input.value && input.value.trim() !== '') {
                        preenchidos++;
                    }
                });

                const porcentagem = Math.round((preenchidos / inputs.length) * 100);
                progressBar.style.width = porcentagem + '%';
                progressText.textContent = porcentagem + '% completo';

                return porcentagem;
            }

            // === VALIDACAO VISUAL EM TEMPO REAL ===
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const field = this.closest('.form-group');
                    if (this.value && this.value.trim() !== '') {
                        field.classList.add('valid');
                        field.classList.remove('invalid');
                    } else {
                        field.classList.add('invalid');
                        field.classList.remove('valid');
                    }
                    calcularProgresso();
                    atualizarNavegacaoSecoes();
                });

                input.addEventListener('input', function() {
                    calcularProgresso();
                    formModificado = true;
                    agendarAutoSave();
                });
            });

            // === NAVEGACAO ENTRE SECOES ===
            sectionNavBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const sectionIndex = parseInt(this.dataset.section);
                    sections[sectionIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });

                    sectionNavBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            function atualizarNavegacaoSecoes() {
                sections.forEach((section, index) => {
                    const sectionInputs = section.querySelectorAll('input[required], select[required]');
                    let todosPreenchidos = true;

                    sectionInputs.forEach(input => {
                        if (!input.value || input.value.trim() === '') {
                            todosPreenchidos = false;
                        }
                    });

                    const btn = sectionNavBtns[index];
                    if (todosPreenchidos) {
                        btn.classList.add('completed');
                    } else {
                        btn.classList.remove('completed');
                    }
                });
            }

            // === AUTO-SAVE EM LOCALSTORAGE ===
            function salvarRascunho() {
                const formData = {};
                const formInputs = form.querySelectorAll('input, select');

                formInputs.forEach(input => {
                    if (input.name) {
                        formData[input.name] = input.value;
                    }
                });

                localStorage.setItem('editForm_<?php echo $id; ?>', JSON.stringify(formData));

                autoSaveIndicator.classList.add('show');
                setTimeout(() => {
                    autoSaveIndicator.classList.remove('show');
                }, 2000);
            }

            function carregarRascunho() {
                const rascunho = localStorage.getItem('editForm_<?php echo $id; ?>');
                if (rascunho && confirm('Encontramos um rascunho não salvo. Deseja recuperá-lo?')) {
                    const formData = JSON.parse(rascunho);
                    Object.keys(formData).forEach(name => {
                        const input = form.querySelector(`[name="${name}"]`);
                        if (input) {
                            input.value = formData[name];
                        }
                    });
                    calcularProgresso();
                    atualizarNavegacaoSecoes();
                }
            }

            function agendarAutoSave() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(salvarRascunho, 2000);
            }

            // === BOTAO FLUTUANTE DE SALVAR ===
            if (floatingSaveBtn) {
                floatingSaveBtn.addEventListener('click', () => {
                    if (validarFormulario()) {
                        loadingOverlay.classList.add('active');
                        form.submit();
                    }
                });
            }

            // === BOTAO VOLTAR AO TOPO ===
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    scrollToTopBtn.style.display = 'flex';
                } else {
                    scrollToTopBtn.style.display = 'none';
                }
            });

            if (scrollToTopBtn) {
                scrollToTopBtn.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            // === VALIDACAO DO FORMULARIO ===
            function validarFormulario() {
                let valido = true;
                let primeiroInvalido = null;

                inputs.forEach(input => {
                    const field = input.closest('.form-group');
                    if (!input.value || input.value.trim() === '') {
                        field.classList.add('invalid');
                        field.classList.remove('valid');
                        valido = false;
                        if (!primeiroInvalido) primeiroInvalido = input;
                    }
                });

                if (!valido && primeiroInvalido) {
                    primeiroInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    primeiroInvalido.focus();
                    alert('Por favor, preencha todos os campos obrigatórios marcados em vermelho.');
                }

                return valido;
            }

            // === SUBMISSAO DO FORMULARIO ===
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (validarFormulario()) {
                    formModificado = false;
                    localStorage.removeItem('editForm_<?php echo $id; ?>');
                    loadingOverlay.classList.add('active');
                    this.submit();
                }
            });

            // === CONFIRMACAO ANTES DE SAIR ===
            window.addEventListener('beforeunload', function(e) {
                if (formModificado) {
                    e.preventDefault();
                    e.returnValue = 'Você tem alterações não salvas. Deseja sair mesmo assim?';
                }
            });

            // === ATALHOS DE TECLADO ===
            document.addEventListener('keydown', function(e) {
                // Ctrl + S para salvar
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    floatingSaveBtn.click();
                }

                // Escape para cancelar
                if (e.key === 'Escape') {
                    if (formModificado) {
                        if (confirm('Deseja cancelar a edição? Alterações não salvas serão perdidas.')) {
                            formModificado = false;
                            window.location.href = 'consulta.php<?php echo $query_string_return; ?>';
                        }
                    } else {
                        window.location.href = 'consulta.php<?php echo $query_string_return; ?>';
                    }
                }

                // Tab + Shift para navegar entre seções
                if (e.shiftKey && e.key === 'Tab' && e.target.tagName === 'INPUT') {
                    // Comportamento padrão, apenas mencionado para documentação
                }
            });

            // === ANIMACAO DE FOCO NOS CAMPOS ===
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    const group = this.closest('.form-group');
                    group.style.transform = 'scale(1.02)';
                    group.style.transition = 'transform 0.2s ease';
                });

                input.addEventListener('blur', function() {
                    const group = this.closest('.form-group');
                    group.style.transform = 'scale(1)';
                });
            });

            // === INICIALIZACAO ===
            calcularProgresso();
            atualizarNavegacaoSecoes();
            carregarRascunho();

            // Destacar primeira seção
            if (sectionNavBtns.length > 0) {
                sectionNavBtns[0].classList.add('active');
            }

            // === OBSERVER PARA ATUALIZAR NAVEGACAO BASEADO NO SCROLL ===
            const observerOptions = {
                root: null,
                rootMargin: '-50% 0px -50% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = Array.from(sections).indexOf(entry.target);
                        sectionNavBtns.forEach(btn => btn.classList.remove('active'));
                        if (sectionNavBtns[index]) {
                            sectionNavBtns[index].classList.add('active');
                        }
                    }
                });
            }, observerOptions);

            sections.forEach(section => observer.observe(section));

            // === FEEDBACK VISUAL AO SUBMETER ===
            form.querySelectorAll('button[type="submit"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (validarFormulario()) {
                        this.disabled = true;
                        this.innerHTML = `
                            <svg class="icon" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                            </svg>
                            Salvando...
                        `;
                    }
                });
            });
        });
    </script>
</body>
</html>
