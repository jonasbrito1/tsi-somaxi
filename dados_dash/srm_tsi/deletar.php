<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login.php");
    exit(); // Evita que o código continue executando
}

// Conectar ao banco de dados
require_once 'includes/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: consulta.php");
    exit();
}

$id = intval($_GET['id']);

try {
    // Preparar e executar a consulta para deletar o registro
    $sql = "DELETE FROM tabela_dados_tsi WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    // Redirecionar após a exclusão com um status de sucesso
    header("Location: consulta.php?status=deleted");
    exit();

} catch (PDOException $e) {
    // Exibir erro caso algo dê errado
    echo "<p>Erro ao deletar o registro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
