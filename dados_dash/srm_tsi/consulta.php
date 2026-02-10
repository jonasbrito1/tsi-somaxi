<?php
// Iniciar a sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login.php");
    exit(); // Evita que o código continue executando
}

require_once 'includes/db.php'; // Acessar a conexão com o banco de dados

// Inicializar variáveis de filtro
$filtro_tripulante = isset($_GET['tripulante']) ? trim($_GET['tripulante']) : '';
$filtro_mes = isset($_GET['mes']) ? trim($_GET['mes']) : '';
$filtro_ano = isset($_GET['ano']) ? trim($_GET['ano']) : '';

// Obter lista de tripulantes (clientes) para o datalist
$stmt_tripulantes = $pdo->query("SELECT DISTINCT tripulante FROM tabela_dados_tsi ORDER BY tripulante ASC");
$tripulantes = $stmt_tripulantes->fetchAll(PDO::FETCH_ASSOC);

// Construir a query com filtros
$sql = "SELECT * FROM tabela_dados_tsi WHERE 1=1";
$params = [];

if (!empty($filtro_tripulante)) {
    $sql .= " AND tripulante LIKE :tripulante";
    $params[':tripulante'] = "%$filtro_tripulante%";
}

if (!empty($filtro_mes)) {
    $sql .= " AND mes = :mes";
    $params[':mes'] = $filtro_mes;
}

if (!empty($filtro_ano)) {
    $sql .= " AND ano = :ano";
    $params[':ano'] = $filtro_ano;
}

// Preparar e executar consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir o arquivo CSS diretamente na página
echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Dados dos Tripulantes</title>
    <link rel="stylesheet" type="text/css" href="css/consulta.css">
    <style>
        .table-container {
            max-height: 500px;
            overflow-y: scroll;
        }
    </style>
</head>
<body>';

// Adicionando o cabeçalho da consulta
echo "<div class='header-consulta'>
        Consulta de dados dos tripulantes - Somaxi
      </div>";

// Adicionando os campos de filtro antes da tabela
echo "<div class='filter-container'>
        <form method='GET' action='consulta.php' class='filter-form'>
            <div class='filter-field'>
                <label for='tripulante'>Tripulante:</label>
                <input type='text' id='tripulante' name='tripulante' value='" . htmlspecialchars($filtro_tripulante) . "' placeholder='Digite ou selecione o tripulante' list='tripulantes'>
                <datalist id='tripulantes'>";

// Preencher o datalist com os tripulantes
foreach ($tripulantes as $tripulante) {
    echo "<option value='" . htmlspecialchars($tripulante['tripulante']) . "'>";
}

echo "          </datalist>
            </div>
            <div class='filter-field'>
                <label for='mes'>Mês:</label>
                <select id='mes' name='mes'>
                    <option value=''>Mês</option>
                    <option value='Janeiro'" . ($filtro_mes === 'Janeiro' ? ' selected' : '') . ">Janeiro</option>
                    <option value='Fevereiro'" . ($filtro_mes === 'Fevereiro' ? ' selected' : '') . ">Fevereiro</option>
                    <option value='Março'" . ($filtro_mes === 'Março' ? ' selected' : '') . ">Março</option>
                    <option value='Abril'" . ($filtro_mes === 'Abril' ? ' selected' : '') . ">Abril</option>
                    <option value='Maio'" . ($filtro_mes === 'Maio' ? ' selected' : '') . ">Maio</option>
                    <option value='Junho'" . ($filtro_mes === 'Junho' ? ' selected' : '') . ">Junho</option>
                    <option value='Julho'" . ($filtro_mes === 'Julho' ? ' selected' : '') . ">Julho</option>
                    <option value='Agosto'" . ($filtro_mes === 'Agosto' ? ' selected' : '') . ">Agosto</option>
                    <option value='Setembro'" . ($filtro_mes === 'Setembro' ? ' selected' : '') . ">Setembro</option>
                    <option value='Outubro'" . ($filtro_mes === 'Outubro' ? ' selected' : '') . ">Outubro</option>
                    <option value='Novembro'" . ($filtro_mes === 'Novembro' ? ' selected' : '') . ">Novembro</option>
                    <option value='Dezembro'" . ($filtro_mes === 'Dezembro' ? ' selected' : '') . ">Dezembro</option>
                </select>
            </div>
            <div class='filter-field'>
                <label for='ano'>Ano:</label>
                <select id='ano' name='ano'>
                    <option value=''>Ano</option>";

// Adicionando opções de ano (2024 a 2030)
for ($ano = 2024; $ano <= 2030; $ano++) {
    echo "<option value='$ano'" . ($filtro_ano == $ano ? ' selected' : '') . ">$ano</option>";
}

echo "          </select>
            </div>
            <button type='submit' class='btn-filter'>Pesquisar</button>
            <a href='consulta.php' class='btn-clear'>Limpar Consulta</a>
        </form>
      </div>";

// Adicionando a div com rolagem vertical
echo "<div class='table-container'>
        <table>
            <thead>
                <tr>
                    <th>Tripulante</th>
                    <th>Mês</th>
                    <th>Ano</th>
                    <th>Pontuação Integridade</th>
                    <th>Monitoramento Proativo</th>
                    <th>Disponibilidade Servidor</th>
                    <th>Falha Logon</th>
                    <th>Cobertura Antivírus</th>
                    <th>Cobertura Atualização Patches</th>
                    <th>Cobertura Web Protection</th>
                    <th>Total Dispositivos</th>
                    <th>Tipo Desktop</th>
                    <th>Tipo Notebook</th>
                    <th>Tipo Servidor</th>
                    <th>Alertas Resolvidos</th>
                    <th>Ameaças Detectadas</th>
                    <th>Patches Instalados</th>
                    <th>Acessos Remotos</th>
                    <th>Web Protection Filtradas/Bloqueadas</th>
                    <th>Web Protection Mal Intencionadas/Bloqueadas</th>
                    <th>Backup Completo</th>
                    <th>Backup com Erro</th>
                    <th>Backup com Falha</th>
                    <th>Chamados Abertos</th>
                    <th>Chamados Fechados</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>";

// Renderizar registros da consulta
foreach ($registros as $registro) {
    echo "<tr>
            <td>" . htmlspecialchars($registro['tripulante']) . "</td>
            <td>" . htmlspecialchars($registro['mes']) . "</td>
            <td>" . htmlspecialchars($registro['ano']) . "</td>
            <td>" . htmlspecialchars($registro['pontuacao_integridade']) . "</td>
            <td>" . htmlspecialchars($registro['monitoramento_proativo']) . "</td>
            <td>" . htmlspecialchars($registro['disponibilidade_servidor']) . "</td>
            <td>" . htmlspecialchars($registro['falha_logon']) . "</td>
            <td>" . htmlspecialchars($registro['cobertura_antivirus']) . "</td>
            <td>" . htmlspecialchars($registro['cobertura_atualizacao_patches']) . "</td>
            <td>" . htmlspecialchars($registro['cobertura_web_protection']) . "</td>
            <td>" . htmlspecialchars($registro['total_dispositivos']) . "</td>
            <td>" . htmlspecialchars($registro['tipo_desktop']) . "</td>
            <td>" . htmlspecialchars($registro['tipo_notebook']) . "</td>
            <td>" . htmlspecialchars($registro['tipo_servidor']) . "</td>
            <td>" . htmlspecialchars($registro['alertas_resolvidos']) . "</td>
            <td>" . htmlspecialchars($registro['ameacas_detectadas']) . "</td>
            <td>" . htmlspecialchars($registro['patches_instalados']) . "</td>
            <td>" . htmlspecialchars($registro['acessos_remotos']) . "</td>
            <td>" . htmlspecialchars($registro['web_protection_filtradas_bloqueadas']) . "</td>
            <td>" . htmlspecialchars($registro['web_protection_mal_intencionadas_bloqueadas']) . "</td>
            <td>" . htmlspecialchars($registro['bkp_completo']) . "</td>
            <td>" . htmlspecialchars($registro['bkp_com_erro']) . "</td>
            <td>" . htmlspecialchars($registro['bkp_com_falha']) . "</td>
            <td>" . htmlspecialchars($registro['num_chamados_abertos']) . "</td>
            <td>" . htmlspecialchars($registro['num_chamados_fechados']) . "</td>
            <td>
                <a href='editar.php?id=" . $registro['id'] . "'>Editar</a> | 
                <a href='deletar.php?id=" . $registro['id'] . "' class='delete' onclick='return confirm(\"Tem certeza que deseja deletar?\")'>Deletar</a>
            </td>
          </tr>";
}

echo "</tbody></table></div>";

echo '</body></html>';
?>
