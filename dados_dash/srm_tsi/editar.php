<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login.php");
    exit(); // Evita que o código continue executando
}

require_once 'includes/db.php'; // Conexão com o banco de dados

// Obter o ID do registro a ser editado
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Buscar dados para edição
    $sql = "SELECT * FROM tabela_dados_tsi WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        // Exibir formulário com dados para edição
        echo '<!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Editar Dados dos Tripulantes</title>
            <link rel="stylesheet" href="css/style.css"> <!-- Link para o arquivo CSS -->
        </head>
        <body>
            <div class="header">
                Edição de Dados dos Tripulantes - Somaxi
            </div>
            <div class="form-container">
                <form action="editar_submit.php?id=' . $registro['id'] . '" method="POST">
                    <label for="tripulante">Tripulante:</label>
                    <select id="tripulante" name="tripulante" required>
                        <option value="" disabled>Selecionar tripulante</option>';

                        // Preencher a opção selecionada para o tripulante
                        $tripulantes = $pdo->query("SELECT DISTINCT tripulante FROM tabela_dados_tsi ORDER BY tripulante ASC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($tripulantes as $tripulante) {
                            echo "<option value='" . htmlspecialchars($tripulante['tripulante']) . "'" . ($registro['tripulante'] === $tripulante['tripulante'] ? ' selected' : '') . ">" . htmlspecialchars($tripulante['tripulante']) . "</option>";
                        }

        echo '  </select><br>

                    <label for="mes">Mês:</label>
                    <select id="mes" name="mes" required>
                        <option value="" disabled>Selecionar mês</option>
                        <option value="Janeiro"' . ($registro['mes'] === 'Janeiro' ? ' selected' : '') . '>Janeiro</option>
                        <option value="Fevereiro"' . ($registro['mes'] === 'Fevereiro' ? ' selected' : '') . '>Fevereiro</option>
                        <option value="Março"' . ($registro['mes'] === 'Março' ? ' selected' : '') . '>Março</option>
                        <option value="Abril"' . ($registro['mes'] === 'Abril' ? ' selected' : '') . '>Abril</option>
                        <option value="Maio"' . ($registro['mes'] === 'Maio' ? ' selected' : '') . '>Maio</option>
                        <option value="Junho"' . ($registro['mes'] === 'Junho' ? ' selected' : '') . '>Junho</option>
                        <option value="Julho"' . ($registro['mes'] === 'Julho' ? ' selected' : '') . '>Julho</option>
                        <option value="Agosto"' . ($registro['mes'] === 'Agosto' ? ' selected' : '') . '>Agosto</option>
                        <option value="Setembro"' . ($registro['mes'] === 'Setembro' ? ' selected' : '') . '>Setembro</option>
                        <option value="Outubro"' . ($registro['mes'] === 'Outubro' ? ' selected' : '') . '>Outubro</option>
                        <option value="Novembro"' . ($registro['mes'] === 'Novembro' ? ' selected' : '') . '>Novembro</option>
                        <option value="Dezembro"' . ($registro['mes'] === 'Dezembro' ? ' selected' : '') . '>Dezembro</option>
                    </select><br>

                    <label for="ano">Ano:</label>
                    <select id="ano" name="ano" required>
                        <option value="" disabled>Selecionar ano</option>';

                        // Preencher o campo ano com opções
                        for ($ano = 2024; $ano <= 2030; $ano++) {
                            echo "<option value='$ano'" . ($registro['ano'] == $ano ? ' selected' : '') . ">$ano</option>";
                        }

        echo '</select><br>

                    <label for="pontuacao_integridade">Pontuação de Integridade:</label>
                    <input type="number" id="pontuacao_integridade" name="pontuacao_integridade" value="' . htmlspecialchars($registro['pontuacao_integridade']) . '" required><br>

                    <label for="monitoramento_proativo">Monitoramento Proativo:</label>
                    <input type="number" id="monitoramento_proativo" name="monitoramento_proativo" value="' . htmlspecialchars($registro['monitoramento_proativo']) . '" required><br>

                    <label for="disponibilidade_servidor">Disponibilidade do Servidor:</label>
                    <input type="number" id="disponibilidade_servidor" name="disponibilidade_servidor" value="' . htmlspecialchars($registro['disponibilidade_servidor']) . '" required><br>

                    <label for="falha_logon">Falhas de Logon:</label>
                    <input type="number" id="falha_logon" name="falha_logon" value="' . htmlspecialchars($registro['falha_logon']) . '" required><br>

                    <label for="cobertura_antivirus">Cobertura Antivirus:</label>
                    <input type="number" id="cobertura_antivirus" name="cobertura_antivirus" value="' . htmlspecialchars($registro['cobertura_antivirus']) . '" required><br>

                    <label for="cobertura_atualizacao_patches">Cobertura de Atualização de Patches:</label>
                    <input type="number" id="cobertura_atualizacao_patches" name="cobertura_atualizacao_patches" value="' . htmlspecialchars($registro['cobertura_atualizacao_patches']) . '" required><br>

                    <label for="cobertura_web_protection">Cobertura de Web Protection:</label>
                    <input type="number" id="cobertura_web_protection" name="cobertura_web_protection" value="' . htmlspecialchars($registro['cobertura_web_protection']) . '" required><br>

                    <label for="total_dispositivos">Total de Dispositivos:</label>
                    <input type="number" id="total_dispositivos" name="total_dispositivos" value="' . htmlspecialchars($registro['total_dispositivos']) . '" required><br>

                    <label for="tipo_desktop">Desktop:</label>
                    <input type="number" id="tipo_desktop" name="tipo_desktop" value="' . htmlspecialchars($registro['tipo_desktop']) . '" required><br>

                    <label for="tipo_notebook">Notebook:</label>
                    <input type="number" id="tipo_notebook" name="tipo_notebook" value="' . htmlspecialchars($registro['tipo_notebook']) . '" required><br>

                    <label for="tipo_servidor">Servidor:</label>
                    <input type="number" id="tipo_servidor" name="tipo_servidor" value="' . htmlspecialchars($registro['tipo_servidor']) . '" required><br>

                    <label for="alertas_resolvidos">Alertas Resolvidos:</label>
                    <input type="number" id="alertas_resolvidos" name="alertas_resolvidos" value="' . htmlspecialchars($registro['alertas_resolvidos']) . '" required><br>

                    <label for="ameacas_detectadas">Ameaças Detectadas:</label>
                    <input type="number" id="ameacas_detectadas" name="ameacas_detectadas" value="' . htmlspecialchars($registro['ameacas_detectadas']) . '" required><br>

                    <label for="patches_instalados">Patches Instalados:</label>
                    <input type="number" id="patches_instalados" name="patches_instalados" value="' . htmlspecialchars($registro['patches_instalados']) . '" required><br>

                    <label for="acessos_remotos">Acessos Remotos:</label>
                    <input type="number" id="acessos_remotos" name="acessos_remotos" value="' . htmlspecialchars($registro['acessos_remotos']) . '" required><br>

                    <label for="web_protection_filtradas_bloqueadas">Web Protection - Filtradas/Bloqueadas:</label>
                    <input type="number" id="web_protection_filtradas_bloqueadas" name="web_protection_filtradas_bloqueadas" value="' . htmlspecialchars($registro['web_protection_filtradas_bloqueadas']) . '" required><br>

                    <label for="web_protection_mal_intencionadas_bloqueadas">Web Protection - Mal Intencionadas/Bloqueadas:</label>
                    <input type="number" id="web_protection_mal_intencionadas_bloqueadas" name="web_protection_mal_intencionadas_bloqueadas" value="' . htmlspecialchars($registro['web_protection_mal_intencionadas_bloqueadas']) . '" required><br>

                    <label for="bkp_completo">Backup Completo:</label>
                    <input type="number" id="bkp_completo" name="bkp_completo" value="' . htmlspecialchars($registro['bkp_completo']) . '" required><br>

                    <label for="bkp_com_erro">Backup com Erro:</label>
                    <input type="number" id="bkp_com_erro" name="bkp_com_erro" value="' . htmlspecialchars($registro['bkp_com_erro']) . '" required><br>

                    <label for="bkp_com_falha">Backup com Falha:</label>
                    <input type="number" id="bkp_com_falha" name="bkp_com_falha" value="' . htmlspecialchars($registro['bkp_com_falha']) . '" required><br>

                    <label for="num_chamados_abertos">Número de Chamados Abertos:</label>
                    <input type="number" id="num_chamados_abertos" name="num_chamados_abertos" value="' . htmlspecialchars($registro['num_chamados_abertos']) . '" required><br>

                    <label for="num_chamados_fechados">Número de Chamados Fechados:</label>
                    <input type="number" id="num_chamados_fechados" name="num_chamados_fechados" value="' . htmlspecialchars($registro['num_chamados_fechados']) . '" required><br>

                    <input type="submit" value="Salvar">
                </form>
            </div> <!-- Fim do form-container -->
        </body>
        </html>';
    } else {
        echo "Registro não encontrado.";
    }
} else {
    echo "ID inválido.";
}
?>
