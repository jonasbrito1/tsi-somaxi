<?php
/**
 * Script de Setup - Sistema de Autenticação
 * Executa a criação da tabela users e insere o primeiro admin
 */

require_once __DIR__ . '/../includes/db.php';

echo "<h2>Setup do Sistema de Autenticação</h2>";
echo "<hr>";

try {
    // 1. Criar tabela users
    echo "<p><strong>1. Criando tabela users...</strong></p>";

    $sql_create_table = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        perfil ENUM('admin', 'visualizacao') NOT NULL DEFAULT 'visualizacao',
        primeiro_acesso BOOLEAN DEFAULT TRUE,
        ativo BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_perfil (perfil),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_create_table);
    echo "<p style='color: green;'>✓ Tabela users criada com sucesso!</p>";

    // 2. Verificar se admin já existe
    echo "<p><strong>2. Verificando usuário admin...</strong></p>";

    $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = :email");
    $stmt_check->execute([':email' => 'jonas.pacheco@somaxi.com.br']);
    $exists = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($exists['count'] > 0) {
        echo "<p style='color: orange;'>⚠ Usuário admin já existe!</p>";
    } else {
        // 3. Inserir primeiro usuário admin
        echo "<p><strong>3. Criando primeiro usuário admin...</strong></p>";

        $nome = 'Jonas Pacheco';
        $email = 'jonas.pacheco@somaxi.com.br';
        $senha = 'Mudar@123';
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sql_insert = "
        INSERT INTO users (nome, email, password, perfil, primeiro_acesso, ativo)
        VALUES (:nome, :email, :password, 'admin', FALSE, TRUE)
        ";

        $stmt = $pdo->prepare($sql_insert);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':password' => $senha_hash
        ]);

        echo "<p style='color: green;'>✓ Usuário admin criado com sucesso!</p>";
        echo "<div style='background: #f0f9ff; padding: 15px; border-left: 4px solid #3b82f6; margin: 10px 0;'>";
        echo "<strong>Credenciais de Acesso:</strong><br>";
        echo "Email: <code>jonas.pacheco@somaxi.com.br</code><br>";
        echo "Senha: <code>Mudar@123</code><br>";
        echo "Perfil: <code>Admin</code>";
        echo "</div>";
    }

    // 4. Listar todos os usuários
    echo "<p><strong>4. Usuários cadastrados:</strong></p>";

    $stmt_users = $pdo->query("SELECT id, nome, email, perfil, ativo, created_at FROM users");
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th><th>Ativo</th><th>Criado em</th>";
        echo "</tr>";

        foreach ($users as $user) {
            $cor_perfil = $user['perfil'] === 'admin' ? '#ef4444' : '#3b82f6';
            $cor_ativo = $user['ativo'] ? '#10b981' : '#6b7280';

            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['nome']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td style='color: {$cor_perfil}; font-weight: bold;'>" . strtoupper($user['perfil']) . "</td>";
            echo "<td style='color: {$cor_ativo};'>" . ($user['ativo'] ? 'Sim' : 'Não') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }

        echo "</table>";
    }

    echo "<hr>";
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ Setup concluído com sucesso!</p>";
    echo "<p><a href='../auth/login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir para Login</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Verifique a conexão com o banco de dados e tente novamente.</p>";
}
?>
