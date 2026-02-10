// /dashboard/js/main.js
(function () {
  const elCliente = document.getElementById('cliente');
  const elAno = document.getElementById('ano');
  const elMes = document.getElementById('mes');
  const btnAplicar = document.getElementById('btn-aplicar');
  const btnUltimo = document.getElementById('btn-ultimo');

  const elKpiTotal = document.getElementById('kpi-total');
  const elKpiCliente = document.getElementById('kpi-cliente');
  const elKpiAntivirus = document.getElementById('kpi-antivirus');
  const elKpiPatch = document.getElementById('kpi-patch');
  const elKpiWeb = document.getElementById('kpi-web');
  const elKpiDisp = document.getElementById('kpi-disp');
  const elKpiDevices = document.getElementById('kpi-devices');
  const elKpiRemote = document.getElementById('kpi-remote');

  const elTrendAno = document.getElementById('trend-ano');
  const elDevPeriodo = document.getElementById('dev-periodo');

  let trendChart, deviceChart;

  function fmtPct(v) {
    if (v === null || v === undefined || isNaN(v)) return '—';
    // Permite casas decimais quando necessário
    const n = Number(v);
    if (Number.isInteger(n)) return `${n}%`;
    return `${n.toFixed(1)}%`;
    // se preferir 0 casas, use toFixed(0)
  }

  function fmtInt(v) {
    if (v === null || v === undefined || isNaN(v)) return '—';
    return Number(v).toLocaleString('pt-BR');
  }

  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  function buildQS(params) {
    const usp = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') usp.set(k, v);
    });
    return usp.toString();
  }

  async function loadClients() {
    try {
      const list = await fetchJSON(API.clients);
      elCliente.innerHTML = '<option value="">Todos</option>' + list.map(c => `<option value="${String(c)}">${String(c)}</option>`).join('');
    } catch (e) {
      console.warn('Falha ao carregar clientes', e);
    }
  }

  async function loadKPIs({ cliente, ano, mes, ultimo = 0 }) {
    const qs = buildQS({ cliente, ano, mes, ultimo });
    const data = await fetchJSON(`${API.kpis}?${qs}`);
    const { periodo, kpis } = data;

    elAno.value = periodo.ano;
    elMes.value = String(periodo.mes);

    elKpiTotal.textContent = fmtInt(kpis.total_registros);
    elKpiCliente.textContent = cliente && cliente.length ? cliente : 'Todos';
    elKpiAntivirus.textContent = fmtPct(kpis.antivirus_avg);
    elKpiPatch.textContent = fmtPct(kpis.patch_avg);
    elKpiWeb.textContent = fmtPct(kpis.web_avg);
    elKpiDisp.textContent = fmtPct(kpis.disponibilidade_avg);
    elKpiDevices.textContent = fmtInt(kpis.dispositivos_total);
    elKpiRemote.textContent = fmtInt(kpis.acesso_remoto_total);

    return periodo;
  }

  async function loadTrend({ cliente, ano }) {
    const qs = buildQS({ cliente, ano });
    const data = await fetchJSON(`${API.trends}?${qs}`);

    elTrendAno.textContent = String(data.ano);

    const labels = data.categories;
    const s = data.series;

    const datasets = [
      {
        label: 'Antivírus',
        data: s.antivirus_avg,
        borderColor: '#2b8a3e',
        backgroundColor: 'rgba(43,138,62,0.15)',
        tension: 0.3
      },
      {
        label: 'Patching',
        data: s.patch_avg,
        borderColor: '#1971c2',
        backgroundColor: 'rgba(25,113,194,0.15)',
        tension: 0.3
      },
      {
        label: 'Web Protection',
        data: s.web_avg,
        borderColor: '#ae3ec9',
        backgroundColor: 'rgba(174,62,201,0.15)',
        tension: 0.3
      },
      {
        label: 'Disponibilidade',
        data: s.disponibilidade_avg,
        borderColor: '#e67700',
        backgroundColor: 'rgba(230,119,0,0.15)',
        tension: 0.3
      }
    ];

    const ctx = document.getElementById('trendChart').getContext('2d');
    if (trendChart) trendChart.destroy();
    trendChart = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'top' },
          tooltip: { callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${fmtPct(ctx.parsed.y)}`
          }}
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: (v) => `${v}%`
            }
          }
        }
      }
    });
  }

  async function loadDevices({ cliente, ano, mes, ultimo = 0 }) {
    const qs = buildQS({ cliente, ano, mes, ultimo });
    const data = await fetchJSON(`${API.devices}?${qs}`);
    elDevPeriodo.textContent = `${String(data.periodo.mes).padStart(2,'0')}/${data.periodo.ano}`;

    const labels = data.series.map(s => s.name);
    const values = data.series.map(s => s.value);

    const ctx = document.getElementById('deviceChart').getContext('2d');
    if (deviceChart) deviceChart.destroy();
    deviceChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: ['#1971c2','#2b8a3e','#ae3ec9','#e67700','#0ca678','#6741d9','#fa5252']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const label = ctx.label || '';
                const val = ctx.parsed || 0;
                return `${label}: ${fmtInt(val)}`;
              }
            }
          }
        }
      }
    });
  }

  async function bootstrap() {
    await loadClients();

    // Inicializa com último período
    const cliente = '';
    const periodo = await loadKPIs({ cliente, ultimo: 1 });
    await loadTrend({ cliente, ano: periodo.ano });
    await loadDevices({ cliente, ano: periodo.ano, mes: periodo.mes });
  }

  btnAplicar.addEventListener('click', async () => {
    const cliente = elCliente.value;
    const ano = parseInt(elAno.value, 10) || undefined;
    const mes = parseInt(elMes.value, 10) || undefined;

    const periodo = await loadKPIs({ cliente, ano, mes, ultimo: 0 });
    await loadTrend({ cliente, ano: periodo.ano });
    await loadDevices({ cliente, ano: periodo.ano, mes: periodo.mes });
  });

  btnUltimo.addEventListener('click', async () => {
    const cliente = elCliente.value || '';
    const periodo = await loadKPIs({ cliente, ultimo: 1 });
    await loadTrend({ cliente, ano: periodo.ano });
    await loadDevices({ cliente, ano: periodo.ano, mes: periodo.mes });
  });

  bootstrap();
})();
