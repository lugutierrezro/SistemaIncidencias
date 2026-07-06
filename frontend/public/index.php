<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\index.php
// Dashboard — Adaptador de entrada UI (Arquitectura Hexagonal)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Application\UseCases\Dashboard\GetDashboardStatsUseCase;

$stats = [
    'open_count'           => 0,
    'resolved_count'       => 0,
    'aulas_count'          => 0,
    'recent_incidents'     => [],
    'avg_resolution_hours' => 0.0,
    'chart_donut'          => [0, 0, 0],
    'timeline'             => []
];

$backendOnline = false;
try {
    $incidenciaRepo = new SqlsrvIncidenciaRepository();
    $aulaRepo = new SqlsrvAulaRepository();
    $useCase = new GetDashboardStatsUseCase($incidenciaRepo, $aulaRepo);
    
    $stats = $useCase->execute();
    $backendOnline = true;
} catch (Throwable $e) {
    $backendOnline = false;
}

$page_title = "Dashboard";
include 'components/head.php';
include 'components/sidebar.php';
?>

<!-- PAGE WRAPPER -->
<div class="page-wrapper">
  <?php
  $search_placeholder = "Buscar incidencias, aulas, usuarios...";
  include 'components/topbar.php';
  ?>

  <!-- CONTENT -->
  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header" data-aos="fade-up">
      <div class="breadcrumb-custom">
        <a href="#">Inicio</a>
        <span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span>
        <span>Dashboard</span>
      </div>
      <h1>Panel de Control</h1>
      <p>Resumen general del sistema de incidencias — <span id="fecha-actual"></span></p>
    </div>

    <!-- METRIC CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-xl-3 col-sm-6 fade-up fade-up-1">
        <div class="card-metric">
          <div class="metric-icon" style="background:#dbeafe; color:#1d4ed8;">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div class="metric-value" id="cnt-abiertas">0</div>
          <div class="metric-label">Incidencias Abiertas</div>
          <div class="metric-delta delta-down"><i class="fa-solid fa-arrow-up"></i> +3 hoy</div>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 fade-up fade-up-2">
        <div class="card-metric">
          <div class="metric-icon" style="background:#d1fae5; color:#059669;">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="metric-value" id="cnt-resueltas">0</div>
          <div class="metric-label">Resueltas este mes</div>
          <div class="metric-delta delta-up"><i class="fa-solid fa-arrow-up"></i> +12%</div>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 fade-up fade-up-3">
        <div class="card-metric">
          <div class="metric-icon" style="background:#fef3c7; color:#d97706;">
            <i class="fa-solid fa-school"></i>
          </div>
          <div class="metric-value" id="cnt-aulas">0</div>
          <div class="metric-label">Aulas Activas</div>
          <div class="metric-delta delta-flat"><i class="fa-solid fa-minus"></i> Sin cambios</div>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 fade-up fade-up-4">
        <div class="card-metric">
          <div class="metric-icon" style="background:#ede9fe; color:#7c3aed;">
            <i class="fa-solid fa-clock"></i>
          </div>
          <div class="metric-value" id="val-promedio">0h</div>
          <div class="metric-label">Tiempo Promedio Res.</div>
          <div class="metric-delta delta-up"><i class="fa-solid fa-arrow-down"></i> -18 min</div>
        </div>
      </div>
    </div>

    <!-- CHARTS + ACTIVITY -->
    <div class="row g-3 mb-4">
      <!-- Main Chart -->
      <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
        <div class="card-panel h-100">
          <div class="card-panel-header">
            <h5><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Incidencias por Semana</h5>
            <div class="d-flex gap-2">
              <select class="form-control-custom form-select-custom py-1 px-2" style="font-size:0.8rem;width:auto;">
                <option>Últimas 8 semanas</option>
                <option>Este mes</option>
              </select>
            </div>
          </div>
          <div class="card-panel-body">
            <canvas id="chartSemanal" height="100"></canvas>
          </div>
        </div>
      </div>
      <!-- Donut Chart -->
      <div class="col-lg-4" data-aos="fade-up" data-aos-delay="150">
        <div class="card-panel h-100">
          <div class="card-panel-header">
            <h5><i class="fa-solid fa-chart-pie" style="color:var(--accent)"></i> Por Estado</h5>
          </div>
          <div class="card-panel-body text-center">
            <canvas id="chartDonut" height="180"></canvas>
            <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap" style="font-size:0.78rem;">
              <span><span style="width:10px;height:10px;background:#f59e0b;display:inline-block;border-radius:2px;margin-right:4px;"></span>Abiertas</span>
              <span><span style="width:10px;height:10px;background:#0ea5e9;display:inline-block;border-radius:2px;margin-right:4px;"></span>En Proceso</span>
              <span><span style="width:10px;height:10px;background:#10b981;display:inline-block;border-radius:2px;margin-right:4px;"></span>Resueltas</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLE + TIMELINE -->
    <div class="row g-3">
      <!-- Recent Incidents Table -->
      <div class="col-lg-7" data-aos="fade-up" data-aos-delay="50">
        <div class="card-panel">
          <div class="card-panel-header">
            <h5><i class="fa-solid fa-list-check" style="color:var(--primary)"></i> Incidencias Recientes</h5>
            <a href="incidencias.php" class="btn-sky-outline py-1 px-3" style="font-size:0.8rem;">Ver todas</a>
          </div>
          <div style="overflow-x:auto;">
            <table class="table-custom">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Descripción</th>
                  <th>Aula</th>
                  <th>Estado</th>
                  <th>Prioridad</th>
                </tr>
              </thead>
              <tbody id="tbody-incidencias">
                <!-- Se cargará dinámicamente -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- Activity Timeline -->
      <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
        <div class="card-panel h-100">
          <div class="card-panel-header">
            <h5><i class="fa-solid fa-timeline" style="color:var(--accent)"></i> Actividad Reciente</h5>
          </div>
          <div class="card-panel-body">
            <div class="timeline" id="timelineContainer">
              <!-- Se cargará dinámicamente -->
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  const initialStats  = <?php echo json_encode($stats); ?>;
  const backendOnline = <?php echo $backendOnline ? 'true' : 'false'; ?>;

  const d = new Date();
  document.getElementById('fecha-actual').textContent = d.toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

  function animateCount(id, end, duration, suffix = '') {
    let start = 0;
    const el = document.getElementById(id);
    if (!el) return;
    if (end === 0) { el.textContent = '0' + suffix; return; }
    const step = end / (duration / 16);
    const timer = setInterval(() => {
      start += step;
      if (start >= end) { el.textContent = end + suffix; clearInterval(timer); }
      else { el.textContent = Math.floor(start) + suffix; }
    }, 16);
  }

  function displayDashboardData(data) {
    animateCount('cnt-abiertas',  data.open_count,           1000);
    animateCount('cnt-resueltas', data.resolved_count,        1000);
    animateCount('cnt-aulas',     data.aulas_count,           1000);
    const h = parseFloat(data.avg_resolution_hours);
    document.getElementById('val-promedio').textContent = isNaN(h) ? '0h' : h.toFixed(1) + 'h';

    const tbody = document.getElementById('tbody-incidencias');
    if (!data.recent_incidents || !data.recent_incidents.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sin incidencias recientes</td></tr>';
    } else {
      tbody.innerHTML = data.recent_incidents.map(i => {
        const idShort = (i.id || '').substring(0, 8).toUpperCase();
        const estadoCls = { abierta:'badge-abierta', en_proceso:'badge-proceso', resuelta:'badge-resuelta', critica:'badge-critica' }[i.estado] || 'badge-abierta';
        const prioColors = { alta:'background:#fee2e2;color:#991b1b;', media:'background:#fef3c7;color:#92400e;', baja:'background:#d1fae5;color:#065f46;' };
        const prioStyle = prioColors[(i.prioridad || '').toLowerCase()] || 'background:#e2e8f0;color:#334155;';
        return `
          <tr>
            <td><span style="color:var(--primary);font-weight:700;">#${idShort}</span></td>
            <td>${escapeHtml(i.titulo)}</td>
            <td><span class="badge bg-light text-dark border">${escapeHtml(i.aula_nombre || 'No asignada')}</span></td>
            <td><span class="badge-estado ${estadoCls} badge-dot">${capitalize(i.estado)}</span></td>
            <td><span class="badge-estado" style="${prioStyle}">${capitalize(i.prioridad || 'media')}</span></td>
          </tr>`;
      }).join('');
    }

    initDonutChart(data.chart_donut);

    const timelineBox = document.getElementById('timelineContainer');
    if (!data.timeline || !data.timeline.length) {
      timelineBox.innerHTML = '<div class="text-center text-muted py-3">Sin actividad reciente</div>';
    } else {
      timelineBox.innerHTML = data.timeline.map(act => {
        const typeCls = act.type || 'primary';
        const date = new Date(act.time_str);
        const tf = isNaN(date.getTime()) ? (act.time_str || '') : date.toLocaleTimeString('es-ES', { hour:'2-digit', minute:'2-digit' });
        return `
          <div class="timeline-item">
            <div class="timeline-dot ${typeCls === 'primary' ? '' : typeCls}"></div>
            <div class="timeline-text">${escapeHtml(act.text)}</div>
            <div class="timeline-time"><i class="fa-regular fa-clock me-1"></i>${tf}</div>
          </div>`;
      }).join('');
    }
  }

  function loadDashboardData() {
    if (backendOnline) {
      displayDashboardData(initialStats);
    } else {
      animateCount('cnt-abiertas',  24,  1000);
      animateCount('cnt-resueltas', 148, 1000);
      animateCount('cnt-aulas',     12,  1000);
      document.getElementById('val-promedio').textContent = '1.5h';
      initDonutChart([24, 18, 148]);
      document.getElementById('tbody-incidencias').innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-muted py-3">
            <i class="fa-solid fa-database me-1"></i> No se pudo conectar con la base de datos
          </td>
        </tr>`;
    }
  }

  let donutChartInstance = null;
  function initDonutChart(chartData) {
    const ctx = document.getElementById('chartDonut');
    if (!ctx) return;
    if (donutChartInstance) donutChartInstance.destroy();
    donutChartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Abiertas', 'En Proceso', 'Resueltas'],
        datasets: [{ data: chartData, backgroundColor: ['#f59e0b','#0ea5e9','#10b981'], borderWidth: 0, hoverOffset: 6 }]
      },
      options: {
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: '#0c4a6e', bodyColor: '#e0f2fe', padding: 10, cornerRadius: 8 }
        }
      }
    });
  }

  const ctxLine = document.getElementById('chartSemanal').getContext('2d');
  const grad = ctxLine.createLinearGradient(0, 0, 0, 300);
  grad.addColorStop(0, 'rgba(14,165,233,0.3)');
  grad.addColorStop(1, 'rgba(14,165,233,0)');
  new Chart(ctxLine, {
    type: 'line',
    data: {
      labels: ['Sem 1','Sem 2','Sem 3','Sem 4','Sem 5','Sem 6','Sem 7','Sem 8'],
      datasets: [{
        label: 'Incidencias',
        data: [12, 19, 8, 24, 16, 21, 14, 24],
        borderColor: '#0ea5e9', backgroundColor: grad,
        borderWidth: 2.5, pointBackgroundColor: '#0ea5e9',
        pointRadius: 4, pointHoverRadius: 7, fill: true, tension: 0.4
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0c4a6e', titleColor: '#bae6fd', bodyColor: '#e0f2fe', padding: 10, cornerRadius: 8 } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
        y: { grid: { color: '#f0f9ff' }, ticks: { color: '#64748b', font: { size: 11 } } }
      }
    }
  });

  function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, m =>
      ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
    );
  }
  function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
  }

  loadDashboardData();
</script>
<?php
$extra_js = ob_get_clean();
include 'components/footer.php';
?>
