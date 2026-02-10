<?php
require_once 'config/db.php';
require_once 'classes/Relatorio.php';

$kpiData = Relatorio::getKPIData();
$monthlyTrends = Relatorio::getMonthlyTrends();
$clientsData = Relatorio::getClientsData();

include 'includes/header.php';
?>

<div class="dashboard-container">
    <h1>Dashboard RMM</h1>
    
    <!-- Cards de KPI -->
    <div class="kpi-cards">
        <div class="kpi-card">
            <h3>Pontuação Média</h3>
            <div class="kpi-value"><?= number_format($kpiData['pontuacao_media'], 2) ?>%</div>
        </div>
        
        <div class="kpi-card">
            <h3>Disponibilidade</h3>
            <div class="kpi-value"><?= number_format($kpiData['disponibilidade_media'], 2) ?>%</div>
        </div>
        
        <div class="kpi-card">
            <h3>Cobertura AV</h3>
            <div class="kpi-value"><?= number_format($kpiData['cobertura_av'], 2) ?>%</div>
        </div>
        
        <div class="kpi-card">
            <h3>Alertas Resolvidos</h3>
            <div class="kpi-value"><?= $kpiData['total_alertas'] ?></div>
        </div>
    </div>
    
    <!-- Gráfico de Tendência (usando CanvasJS) -->
    <div class="chart-container">
        <h2>Tendência Mensal</h2>
        <div id="trendChart" style="height: 300px;"></div>
    </div>
    
    <!-- Tabela de Clientes -->
    <div class="table-container">
        <h2>Desempenho por Cliente</h2>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Pontuação</th>
                    <th>Cobertura AV</th>
                    <th>Relatórios</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientsData as $client): ?>
                <tr>
                    <td><?= htmlspecialchars($client['cliente']) ?></td>
                    <td><?= number_format($client['pontuacao'], 2) ?>%</td>
                    <td><?= number_format($client['cobertura_av'], 2) ?>%</td>
                    <td><?= $client['total_relatorios'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script>
// Gráfico de tendência
window.onload = function() {
    var chart = new CanvasJS.Chart("trendChart", {
        theme: "light2",
        animationEnabled: true,
        title: { text: "Evolução Mensal" },
        axisY: { suffix: "%", maximum: 100 },
        data: [{
            type: "line",
            dataPoints: [
                <?php foreach ($monthlyTrends as $trend): ?>
                { label: "<?= $trend['periodo'] ?>", y: <?= $trend['pontuacao'] ?> },
                <?php endforeach; ?>
            ]
        }]
    });
    chart.render();
}
</script>

<?php include 'includes/footer.php'; ?>
