<?php
echo "<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    color: #333;
}

table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
}

th, td {
    text-align: left;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

th {
    background-color: #007bff;
    color: white;
}

form {
    background-color: #ffffff;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #ddd;
    box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);
}

input[type=text], input[type=number], select {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

input[type=submit] {
    width: 100%;
    background-color: #007bff;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

input[type=submit]:hover {
    background-color: #0056b3;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>";

// Configurações de conexão com o banco de dados
$servidor = "localhost";
$usuario = "somaxi"; //xamp
$senha = "S0m4x1@193"; // xamp
$banco_de_dados = "dados_tripulantes_tsi";


// Criando a conexão
$conn = new mysqli($servidor, $usuario, $senha, $banco_de_dados);

// Checando a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Atualização dos Dados
if (isset($_POST['atualizar'])) {
    $sql = $conn->prepare("UPDATE tabela_dados_tsi SET tripulante=?, mes=?, total_dispositivos=?, tipo_desktop=?, tipo_notebook=?, tipo_servidor=?, disponibilidade_servidor=?, alertas_resolvidos=?, ameacas_detectadas=?, patches_instalados=?, acessos_remotos=?, pontuacao_integridade=?, num_chamados_abertos=?, num_chamados_fechados=?, monitoramento_proativo=?, falha_logon=?, cobertura_antivirus=?, cobertura_atualizacao_patches=?, cobertura_web_protection=?, web_protection_filtradas_bloqueadas=?, web_protection_mal_intencionadas_bloqueadas=?, bkp_completo=?, bkp_com_erro=?, bkp_com_falha=? WHERE id=?");

    $sql->bind_param("ssisssssssssiiisssssssssi", $_POST['tripulante'], $_POST['mes'], $_POST['total_dispositivos'], $_POST['tipo_desktop'], $_POST['tipo_notebook'], $_POST['tipo_servidor'], $_POST['disponibilidade_servidor'], $_POST['alertas_resolvidos'], $_POST['ameacas_detectadas'], $_POST['patches_instalados'], $_POST['acessos_remotos'], $_POST['pontuacao_integridade'], $_POST['num_chamados_abertos'], $_POST['num_chamados_fechados'], $_POST['monitoramento_proativo'], $_POST['falha_logon'], $_POST['cobertura_antivirus'], $_POST['cobertura_atualizacao_patches'], $_POST['cobertura_web_protection'], $_POST['web_protection_filtradas_bloqueadas'], $_POST['web_protection_mal_intencionadas_bloqueadas'], $_POST['bkp_completo'], $_POST['bkp_com_erro'], $_POST['bkp_com_falha'], $_POST['id']);

    if ($sql->execute()) {
        echo "Registro atualizado com sucesso!";
     //header("Location: index2.php"); // Redireciona para index2.php
	  header("Location: consulta.php"); // Redireciona para consulta.php
    exit; // Encerra a execução do script
    } else {
        echo "Erro ao atualizar o registro: " . $conn->error;
    }
    $sql->close();
}

// Deletando um registro
if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    $sql = $conn->prepare("DELETE FROM tabela_dados_tsi WHERE id = ?");
    $sql->bind_param("i", $id);
    if ($sql->execute()) {
        echo "Registro deletado com sucesso!";
    //header("Location: index2.php"); // Redireciona para index2.php
	  header("Location: consulta.php"); // Redireciona para consulta.php

    exit; // Encerra a execução do script
    } else {
        echo "Erro ao deletar registro: " . $conn->error;
    }
    $sql->close();
}
// Formulário de Edição
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = $conn->prepare("SELECT * FROM tabela_dados_tsi WHERE id = ?");
    $sql->bind_param("i", $id);
    $sql->execute();
    $resultado = $sql->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        echo "<form method='post'>";
        echo "<input type='hidden' name='id' value='".$id."'>";
        // Inclua todos os campos do formulário aqui
        echo "Tripulante: <input type='text' name='tripulante' value='".$row['tripulante']."'><br>";
        echo "Mês: <input type='text' name='mes' value='".$row['mes']."'><br>";
        echo "Total de Dispositivos: <input type='number' name='total_dispositivos' value='".$row['total_dispositivos']."'><br>";
        echo "Tipo Desktop: <input type='text' name='tipo_desktop' value='".$row['tipo_desktop']."'><br>";
        echo "Tipo Notebook: <input type='text' name='tipo_notebook' value='".$row['tipo_notebook']."'><br>";
        echo "Tipo Servidor: <input type='text' name='tipo_servidor' value='".$row['tipo_servidor']."'><br>";
        echo "Disponibilidade Servidor: <input type='text' name='disponibilidade_servidor' value='".$row['disponibilidade_servidor']."'><br>";
        echo "Alertas Resolvidos: <input type='number' name='alertas_resolvidos' value='".$row['alertas_resolvidos']."'><br>";
        echo "Ameaças Detectadas: <input type='number' name='ameacas_detectadas' value='".$row['ameacas_detectadas']."'><br>";
        echo "Patches Instalados: <input type='number' name='patches_instalados' value='".$row['patches_instalados']."'><br>";
        echo "Acessos Remotos: <input type='number' name='acessos_remotos' value='".$row['acessos_remotos']."'><br>";
        echo "Pontuação Integridade: <input type='number' name='pontuacao_integridade' value='".$row['pontuacao_integridade']."'><br>";
        echo "Nº Chamados Abertos: <input type='number' name='num_chamados_abertos' value='".$row['num_chamados_abertos']."'><br>";
        echo "Nº Chamados Fechados: <input type='number' name='num_chamados_fechados' value='".$row['num_chamados_fechados']."'><br>";
        echo "Monitoramento Proativo: <input type='text' name='monitoramento_proativo' value='".$row['monitoramento_proativo']."'><br>";
        echo "Falha Logon: <input type='number' name='falha_logon' value='".$row['falha_logon']."'><br>";
        echo "Cobertura Antivirus: <input type='text' name='cobertura_antivirus' value='".$row['cobertura_antivirus']."'><br>";
        echo "Cobertura Atualização Patches: <input type='text' name='cobertura_atualizacao_patches' value='".$row['cobertura_atualizacao_patches']."'><br>";
        echo "Cobertura Web Protection: <input type='text' name='cobertura_web_protection' value='".$row['cobertura_web_protection']."'><br>";
        echo "Web Protection Filtradas Bloqueadas: <input type='number' name='web_protection_filtradas_bloqueadas' value='".$row['web_protection_filtradas_bloqueadas']."'><br>";
        echo "Web Protection Mal Intencionadas Bloqueadas: <input type='number' name='web_protection_mal_intencionadas_bloqueadas' value='".$row['web_protection_mal_intencionadas_bloqueadas']."'><br>";
        echo "Backup Completo: <input type='number' name='bkp_completo' value='".$row['bkp_completo']."'><br>";
        echo "Backup com Erro: <input type='number' name='bkp_com_erro' value='".$row['bkp_com_erro']."'><br>";
        echo "Backup com Falha: <input type='number' name='bkp_com_falha' value='".$row['bkp_com_falha']."'><br>";

        echo "<input type='submit' name='atualizar' value='Atualizar'>";
        echo "</form>";
    } else {
        echo "Registro não encontrado.";
    }
    $sql->close();
}



// Formulário de busca para filtragem
echo "<form method='GET' action=''>";
echo "Tripulante: <input type='text' name='filtro_tripulante'>";
echo "Mês: <input type='text' name='filtro_mes'>";
echo "<input type='submit' value='Filtrar'>";
echo "</form>";

// Filtragem
$filtro_tripulante = isset($_GET['filtro_tripulante']) ? $_GET['filtro_tripulante'] : '';
$filtro_mes = isset($_GET['filtro_mes']) ? $_GET['filtro_mes'] : '';

$sql = "SELECT * FROM tabela_dados_tsi WHERE 1=1";

if (!empty($filtro_tripulante)) {
    $sql .= " AND tripulante LIKE '%" . $conn->real_escape_string($filtro_tripulante) . "%'";
}

if (!empty($filtro_mes)) {
    $sql .= " AND mes LIKE '%" . $conn->real_escape_string($filtro_mes) . "%'";
}

// Listagem de Registros com filtragem aplicada
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>Tripulante</th>
            <th>Mês</th>
            <th>Total de Dispositivos</th>
            <th>Tipo Desktop</th>
            <th>Tipo Notebook</th>
            <th>Tipo Servidor</th>
            <th>Disponibilidade Servidor</th>
            <th>Alertas Resolvidos</th>
            <th>Ameaças Detectadas</th>
            <th>Patches Instalados</th>
            <th>Acessos Remotos</th>
            <th>Pontuação Integridade</th>
            <th>Nº Chamados Abertos</th>
            <th>Nº Chamados Fechados</th>
            <th>Monitoramento Proativo</th>
            <th>Falha Logon</th>
            <th>Cobertura Antivirus</th>
            <th>Cobertura Atualização Patches</th>
            <th>Cobertura Web Protection</th>
            <th>Web Protection Filtradas Bloqueadas</th>
            <th>Web Protection Mal Intencionadas Bloqueadas</th>
            <th>Backup Completo</th>
            <th>Backup com Erro</th>
            <th>Backup com Falha</th>
            <th>Ações</th>
          </tr>";
    while($row = $resultado->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['id']."</td>";
        echo "<td>".$row['tripulante']."</td>";
        echo "<td>".$row['mes']."</td>";
        echo "<td>".$row['total_dispositivos']."</td>";
        echo "<td>".$row['tipo_desktop']."</td>";
        echo "<td>".$row['tipo_notebook']."</td>";
        echo "<td>".$row['tipo_servidor']."</td>";
        echo "<td>".$row['disponibilidade_servidor']."</td>";
        echo "<td>".$row['alertas_resolvidos']."</td>";
        echo "<td>".$row['ameacas_detectadas']."</td>";
        echo "<td>".$row['patches_instalados']."</td>";
        echo "<td>".$row['acessos_remotos']."</td>";
        echo "<td>".$row['pontuacao_integridade']."</td>";
        echo "<td>".$row['num_chamados_abertos']."</td>";
        echo "<td>".$row['num_chamados_fechados']."</td>";
        echo "<td>".$row['monitoramento_proativo']."</td>";
        echo "<td>".$row['falha_logon']."</td>";
        echo "<td>".$row['cobertura_antivirus']."</td>";
        echo "<td>".$row['cobertura_atualizacao_patches']."</td>";
        echo "<td>".$row['cobertura_web_protection']."</td>";
        echo "<td>".$row['web_protection_filtradas_bloqueadas']."</td>";
        echo "<td>".$row['web_protection_mal_intencionadas_bloqueadas']."</td>";
        echo "<td>".$row['bkp_completo']."</td>";
        echo "<td>".$row['bkp_com_erro']."</td>";
        echo "<td>".$row['bkp_com_falha']."</td>";
        echo "<td><a href='?editar=".$row['id']."'>Editar</a> | <a href='?deletar=".$row['id']."' onclick=\"return confirm('Tem certeza que deseja deletar este registro?');\">Deletar</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "0 registros encontrados.";
}
$conn->close();
?>

