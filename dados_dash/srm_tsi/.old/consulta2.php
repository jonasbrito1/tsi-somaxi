<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão TSI - Filtro</title>
</head>
<body>
    <h1>Gestão de TSI</h1>
    
    <!-- Formulário para filtragem -->
    <h2>Filtrar Registros</h2>
    <form method="GET">
        <!-- Campos para filtragem -->
        <div>
            <input type="text" name="tripulante" placeholder="Tripulante">
            <input type="month" name="mes" placeholder="Mês">
        </div>
        <button type="submit">Filtrar</button>
    </form>
    
    <!-- Tabela de Registros -->
    <h2>Registros</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Tripulante</th>
                <th>Mês</th>
                <th>Total de Dispositivos</th>
                <th>Tipo de Desktop</th>
                <th>Tipo de Notebook</th>
                <th>Tipo de Servidor</th>
                <th>Disponibilidade do Servidor</th>
                <th>Alertas Resolvidos</th>
                <!-- Adicione mais cabeçalhos conforme necessário -->
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Exemplo básico de filtragem em PHP
            if (isset($_GET['tripulante']) || isset($_GET['mes'])) {
                $tripulante = $_GET['tripulante'];
                $mes = $_GET['mes'];
                
                // Conexão ao banco de dados
                // $pdo = new PDO('mysql:host=seuHost;dbname=seuBanco', 'usuario', 'senha');
                
                // Preparando a consulta SQL
                $sql = "SELECT * FROM tabela_dados_tsi WHERE tripulante LIKE :tripulante AND mes LIKE :mes";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':tripulante' => '%' . $tripulante . '%',
                    ':mes' => '%' . $mes . '%'
                ]);
                
                // Loop para exibir os resultados
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['tripulante']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['mes']) . "</td>";
                    // Continue imprimindo os outros campos
                    echo "<td>" . htmlspecialchars($row['total_dispositivos']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['tipo_desktop']) . "</td>";
                    // Adicione mais campos conforme necessário
                    echo "<td>Ações</td>"; // Substitua por ações reais
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
</body>
</html>
