<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validação simples (substitua por autenticação real)
    if ($username === 'admin' && $password === 'senha123') {
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Credenciais inválidas!";
    }
}

include 'includes/header.php';
?>

<div class="login-container">
    <h2>Login</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Entrar</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
