<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy(); 
    header("Location: login.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    // Verificar se as senhas coincidem
    if ($password != $confirmPassword) {
        $error_message = "As senhas não coincidem.";
    } else {
        // Conectar ao banco de dados
        $conn = new mysqli("localhost", "somaxi", "S0m4x1@193", "dados_tripulantes_tsi");

        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }

        // Verificar se o nome de usuário já existe
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Nome de usuário já está em uso.";
        } else {
            // Inserir novo usuário no banco de dados
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $email, $hashedPassword);

            if ($stmt->execute()) {
                header("Location: login.php"); // Redirecionar para a página de login após o cadastro
                exit();
            } else {
                $error_message = "Erro ao cadastrar usuário.";
            }
        }

        $conn->close();
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

        .login-link {
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
            <h2>Cadastro</h2>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <label for="username">Nome de usuário:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm-password">Confirmar senha:</label>
            <input type="password" id="confirm-password" name="confirm-password" required>

            <input type="submit" value="Cadastrar">
        </form>

        <div class="login-link">
            <p>Já tem uma conta? <a href="login.php">Entrar</a></p>
        </div>
    </div>
</body>

</html>
