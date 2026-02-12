<?php
/**
 * EDITAR SUBMIT - TSI
 * ATENÇÃO: Requer autenticação
 */

// Iniciar a sessão
session_start();

// Conectar ao banco de dados e carregar autenticação
require_once 'includes/db.php';
require_once 'includes/auth.php';

// ============================================
// VALIDAÇÃO DE SEGURANÇA
// ============================================

// Verificar se está logado
require_login();

$user = get_logged_user();

// Verificar se o ID foi passado
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar dados do formulário
    $tripulante = $_POST['tripulante'];
    $mes = $_POST['mes'];
    $ano = $_POST['ano'];
    $pontuacao_integridade = $_POST['pontuacao_integridade'];
    $monitoramento_proativo = $_POST['monitoramento_proativo'];
    $disponibilidade_servidor = $_POST['disponibilidade_servidor'];
    $falha_logon = $_POST['falha_logon'];
    $cobertura_antivirus = $_POST['cobertura_antivirus'];
    $cobertura_atualizacao_patches = $_POST['cobertura_atualizacao_patches'];
    $cobertura_web_protection = $_POST['cobertura_web_protection'];
    $total_dispositivos = $_POST['total_dispositivos'];
    $tipo_desktop = $_POST['tipo_desktop'];
    $tipo_notebook = $_POST['tipo_notebook'];
    $tipo_servidor = $_POST['tipo_servidor'];
    $alertas_resolvidos = $_POST['alertas_resolvidos'];
    $ameacas_detectadas = $_POST['ameacas_detectadas'];
    $patches_instalados = $_POST['patches_instalados'];
    $acessos_remotos = $_POST['acessos_remotos'];
    $web_protection_filtradas_bloqueadas = $_POST['web_protection_filtradas_bloqueadas'];
    $web_protection_mal_intencionadas_bloqueadas = $_POST['web_protection_mal_intencionadas_bloqueadas'];
    $bkp_completo = $_POST['bkp_completo'];
    $bkp_com_erro = $_POST['bkp_com_erro'];
    $bkp_com_falha = $_POST['bkp_com_falha'];
    $num_chamados_abertos = $_POST['num_chamados_abertos'];
    $num_chamados_fechados = $_POST['num_chamados_fechados'];

    try {
        // Preparar a query de atualização
        $sql = "UPDATE tabela_dados_tsi SET
                    tripulante = :tripulante,
                    mes = :mes,
                    ano = :ano,
                    pontuacao_integridade = :pontuacao_integridade,
                    monitoramento_proativo = :monitoramento_proativo,
                    disponibilidade_servidor = :disponibilidade_servidor,
                    falha_logon = :falha_logon,
                    cobertura_antivirus = :cobertura_antivirus,
                    cobertura_atualizacao_patches = :cobertura_atualizacao_patches,
                    cobertura_web_protection = :cobertura_web_protection,
                    total_dispositivos = :total_dispositivos,
                    tipo_desktop = :tipo_desktop,
                    tipo_notebook = :tipo_notebook,
                    tipo_servidor = :tipo_servidor,
                    alertas_resolvidos = :alertas_resolvidos,
                    ameacas_detectadas = :ameacas_detectadas,
                    patches_instalados = :patches_instalados,
                    acessos_remotos = :acessos_remotos,
                    web_protection_filtradas_bloqueadas = :web_protection_filtradas_bloqueadas,
                    web_protection_mal_intencionadas_bloqueadas = :web_protection_mal_intencionadas_bloqueadas,
                    bkp_completo = :bkp_completo,
                    bkp_com_erro = :bkp_com_erro,
                    bkp_com_falha = :bkp_com_falha,
                    num_chamados_abertos = :num_chamados_abertos,
                    num_chamados_fechados = :num_chamados_fechados
                WHERE id = :id";

        // Preparar a query
        $stmt = $pdo->prepare($sql);

        // Bind dos parâmetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':tripulante', $tripulante);
        $stmt->bindParam(':mes', $mes);
        $stmt->bindParam(':ano', $ano);
        $stmt->bindParam(':pontuacao_integridade', $pontuacao_integridade, PDO::PARAM_INT);
        $stmt->bindParam(':monitoramento_proativo', $monitoramento_proativo, PDO::PARAM_INT);
        $stmt->bindParam(':disponibilidade_servidor', $disponibilidade_servidor, PDO::PARAM_INT);
        $stmt->bindParam(':falha_logon', $falha_logon, PDO::PARAM_INT);
        $stmt->bindParam(':cobertura_antivirus', $cobertura_antivirus, PDO::PARAM_INT);
        $stmt->bindParam(':cobertura_atualizacao_patches', $cobertura_atualizacao_patches, PDO::PARAM_INT);
        $stmt->bindParam(':cobertura_web_protection', $cobertura_web_protection, PDO::PARAM_INT);
        $stmt->bindParam(':total_dispositivos', $total_dispositivos, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_desktop', $tipo_desktop, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_notebook', $tipo_notebook, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_servidor', $tipo_servidor, PDO::PARAM_INT);
        $stmt->bindParam(':alertas_resolvidos', $alertas_resolvidos, PDO::PARAM_INT);
        $stmt->bindParam(':ameacas_detectadas', $ameacas_detectadas, PDO::PARAM_INT);
        $stmt->bindParam(':patches_instalados', $patches_instalados, PDO::PARAM_INT);
        $stmt->bindParam(':acessos_remotos', $acessos_remotos, PDO::PARAM_INT);
        $stmt->bindParam(':web_protection_filtradas_bloqueadas', $web_protection_filtradas_bloqueadas, PDO::PARAM_INT);
        $stmt->bindParam(':web_protection_mal_intencionadas_bloqueadas', $web_protection_mal_intencionadas_bloqueadas, PDO::PARAM_INT);
        $stmt->bindParam(':bkp_completo', $bkp_completo, PDO::PARAM_INT);
        $stmt->bindParam(':bkp_com_erro', $bkp_com_erro, PDO::PARAM_INT);
        $stmt->bindParam(':bkp_com_falha', $bkp_com_falha, PDO::PARAM_INT);
        $stmt->bindParam(':num_chamados_abertos', $num_chamados_abertos, PDO::PARAM_INT);
        $stmt->bindParam(':num_chamados_fechados', $num_chamados_fechados, PDO::PARAM_INT);

        // Executar a query
        $stmt->execute();

        // Preservar filtros ao redirecionar
        $query_params = [];
        if (isset($_GET['tripulante'])) $query_params['tripulante'] = $_GET['tripulante'];
        if (isset($_GET['mes'])) $query_params['mes'] = $_GET['mes'];
        if (isset($_GET['ano'])) $query_params['ano'] = $_GET['ano'];
        if (isset($_GET['status'])) $query_params['status'] = $_GET['status'];
        if (isset($_GET['pagina'])) $query_params['pagina'] = $_GET['pagina'];
        $query_params['updated'] = '1';

        $query_string = http_build_query($query_params);

        // Redirecionar com mensagem de sucesso e filtros preservados
        header("Location: consulta.php?" . $query_string);
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, redirecionar com mensagem de erro e preservar filtros
        $query_params = [];
        if (isset($_GET['tripulante'])) $query_params['tripulante'] = $_GET['tripulante'];
        if (isset($_GET['mes'])) $query_params['mes'] = $_GET['mes'];
        if (isset($_GET['ano'])) $query_params['ano'] = $_GET['ano'];
        if (isset($_GET['status'])) $query_params['status'] = $_GET['status'];
        if (isset($_GET['pagina'])) $query_params['pagina'] = $_GET['pagina'];

        $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';

        header("Location: editar.php?id=$id&error=1&message=" . urlencode($e->getMessage()) . $query_string);
        exit();
    }
} else {
    // Se o acesso não for via POST, redirecionar para a página de consulta
    header("Location: consulta.php");
    exit();
}
?>
