<?php
/**
 * Logout - Sistema TSI
 * Encerra a sessão do usuário e redireciona para login
 */

session_start();
require_once __DIR__ . '/../includes/auth.php';

// Fazer logout
logout_user();

// Redirecionar para login com mensagem
header('Location: /auth/login.php?logout=1');
exit;
?>
