<?php
/**
 * Gerador de Hash de Senha
 * Gera o hash correto para a senha e atualiza no banco
 */

require_once __DIR__ . '/../includes/db.php';

echo "<h2>Gerador de Hash de Senha</h2>";
echo "<hr>";

// Senha que queremos usar
$senha = 'Mudar@123';
$email = 'jonas.pacheco@somaxi.com.br';

// Gerar hash
$hash_correto = password_hash($senha, PASSWORD_DEFAULT);

echo "<p><strong>Senha:</strong> <code>{$senha}</code></p>";
echo "<p><strong>Hash gerado:</strong></p>";
echo "<textarea style='width: 100%; height: 60px; font-family: monospace;'>{$hash_correto}</textarea>";

echo "<hr>";

// Verificar senha antiga no banco
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, password FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<p><strong>Usuário encontrado:</strong> {$user['nome']} ({$user['email']})</p>";
        echo "<p><strong>Hash atual no banco:</strong></p>";
        echo "<textarea style='width: 100%; height: 60px; font-family: monospace;'>{$user['password']}</textarea>";

        // Verificar se a senha atual funciona
        if (password_verify($senha, $user['password'])) {
            echo "<p style='color: green;'><strong>✓ A senha atual JÁ FUNCIONA!</strong></p>";
            echo "<p>Tente fazer login novamente.</p>";
        } else {
            echo "<p style='color: red;'><strong>✗ A senha atual NÃO funciona!</strong></p>";
            echo "<p>Vamos atualizar com o hash correto...</p>";

            // Atualizar com hash correto
            $stmt_update = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt_update->execute([
                ':password' => $hash_correto,
                ':email' => $email
            ]);

            echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ Hash atualizado com sucesso!</p>";
            echo "<p>Agora você pode fazer login com:</p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> {$email}</li>";
            echo "<li><strong>Senha:</strong> {$senha}</li>";
            echo "</ul>";

            // Verificar novamente
            $stmt_check = $pdo->prepare("SELECT password FROM users WHERE email = :email");
            $stmt_check->execute([':email' => $email]);
            $user_updated = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (password_verify($senha, $user_updated['password'])) {
                echo "<p style='color: green; font-size: 16px;'><strong>✓ CONFIRMADO: A senha agora funciona!</strong></p>";
            }
        }
    } else {
        echo "<p style='color: red;'><strong>Erro:</strong> Usuário não encontrado!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir para Login</a></p>";
?>
