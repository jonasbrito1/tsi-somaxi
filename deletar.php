<?php
/**
 * DELETAR REGISTRO - TSI
 * ATENÇÃO: Apenas administradores podem deletar registros
 */

// Iniciar a sessão
session_start();

// Conectar ao banco de dados e carregar autenticação
require_once 'includes/db.php';
require_once 'includes/auth.php';

// ============================================
// VALIDAÇÃO DE SEGURANÇA - ADMIN APENAS
// ============================================

// Verificar se está logado
require_login();

// Verificar se é administrador
if (!is_admin()) {
    // Log de tentativa não autorizada
    error_log("TENTATIVA DE EXCLUSÃO NÃO AUTORIZADA - User ID: " . ($_SESSION['user_id'] ?? 'desconhecido') . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));

    // Preservar filtros da consulta
    $query_params = [];
    if (isset($_GET['tripulante'])) $query_params['tripulante'] = $_GET['tripulante'];
    if (isset($_GET['mes'])) $query_params['mes'] = $_GET['mes'];
    if (isset($_GET['ano'])) $query_params['ano'] = $_GET['ano'];
    if (isset($_GET['status'])) $query_params['status'] = $_GET['status'];
    if (isset($_GET['pagina'])) $query_params['pagina'] = $_GET['pagina'];

    $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';

    // Redirecionar com mensagem de erro
    header("Location: consulta.php{$query_string}&error=1&message=" . urlencode("Acesso negado! Apenas administradores podem deletar registros."));
    exit();
}

// ============================================
// VALIDAÇÃO DE PARÂMETROS
// ============================================

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: consulta.php?error=1&message=" . urlencode("ID inválido."));
    exit();
}

$id = intval($_GET['id']);

if ($id <= 0) {
    header("Location: consulta.php?error=1&message=" . urlencode("ID inválido."));
    exit();
}

// ============================================
// BUSCAR INFORMAÇÕES DO REGISTRO ANTES DE DELETAR
// ============================================

try {
    // Buscar dados do registro para log
    $stmt_info = $pdo->prepare("SELECT id, tripulante, mes, ano FROM tabela_dados_tsi WHERE id = :id");
    $stmt_info->execute([':id' => $id]);
    $registro = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        header("Location: consulta.php?error=1&message=" . urlencode("Registro não encontrado."));
        exit();
    }

    // ============================================
    // EXECUTAR EXCLUSÃO
    // ============================================

    // Iniciar transação
    $pdo->beginTransaction();

    $sql = "DELETE FROM tabela_dados_tsi WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    // Verificar se deletou
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        header("Location: consulta.php?error=1&message=" . urlencode("Erro: Registro não foi deletado."));
        exit();
    }

    // Commit da transação
    $pdo->commit();

    // ============================================
    // LOG DE AUDITORIA
    // ============================================

    $user_info = get_logged_user();
    $log_message = sprintf(
        "REGISTRO DELETADO - ID: %d, Tripulante: %s, Período: %s/%s - Por: %s (ID: %d) - IP: %s",
        $registro['id'],
        $registro['tripulante'],
        $registro['mes'],
        $registro['ano'],
        $user_info['nome'],
        $user_info['id'],
        $_SERVER['REMOTE_ADDR'] ?? 'desconhecido'
    );
    error_log($log_message);

    // ============================================
    // REDIRECIONAR COM SUCESSO
    // ============================================

    // Preservar filtros da consulta
    $query_params = [];
    if (isset($_GET['tripulante'])) $query_params['tripulante'] = $_GET['tripulante'];
    if (isset($_GET['mes'])) $query_params['mes'] = $_GET['mes'];
    if (isset($_GET['ano'])) $query_params['ano'] = $_GET['ano'];
    if (isset($_GET['status'])) $query_params['status'] = $_GET['status'];
    if (isset($_GET['pagina'])) $query_params['pagina'] = $_GET['pagina'];
    $query_params['deleted'] = '1';

    $query_string = http_build_query($query_params);

    header("Location: consulta.php?" . $query_string);
    exit();

} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log do erro
    error_log("ERRO AO DELETAR REGISTRO - ID: $id - Erro: " . $e->getMessage());

    // Redirecionar com mensagem de erro
    header("Location: consulta.php?error=1&message=" . urlencode("Erro ao deletar registro: " . $e->getMessage()));
    exit();
}
?>
