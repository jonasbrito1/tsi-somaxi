<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Verificar se está logado
require_login();

$error_message = '';
$success_message = '';
$primeiro_acesso = isset($_GET['primeiro_acesso']) && $_GET['primeiro_acesso'] == '1';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validações
    if (empty($nova_senha) || empty($confirmar_senha)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $error_message = 'As senhas não coincidem.';
    } else {
        // Validar força da senha
        $validacao = validate_password_strength($nova_senha);

        if (!$validacao['valid']) {
            $error_message = $validacao['message'];
        } else {
            // Se não for primeiro acesso, verificar senha atual
            if (!$primeiro_acesso && !empty($senha_atual)) {
                $result_auth = authenticate_user($_SESSION['user_email'], $senha_atual);

                if (!$result_auth['success']) {
                    $error_message = 'Senha atual incorreta.';
                } else {
                    // Alterar senha
                    $result = change_password($_SESSION['user_id'], $nova_senha, $primeiro_acesso);

                    if ($result['success']) {
                        $success_message = $result['message'] . ' Faça login novamente com sua nova senha.';

                        // Fazer logout e redirecionar para login
                        logout_user();
                        header('Refresh: 3; url=login.php?senha_alterada=1');
                    } else {
                        $error_message = $result['message'];
                    }
                }
            } elseif ($primeiro_acesso) {
                // Primeiro acesso - não precisa verificar senha atual
                $result = change_password($_SESSION['user_id'], $nova_senha, true);

                if ($result['success']) {
                    $success_message = $result['message'] . ' Faça login novamente com sua nova senha.';

                    // Fazer logout e redirecionar para login
                    logout_user();
                    header('Refresh: 3; url=login.php?senha_alterada=1');
                } else {
                    $error_message = $result['message'];
                }
            } else {
                $error_message = 'Por favor, informe a senha atual.';
            }
        }
    }
}

$user = get_logged_user();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - SOMAXI Group</title>
    <link rel="icon" href="../utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/app.css">
    <script src="../js/theme.js"></script>
    <script src="../js/user-dropdown.js"></script>
</head>

<body>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <?php echo $primeiro_acesso ? 'Bem-vindo!' : 'Alterar Senha'; ?>
                    </h1>
                    <p class="header-subtitle">
                        <?php echo $primeiro_acesso ? 'Defina sua nova senha de acesso' : 'Atualize sua senha'; ?>
                    </p>
                </div>
            </div>
            <div class="header-logo">
                <img src="../utils/logo_SOMAXI_GROUP_azul.png" alt="SOMAXI GROUP" class="company-logo">
            </div>
        </div>
    </header>

    <!-- NAVEGACAO -->
    <nav class="app-nav">
        <div class="nav-container">
            <div class="nav-links">
                <a href="../index.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                    Dashboard
                </a>
                <a href="../form.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Novo Registro
                </a>
                <a href="../consulta.php">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Consultar Dados
                </a>
                <a href="../relatorios.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    Relatorios
                </a>
                <?php if (is_admin()): ?>
                <a href="cadastro_usuario.php">
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
                        <a href="editar_perfil.php" class="dropdown-item">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span>Editar Perfil</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item danger">
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

        <div style="max-width: 600px; margin: 0 auto;">

            <!-- MENSAGENS -->
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <svg class="icon" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>

            <!-- FORMULARIO -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <?php echo $primeiro_acesso ? 'Defina sua Senha' : 'Alterar Senha'; ?>
                    </h2>
                </div>
                <div class="card-body">

                    <?php if ($primeiro_acesso): ?>
                    <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                        <svg class="icon" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                        <div>
                            <strong>Primeiro acesso!</strong><br>
                            Por favor, defina uma senha segura para sua conta.
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">

                        <?php if (!$primeiro_acesso): ?>
                        <div class="form-group">
                            <label class="form-label">Senha Atual *</label>
                            <input
                                type="password"
                                name="senha_atual"
                                class="form-input"
                                required
                                autocomplete="current-password">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Nova Senha *</label>
                            <input
                                type="password"
                                name="nova_senha"
                                class="form-input"
                                required
                                autocomplete="new-password">
                            <div style="font-size: var(--font-size-sm); color: var(--gray-600); margin-top: 0.5rem;">
                                A senha deve conter:
                                <ul style="padding-left: 1.25rem; margin-top: 0.25rem;">
                                    <li>Mínimo 8 caracteres</li>
                                    <li>Pelo menos uma letra maiúscula</li>
                                    <li>Pelo menos uma letra minúscula</li>
                                    <li>Pelo menos um número</li>
                                    <li>Pelo menos um caractere especial</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirmar Nova Senha *</label>
                            <input
                                type="password"
                                name="confirmar_senha"
                                class="form-input"
                                required
                                autocomplete="new-password">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg class="icon icon-sm" viewBox="0 0 24 24">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                Salvar Nova Senha
                            </button>
                            <?php if (!$primeiro_acesso): ?>
                            <a href="../index.php" class="btn btn-secondary">
                                <svg class="icon icon-sm" viewBox="0 0 24 24">
                                    <line x1="19" y1="12" x2="5" y2="12"/>
                                    <polyline points="12 19 5 12 12 5"/>
                                </svg>
                                Cancelar
                            </a>
                            <?php endif; ?>
                        </div>

                    </form>

                </div>
            </div>

        </div>

    </main>

</body>
</html>
