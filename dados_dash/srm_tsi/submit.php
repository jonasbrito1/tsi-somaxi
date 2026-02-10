<?php

// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login.php");
    exit(); // Evita que o código continue executando
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Conexão com o banco de dados
require_once 'includes/db.php'; // Arquivo de conexão com o banco

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar e validar os dados do formulário
    $tripulante = $_POST['tripulante'];
    $mes = $_POST['mes'];
    $ano = $_POST['ano'];  // Coletar o valor de ano do formulário
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
        // Preparar a inserção dos dados no banco
        $sql = "INSERT INTO tabela_dados_tsi (
                    tripulante, mes, ano, pontuacao_integridade, monitoramento_proativo, 
                    disponibilidade_servidor, falha_logon, cobertura_antivirus, 
                    cobertura_atualizacao_patches, cobertura_web_protection, total_dispositivos, 
                    tipo_desktop, tipo_notebook, tipo_servidor, alertas_resolvidos, 
                    ameacas_detectadas, patches_instalados, acessos_remotos, 
                    web_protection_filtradas_bloqueadas, web_protection_mal_intencionadas_bloqueadas, 
                    bkp_completo, bkp_com_erro, bkp_com_falha, num_chamados_abertos, num_chamados_fechados
                ) VALUES (
                    :tripulante, :mes, :ano, :pontuacao_integridade, :monitoramento_proativo, 
                    :disponibilidade_servidor, :falha_logon, :cobertura_antivirus, 
                    :cobertura_atualizacao_patches, :cobertura_web_protection, :total_dispositivos, 
                    :tipo_desktop, :tipo_notebook, :tipo_servidor, :alertas_resolvidos, 
                    :ameacas_detectadas, :patches_instalados, :acessos_remotos, 
                    :web_protection_filtradas_bloqueadas, :web_protection_mal_intencionadas_bloqueadas, 
                    :bkp_completo, :bkp_com_erro, :bkp_com_falha, :num_chamados_abertos, :num_chamados_fechados
                )";

        $stmt = $pdo->prepare($sql);

        // Bind dos parâmetros
        $stmt->bindParam(':tripulante', $tripulante);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);  // Bind do valor de ano
        $stmt->bindParam(':mes', $mes);
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

        // Executar a inserção
        $stmt->execute();

        // Redirecionar com mensagem de sucesso
        header("Location: index.php?status=success");
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, redirecionar com mensagem de erro
        header("Location: index.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Se o acesso não for via POST, redirecionar para o formulário
    header("Location: index.php");
    exit();
}
?>
