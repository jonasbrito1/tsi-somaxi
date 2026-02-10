<?php
session_start();

// Incluir configuração do banco de dados
require_once 'includes/db.php';

if (isset($_GET['logout'])) {
    session_destroy(); 
    header("Location: login.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    // Validações básicas
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error_message = "Todos os campos são obrigatórios.";
    } elseif (strlen($username) < 3) {
        $error_message = "O nome de usuário deve ter pelo menos 3 caracteres.";
    } elseif (strlen($password) < 6) {
        $error_message = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($password != $confirmPassword) {
        $error_message = "As senhas não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "E-mail inválido.";
    } else {
        try {
            // Verificar se o nome de usuário ou e-mail já existe
            $sql = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Nome de usuário ou e-mail já está em uso.";
            } else {
                // Inserir novo usuário no banco de dados
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Usuário cadastrado com sucesso!";
                    header("Location: login.php");
                    exit();
                } else {
                    $error_message = "Erro ao cadastrar usuário. Tente novamente.";
                }
            }
            
        } catch (PDOException $e) {
            error_log("Erro no cadastro: " . $e->getMessage());
            $error_message = "Erro interno. Tente novamente mais tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - TSI</title>
    <style>
        /* Estilo básico para a página de cadastro */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e4f1fe;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
        }

        .form-container {
            width: 90%;
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            text-align: center;
            font-size: 28px;
            margin-bottom: 30px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 14px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: -15px;
            margin-bottom: 20px;
        }

        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-header">
            <h2>Cadastro</h2>
        </div>

        <!-- Debug info (remover em produção) -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="debug-info">
                <strong>Debug da Conexão:</strong><br>
                <?php 
                $debugInfo = debugConnection();
                echo "<pre>" . json_encode($debugInfo, JSON_PRETTY_PRINT) . "</pre>";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <label for="username">Nome de usuário:</label>
            <input type="text" id="username" name="username" required 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                   placeholder="Mínimo 3 caracteres">

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   placeholder="seu@email.com">

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required
                   placeholder="Mínimo 6 caracteres">
            <div class="password-requirements">
                * A senha deve ter pelo menos 6 caracteres
            </div>

            <label for="confirm-password">Confirmar senha:</label>
            <input type="password" id="confirm-password" name="confirm-password" required
                   placeholder="Digite a senha novamente">

            <input type="submit" value="Cadastrar">
        </form>

        <div class="login-link">
            <p>Já tem uma conta? <a href="login.php">Entrar</a></p>
        </div>
    </div>

    <script>
        // Validação adicional no frontend
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const username = document.getElementById('username').value;

            if (username.length < 3) {
                alert('O nome de usuário deve ter pelo menos 3 caracteres.');
                e.preventDefault();
                return;
            }

            if (password.length < 6) {
                alert('A senha deve ter pelo menos 6 caracteres.');
                e.preventDefault();
                return;
            }

            if (password !== confirmPassword) {
                alert('As senhas não coincidem.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>

</html>