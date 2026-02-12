<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Verificar se está logado
require_login();

$user = get_logged_user();
$error_message = '';
$success_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validar nome
    if (empty($nome)) {
        $error_message = 'Por favor, informe o nome.';
    }
    // Se quer alterar senha
    elseif (!empty($nova_senha) || !empty($confirmar_senha)) {
        if (empty($senha_atual)) {
            $error_message = 'Por favor, informe a senha atual para alterá-la.';
        } elseif ($nova_senha !== $confirmar_senha) {
            $error_message = 'As senhas não coincidem.';
        } else {
            // Validar força da senha
            $validacao = validate_password_strength($nova_senha);

            if (!$validacao['valid']) {
                $error_message = $validacao['message'];
            } else {
                // Verificar senha atual
                $result_auth = authenticate_user($user['email'], $senha_atual);

                if (!$result_auth['success']) {
                    $error_message = 'Senha atual incorreta.';
                } else {
                    // Atualizar nome e senha
                    try {
                        global $pdo;

                        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET nome = :nome, password = :password, updated_at = CURRENT_TIMESTAMP
                            WHERE id = :id
                        ");

                        $stmt->execute([
                            ':nome' => $nome,
                            ':password' => $senha_hash,
                            ':id' => $user['id']
                        ]);

                        // Atualizar sessão
                        $_SESSION['user_nome'] = $nome;

                        $success_message = 'Perfil atualizado com sucesso!';
                        $user['nome'] = $nome;

                    } catch (PDOException $e) {
                        $error_message = 'Erro ao atualizar perfil: ' . $e->getMessage();
                    }
                }
            }
        }
    }
    // Apenas atualizar nome
    else {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                UPDATE users
                SET nome = :nome, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':id' => $user['id']
            ]);

            // Atualizar sessão
            $_SESSION['user_nome'] = $nome;

            $success_message = 'Nome atualizado com sucesso!';
            $user['nome'] = $nome;

        } catch (PDOException $e) {
            $error_message = 'Erro ao atualizar nome: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - SOMAXI Group</title>
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
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Editar Perfil
                    </h1>
                    <p class="header-subtitle">Atualize suas informações</p>
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
                        <a href="editar_perfil.php" class="dropdown-item active">
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

        <!-- MENSAGENS -->
        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <div><?php echo nl2br(htmlspecialchars($error_message)); ?></div>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <!-- INFORMACOES DO USUARIO -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.25M15.54 15.54l4.24 4.25M1 12h6M17 12h6M4.22 19.78l4.24-4.25M15.54 8.46l4.24-4.25"/></svg>
                    Informações do Perfil
                </div>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                    <div>
                        <label style="display: block; font-size: var(--font-size-sm); font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Email</label>
                        <div style="font-size: var(--font-size-base); color: var(--gray-900); background: var(--gray-100); padding: 0.75rem 1rem; border-radius: var(--radius-md);">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                        <small style="color: var(--gray-500); font-size: var(--font-size-sm);">O email não pode ser alterado</small>
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--font-size-sm); font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Perfil de Acesso</label>
                        <div style="font-size: var(--font-size-base); color: var(--gray-900); background: var(--gray-100); padding: 0.75rem 1rem; border-radius: var(--radius-md);">
                            <?php if ($user['perfil'] === 'admin'): ?>
                                <span class="badge" style="background: var(--danger);">Administrador</span>
                            <?php else: ?>
                                <span class="badge" style="background: var(--info);">Visualização</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORMULARIO DE EDICAO -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    Editar Perfil
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-input"
                                value="<?php echo htmlspecialchars($user['nome']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 1.5rem 0;">
                            <h3 style="font-size: var(--font-size-lg); font-weight: 600; color: var(--gray-900); margin-bottom: 1rem;">
                                Alterar Senha (opcional)
                            </h3>
                            <p style="color: var(--gray-600); font-size: var(--font-size-sm); margin-bottom: 1.5rem;">
                                Deixe em branco se não deseja alterar a senha
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input
                                type="password"
                                id="senha_atual"
                                name="senha_atual"
                                class="form-input"
                                placeholder="••••••••"
                            >
                        </div>

                        <div class="form-group">
                        </div>

                        <div class="form-group">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input
                                type="password"
                                id="nova_senha"
                                name="nova_senha"
                                class="form-input"
                                placeholder="••••••••"
                            >
                            <small class="form-hint">Mínimo 8 caracteres com maiúsculas, minúsculas, números e símbolos</small>
                        </div>

                        <div class="form-group">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                class="form-input"
                                placeholder="••••••••"
                            >
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Salvar Alterações
                            </button>
                            <a href="../index.php" class="btn" style="background: var(--gray-200); color: var(--gray-700);">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </main>
</body>
</html>
