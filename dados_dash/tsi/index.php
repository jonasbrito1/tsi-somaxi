<?php
// /dashboard/index.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard RMM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="./css/style.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
  <header class="topbar">
    <h1>Relatórios RMM</h1>
  </header>

  <main class="container">
    <section class="filters">
      <div class="filter-item">
        <label for="cliente">Cliente</label>
        <select id="cliente">
          <option value="">Todos</option>
        </select>
      </div>
      <div class="filter-item">
        <label for="ano">Ano</label>
        <input type="number" id="ano" min="2000" max="2100" />
      </div>
      <div class="filter-item">
        <label for="mes">Mês</label>
        <select id="mes">
          <option value="1">Jan</option>
          <option value="2">Fev</option>
          <option value="3">Mar</option>
          <option value="4">Abr</option>
          <option value="5">Mai</option>
          <option value="6">Jun</option>
          <option value="7">Jul</option>
          <option value="8">Ago</option>
          <option value="9">Set</option>
          <option value="10">Out</option>
          <option value="11">Nov</option>
          <option value="12">Dez</option>
        </select>
      </div>
      <div class="filter-actions">
        <button id="btn-aplicar">Aplicar</button>
        <button id="btn-ultimo">Último período</button>
      </div>
    </section>

    <section class="kpis">
      <div class="card kpi">
        <div class="kpi-title">Registros</div>
        <div class="kpi-value" id="kpi-total">—</div>
        <div class="kpi-badge" id="kpi-cliente">Todos</div>
      </div>
      <div class="card kpi">
        <div class="kpi-title">Antivírus (média)</div>
        <div class="kpi-value" id="kpi-antivirus">—</div>
      </div>
      <div class="card kpi">
        <div class="kpi-title">Patching (média)</div>
        <div class="kpi-value" id="kpi-patch">—</div>
      </div>
      <div class="card kpi">
        <div class="kpi-title">Web Protection (média)</div>
        <div class="kpi-value" id="kpi-web">—</div>
      </div>
      <div class="card kpi">
        <div class="kpi-title">Disponibilidade Servidores (média)</div>
        <div class="kpi-value" id="kpi-disp">—</div>
      </div>
      <div class="card kpi">
        <div class="kpi-title">Dispositivos Gerenciados</div>
        <div class="kpi-value" id="kpi-devices">—</div>
        <div class="kpi-badge">Acesso remoto total: <span id="kpi-remote">—</span></div>
      </div>
    </section>

    <section class="charts">
      <div class="card chart-card">
        <div class="chart-header">
          <h3>Tendência mensal no ano</h3>
          <span class="chart-sub" id="trend-ano">—</span>
        </div>
        <canvas id="trendChart" height="100"></canvas>
      </div>

      <div class="card chart-card">
        <div class="chart-header">
          <h3>Distribuição de dispositivos</h3>
          <span class="chart-sub" id="dev-periodo">—</span>
        </div>
        <canvas id="deviceChart" height="100"></canvas>
      </div>
    </section>
  </main>

  <footer class="footer">
    <small>MSP Dashboard • RMM</small>
  </footer>

  <script>
    const API = {
      clients: './api/get_clients.php',
      kpis: './api/kpis.php',
      trends: './api/trends.php',
      devices: './api/device_breakdown.php'
    };
  </script>
  <script src="./js/main.js"></script>
</body>
</html>
