<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Se já estiver logado, redirecionar para index
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Processar logout message
if (isset($_GET['logout'])) {
    $success_message = 'Logout realizado com sucesso!';
}

// Mensagem após alterar senha
if (isset($_GET['senha_alterada'])) {
    $success_message = 'Senha alterada com sucesso! Faça login com sua nova senha.';
}

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } else {
        $result = authenticate_user($email, $senha);

        if ($result['success']) {
            // Verificar se precisa alterar senha no primeiro acesso
            if (is_primeiro_acesso()) {
                header('Location: /auth/alterar_senha.php?primeiro_acesso=1');
                exit;
            }

            // Redirecionar para página solicitada ou index
            $redirect = $_GET['redirect'] ?? '/index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SOMAXI Group</title>
    <link rel="icon" href="../utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/app.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            max-width: 200px;
            margin-bottom: 1.5rem;
        }

        .login-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            font-size: var(--font-size-base);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            transition: all var(--transition-base);
            box-sizing: border-box;
            background: var(--white);
            color: var(--gray-900);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: var(--font-size-base);
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../utils/logo_SOMAXI_GROUP_azul.png" alt="SOMAXI GROUP" class="login-logo">
                <h1 class="login-title">Bem-vindo!</h1>
                <p class="login-subtitle">Sistema de Coleta de Dados - Somaxi TSI</p>
            </div>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <svg class="icon" style="flex-shrink: 0;" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <svg class="icon" style="flex-shrink: 0;" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="seu@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="form-input"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">
                    Entrar
                </button>
            </form>
        </div>

        <p style="text-align: center; margin-top: 1.5rem; color: white; font-size: var(--font-size-sm);">
            © <?php echo date('Y'); ?> SOMAXI GROUP. Todos os direitos reservados.
        </p>
    </div>
</body>
</html>
