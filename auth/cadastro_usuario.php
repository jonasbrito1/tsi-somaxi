<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Apenas admins podem acessar
require_admin();

$error_message = '';
$success_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $perfil = $_POST['perfil'] ?? 'visualizacao';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Email inválido.';
    } elseif ($senha !== $confirmar_senha) {
        $error_message = 'As senhas não coincidem.';
    } else {
        // Validar força da senha
        $validacao = validate_password_strength($senha);

        if (!$validacao['valid']) {
            $error_message = $validacao['message'];
        } else {
            // Criar usuário
            $result = create_user([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha,
                'perfil' => $perfil
            ]);

            if ($result['success']) {
                $success_message = $result['message'];

                // Limpar formulário
                $_POST = [];
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Buscar lista de usuários
try {
    $stmt = $pdo->query("
        SELECT id, nome, email, perfil, ativo, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
}

$user = get_logged_user();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários - SOMAXI Group</title>
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
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Cadastro de Usuários
                    </h1>
                    <p class="header-subtitle">Gerenciar usuários do sistema</p>
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
                <a href="cadastro_usuario.php" class="active">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    Usuários
                </a>
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

        <!-- FORMULARIO -->
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <div class="card-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <div class="card-title" style="color: white; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                        <svg class="icon icon-lg" viewBox="0 0 24 24" style="color: white;">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6M23 11h-6"/>
                        </svg>
                    </div>
                    <div>
                        <h2 style="font-size: var(--font-size-xl); font-weight: 700; margin: 0;">Cadastrar Novo Usuário</h2>
                        <p style="font-size: var(--font-size-sm); opacity: 0.9; margin: 0.25rem 0 0 0;">Preencha os dados para criar uma nova conta</p>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding: 2rem;">
                <form method="POST" action="">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

                        <!-- Nome Completo -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="nome" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                Nome Completo *
                            </label>
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-input"
                                placeholder="Digite o nome completo"
                                value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                style="padding: 0.875rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s;"
                                required
                            >
                        </div>

                        <!-- Email -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="email" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                Email *
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input"
                                placeholder="usuario@exemplo.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                style="padding: 0.875rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s;"
                                required
                            >
                        </div>

                    </div>

                    <!-- Perfil de Acesso -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="perfil" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                            <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            Perfil de Acesso *
                        </label>
                        <select id="perfil" name="perfil" class="form-input" style="padding: 0.875rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s; cursor: pointer;" required>
                            <option value="visualizacao" <?php echo (isset($_POST['perfil']) && $_POST['perfil'] === 'visualizacao') ? 'selected' : ''; ?>>
                                Visualização (Somente Leitura)
                            </option>
                            <option value="admin" <?php echo (isset($_POST['perfil']) && $_POST['perfil'] === 'admin') ? 'selected' : ''; ?>>
                                Administrador (Acesso Completo)
                            </option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

                        <!-- Senha -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="senha" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                Senha *
                            </label>
                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                class="form-input"
                                placeholder="Digite a senha"
                                style="padding: 0.875rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s;"
                                required
                            >
                            <small class="form-hint" style="display: block; margin-top: 0.5rem; font-size: var(--font-size-sm); color: var(--gray-500); line-height: 1.4;">
                                Mínimo 8 caracteres com maiúsculas, minúsculas, números e símbolos
                            </small>
                        </div>

                        <!-- Confirmar Senha -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="confirmar_senha" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                                <svg class="icon icon-sm" viewBox="0 0 24 24" style="color: var(--primary);">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                Confirmar Senha *
                            </label>
                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                class="form-input"
                                placeholder="Confirme a senha"
                                style="padding: 0.875rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--radius-md); font-size: var(--font-size-base); transition: all 0.2s;"
                                required
                            >
                        </div>

                    </div>

                    <!-- Botão Submit -->
                    <div style="display: flex; justify-content: flex-end; padding-top: 1rem; border-top: 2px solid var(--gray-100);">
                        <button type="submit" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: var(--font-size-base); font-weight: 600; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.2s;">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <path d="M20 8v6M23 11h-6"/>
                            </svg>
                            Cadastrar Usuário
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <style>
            .form-input:focus {
                outline: none;
                border-color: var(--primary) !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4) !important;
            }

            .btn-primary:active {
                transform: translateY(0);
            }
        </style>

        <!-- LISTA DE USUARIOS -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <div class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Usuários Cadastrados
                </div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <?php if ($usuario['perfil'] === 'admin'): ?>
                                            <span class="badge" style="background: var(--danger);">Admin</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: var(--info);">Visualização</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ativo']): ?>
                                            <span class="badge" style="background: var(--success);">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: var(--gray-500);">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                        Nenhum usuário cadastrado
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</body>
</html>
