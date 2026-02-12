<?php
/**
 * Sistema de Autenticação - TSI
 * Funções para gerenciar login, sessões e permissões
 */

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Verifica se o usuário está autenticado
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Verifica se o usuário é admin
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['user_perfil']) && $_SESSION['user_perfil'] === 'admin';
}

/**
 * Verifica se é o primeiro acesso do usuário
 * @return bool
 */
function is_primeiro_acesso() {
    return isset($_SESSION['primeiro_acesso']) && $_SESSION['primeiro_acesso'] === true;
}

/**
 * Autentica um usuário
 * @param string $email
 * @param string $senha
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function authenticate_user($email, $senha) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id, nome, email, password, perfil, primeiro_acesso, ativo
            FROM users
            WHERE email = :email AND ativo = TRUE
        ");

        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email ou senha incorretos.',
                'user' => null
            ];
        }

        // Verificar senha
        if (!password_verify($senha, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Email ou senha incorretos.',
                'user' => null
            ];
        }

        // Login bem-sucedido - criar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_perfil'] = $user['perfil'];
        $_SESSION['primeiro_acesso'] = $user['primeiro_acesso'];

        return [
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user' => $user
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Erro ao autenticar: ' . $e->getMessage(),
            'user' => null
        ];
    }
}

/**
 * Faz logout do usuário
 */
function logout_user() {
    session_unset();
    session_destroy();
}

/**
 * Redireciona para login se não estiver autenticado
 * @param string $redirect_url URL para redirecionar após login
 */
function require_login($redirect_url = null) {
    if (!is_logged_in()) {
        $query_string = $redirect_url ? '?redirect=' . urlencode($redirect_url) : '';
        header('Location: /auth/login.php' . $query_string);
        exit;
    }

    // Se for primeiro acesso, redirecionar para alterar senha
    if (is_primeiro_acesso() && basename($_SERVER['PHP_SELF']) !== 'alterar_senha.php') {
        header('Location: /auth/alterar_senha.php?primeiro_acesso=1');
        exit;
    }
}

/**
 * Redireciona se não for admin
 */
function require_admin() {
    require_login();

    if (!is_admin()) {
        header('Location: /index.php?error=acesso_negado');
        exit;
    }
}

/**
 * Altera a senha do usuário
 * @param int $user_id
 * @param string $nova_senha
 * @param bool $primeiro_acesso
 * @return array ['success' => bool, 'message' => string]
 */
function change_password($user_id, $nova_senha, $primeiro_acesso = false) {
    global $pdo;

    try {
        // Inicia transação explícita
        $pdo->beginTransaction();

        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = :password,
                primeiro_acesso = FALSE,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $stmt->execute([
            ':password' => $senha_hash,
            ':id' => $user_id
        ]);

        // Verifica se realmente atualizou
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Erro: Usuário não encontrado ou senha não foi alterada.'
            ];
        }

        // Commit da transação
        $pdo->commit();

        // Log de auditoria
        error_log("Senha alterada com sucesso para user_id: $user_id");

        // Atualizar sessão
        $_SESSION['primeiro_acesso'] = false;

        return [
            'success' => true,
            'message' => 'Senha alterada com sucesso!'
        ];

    } catch (PDOException $e) {
        // Rollback em caso de erro
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Erro ao alterar senha para user_id $user_id: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Erro ao alterar senha: ' . $e->getMessage()
        ];
    }
}

/**
 * Cria um novo usuário (apenas admin)
 * @param array $dados ['nome', 'email', 'senha', 'perfil']
 * @return array ['success' => bool, 'message' => string]
 */
function create_user($dados) {
    global $pdo;

    try {
        // Verificar se email já existe
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = :email");
        $stmt_check->execute([':email' => $dados['email']]);
        $exists = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($exists['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Este email já está cadastrado.'
            ];
        }

        // Criar usuário
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (nome, email, password, perfil, primeiro_acesso, ativo)
            VALUES (:nome, :email, :password, :perfil, TRUE, TRUE)
        ");

        $stmt->execute([
            ':nome' => $dados['nome'],
            ':email' => $dados['email'],
            ':password' => $senha_hash,
            ':perfil' => $dados['perfil']
        ]);

        return [
            'success' => true,
            'message' => 'Usuário criado com sucesso!'
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Erro ao criar usuário: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtém informações do usuário logado
 * @return array|null
 */
function get_logged_user() {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'nome' => $_SESSION['user_nome'],
        'email' => $_SESSION['user_email'],
        'perfil' => $_SESSION['user_perfil'],
        'primeiro_acesso' => $_SESSION['primeiro_acesso']
    ];
}

/**
 * Validação de força de senha
 * @param string $senha
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_password_strength($senha) {
    $errors = [];

    if (strlen($senha) < 8) {
        $errors[] = 'A senha deve ter no mínimo 8 caracteres';
    }

    if (!preg_match('/[A-Z]/', $senha)) {
        $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
    }

    if (!preg_match('/[a-z]/', $senha)) {
        $errors[] = 'A senha deve conter pelo menos uma letra minúscula';
    }

    if (!preg_match('/[0-9]/', $senha)) {
        $errors[] = 'A senha deve conter pelo menos um número';
    }

    if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
        $errors[] = 'A senha deve conter pelo menos um caractere especial (@, #, $, etc.)';
    }

    if (count($errors) > 0) {
        return [
            'valid' => false,
            'message' => implode('<br>', $errors)
        ];
    }

    return [
        'valid' => true,
        'message' => 'Senha válida'
    ];
}
?>
