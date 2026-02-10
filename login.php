<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário já está logado
if (isset($_SESSION['user_id'])) {    header("Location: index.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Usar a configuração de banco que já funciona
        require_once 'includes/db.php';

        // Verificar se o usuário existe
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verificar a senha
            if (password_verify($password, $user['password'])) {
                // Senha correta - iniciar sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                // Log de sucesso (opcional)
                error_log("Login bem-sucedido para usuário: " . $username);

                // Redirecionar para o dashboard
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Senha incorreta.";
                error_log("Tentativa de login com senha incorreta para usuário: " . $username);
            }
        } else {
            $error_message = "Usuário não encontrado.";
            error_log("Tentativa de login com usuário inexistente: " . $username);
        }

    } catch (PDOException $e) {
        $error_message = "Erro de conexão com o banco de dados.";
        error_log("Erro PDO no login: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema TSI</title>
    <link rel="stylesheet" href="css/app.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        }
        .login-container {
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 400px;
            margin: 1rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            height: 60px;
            margin-bottom: 1rem;
        }
        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        .credentials-hint {
            background: var(--success-light, #d4edda);
            border: 1px solid var(--success, #28a745);
            border-radius: var(--radius-md, 8px);
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
        }
        .credentials-hint strong {
            color: var(--gray-700);
        }
        .error-message {
            background-color: var(--danger-light, #f8d7da);
            color: var(--danger, #dc3545);
            border: 1px solid var(--danger, #dc3545);
            border-radius: var(--radius-md, 8px);
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .debug-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        .debug-links a {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin: 0.25rem 0.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--gray-500);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--radius-full, 20px);
            font-size: 0.75rem;
            transition: background-color 0.2s ease;
        }
        .debug-links a:hover {
            background-color: var(--gray-600);
        }
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-500);
            font-size: 0.75rem;
        }
        .icon-sm {
            width: 16px;
            height: 16px;
        }
        .icon-md {
            width: 20px;
            height: 20px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <svg class="login-logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <h2 class="login-title">Sistema TSI</h2>
            <p class="login-subtitle">Faca login para acessar o sistema</p>
        </div>

        <div class="credentials-hint">
            <strong>Dica:</strong> Use suas credenciais de acesso<br>
            <small>Credenciais de teste: admin / admin123</small>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label class="form-label" for="username">Nome de usuario</label>
                <input type="text"
                       class="form-control"
                       id="username"
                       name="username"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       required
                       autocomplete="username"
                       placeholder="Digite seu usuario">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Senha</label>
                <input type="password"
                       class="form-control"
                       id="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="Digite sua senha">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Entrar
            </button>
        </form>

        <div class="debug-links">
            <a href="login_debug.php" title="Debug de Login">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="M21 21l-4.35-4.35"></path>
                </svg>
                Debug
            </a>
            <a href="test_connection.php" title="Teste de Conexao">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
                Teste
            </a>
            <a href="fix_passwords.php" title="Corrigir Senhas">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Fix
            </a>
        </div>

        <div class="footer-text">
            Sistema TSI - Somaxi &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>

</html>
