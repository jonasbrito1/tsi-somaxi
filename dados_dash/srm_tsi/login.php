<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário já está logado
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redirecionar para a página de dashboard, se o usuário já estiver logado
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Conectar ao banco de dados
    $conn = new mysqli("localhost", "somaxi", "S0m4x1@193", "dados_tripulantes_tsi");

    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    // Verificar se o usuário existe
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Se a senha estiver correta, iniciar a sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php"); // Redirecionar para a página de sucesso ou dashboard
            exit();
        } else {
            $error_message = "Senha incorreta.";
        }
    } else {
        $error_message = "Usuário não encontrado.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TSI</title>
    <style>
        /* Estilo básico para a página de login */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e4f1fe;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
        }

        .form-container {
            width: 90%;
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .form-header {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .register-link {
            text-align: center;
            margin-top: 10px;
        }

        .error-message {
            color: red;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-header">
            <h2>Login</h2>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="username">Nome de usuário:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Entrar">
        </form>

    </div>
</body>

</html>
