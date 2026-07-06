<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\incidencias.php
// Gestión de Incidencias — Adaptador de entrada UI (Arquitectura Hexagonal)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Application\UseCases\Incidencia\GetAllIncidenciasUseCase;
use App\Application\UseCases\Aula\GetAllAulasUseCase;

$aulas         = [];
$incidencias   = [];
$backendOnline = false;

try {
    $incidenciaRepo = new SqlsrvIncidenciaRepository();
    $aulaRepo       = new SqlsrvAulaRepository();

    // Cargar todas las aulas para el select del modal
    $aulasUseCase  = new GetAllAulasUseCase($aulaRepo);
    $aulasEntities = $aulasUseCase->execute();
    foreach ($aulasEntities as $a) {
        $aulas[] = $a->toArray();
    }

    // Cargar todas las incidencias
    $incidenciasUseCase  = new GetAllIncidenciasUseCase($incidenciaRepo);
    $incidenciasEntities = $incidenciasUseCase->execute();
    foreach ($incidenciasEntities as $i) {
        $incidencias[] = $i->toArray();
    }

    $backendOnline = true;
} catch (Throwable $e) {
    $backendOnline = false;
}

$page_title = "Gestión de Incidencias";
include 'components/head.php';
include 'components/sidebar.php';
?>

<div class="page-wrapper">
  <?php
  $search_placeholder = "Buscar incidencias...";
  $search_oninput     = "filterTable(this.value)";
  include 'components/topbar.php';
  ?>

  <main class="page-content">
    <div class="page-header" data-aos="fade-up">
      <div class="breadcrumb-custom">
        <a href="index.php">Inicio</a><span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span><span>Incidencias</span>
      </div>
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h1>Gestión de Incidencias</h1>
          <p>Registra, asigna y da seguimiento a todas las incidencias del sistema.</p>
        </div>
        <button class="btn-sky" data-bs-toggle="modal" data-bs-target="#modalInc">
          <i class="fa-solid fa-plus"></i> Nueva Incidencia
        </button>
      </div>
    </div>

    <!-- Filtros rápidos -->
    <div class="row g-2 mb-4" data-aos="fade-up">
      <div class="col-12">
        <div class="card-panel" style="padding:1rem 1.5rem;">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <span style="font-size:0.83rem;font-weight:600;color:var(--text-muted);">Filtrar por:</span>
            <select class="form-control-custom form-select-custom py-1 px-3" style="width:auto;font-size:0.83rem;" onchange="filterByEstado(this.value)">
              <option value="">Estado (Todos)</option>
              <option value="abierta">Abierta</option>
              <option value="en_proceso">En Proceso</option>
              <option value="resuelta">Resuelta</option>
              <option value="critica">Crítica</option>
            </select>
            <select class="form-control-custom form-select-custom py-1 px-3" style="width:auto;font-size:0.83rem;" onchange="filterByPrioridad(this.value)">
              <option value="">Prioridad (Todas)</option>
              <option value="alta">Alta</option>
              <option value="media">Media</option>
              <option value="baja">Baja</option>
            </select>
            <button class="btn-sky-outline py-1 px-3" onclick="exportCSV()"><i class="fa-solid fa-file-export me-1"></i>Exportar CSV</button>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLA -->
    <div class="card-panel" data-aos="fade-up" data-aos-delay="50">
      <div class="card-panel-header">
        <h5><i class="fa-solid fa-list-check" style="color:var(--primary)"></i> Listado de Incidencias</h5>
        <span id="totalCount" style="font-size:0.82rem;color:var(--text-muted);"></span>
      </div>
      <div style="overflow-x:auto;">
        <table class="table-custom" id="tablaInc">
          <thead>
            <tr>
              <th>ID</th>
              <th>Título</th>
              <th>Aula</th>
              <th>Reportado por</th>
              <th>Estado</th>
              <th>Prioridad</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyInc"></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- MODAL NUEVA INCIDENCIA -->
<div class="modal fade modal-custom" id="modalInc" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Registrar Nueva Incidencia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formInc">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label-custom">Título de la Incidencia *</label>
              <input type="text" class="form-control-custom" id="incTitulo" placeholder="Describe brevemente el problem" required>
            </div>
            <div class="col-md-4">
              <label class="form-label-custom">Prioridad *</label>
              <select class="form-control-custom form-select-custom" id="incPrioridad" required>
                <option value="">Seleccionar...</option>
                <option value="alta">🔴 Alta</option>
                <option value="media">🟡 Media</option>
                <option value="baja">🟢 Baja</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label-custom">Aula Afectada</label>
              <select class="form-control-custom form-select-custom" id="incAula">
                <option value="">Sin aula / Seleccionar...</option>
                <?php foreach ($aulas as $a): ?>
                  <option value="<?php echo htmlspecialchars($a['id']); ?>">
                    <?php echo htmlspecialchars($a['nombre']) . ' (' . htmlspecialchars($a['edificio'] ?? '') . ')'; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label-custom">Reportado por</label>
              <input type="text" class="form-control-custom" id="incReportado" placeholder="Nombre del solicitante">
            </div>
            <div class="col-12">
              <label class="form-label-custom">Descripción detallada</label>
              <textarea class="form-control-custom" id="incDesc" rows="4" placeholder="Describe el problema con el mayor detalle posible..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer gap-2">
        <button class="btn-sky-outline" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn-sky" onclick="addIncidencia()"><i class="fa-solid fa-floppy-disk me-1"></i>Crear Incidencia</button>
      </div>
    </div>
  </div>
</div>

<?php ob_start(); ?>
<script>
const API_INCIDENCIAS = 'api/incidencias.php';

let incidencias = <?php echo json_encode($incidencias); ?>;
const backendOnline = <?php echo $backendOnline ? 'true' : 'false'; ?>;

const estadoBadge = { abierta:'badge-abierta', en_proceso:'badge-proceso', resuelta:'badge-resuelta', critica:'badge-critica' };
const estadoLabel = { abierta:'Abierta', en_proceso:'En Proceso', resuelta:'Resuelta', critica:'Crítica' };
const prioColors  = { alta:'#fee2e2|#991b1b', media:'#fef3c7|#92400e', baja:'#d1fae5|#065f46' };

function renderTable(data) {
  const tbody = document.getElementById('tbodyInc');
  document.getElementById('totalCount').textContent = `${data.length} resultado(s)`;
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted);">Sin incidencias para mostrar</td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(i => {
    const pKey    = (i.prioridad || 'media').toLowerCase();
    const [pbg, pcolor] = (prioColors[pKey] || '#e2e8f0|#334155').split('|');
    const idShort = (i.id || '').substring(0, 8).toUpperCase();
    const estKey  = (i.estado || 'abierta').toLowerCase();
    const estBadge = estadoBadge[estKey] || 'badge-abierta';
    const estLbl   = estadoLabel[estKey] || i.estado;
    const dateFormatted = i.inserted_at ? new Date(i.inserted_at).toLocaleString('es-ES') : '—';
    return `<tr>
      <td><span style="color:var(--primary);font-weight:700;">#${idShort}</span></td>
      <td style="max-width:220px;font-weight:500;">${escapeHtml(i.titulo)}</td>
      <td><span class="badge bg-light text-dark border" style="font-size:0.78rem;">${escapeHtml(i.aula_nombre || 'No asignada')}</span></td>
      <td style="color:var(--text-muted);">${escapeHtml(i.reportado_por || 'Anónimo')}</td>
      <td><span class="badge-estado ${estBadge} badge-dot">${estLbl}</span></td>
      <td><span class="badge-estado" style="background:${pbg};color:${pcolor};">${capitalize(pKey)}</span></td>
      <td style="color:var(--text-muted);font-size:0.8rem;">${dateFormatted}</td>
      <td>
        <div class="d-flex gap-1">
          <button class="topbar-btn" style="width:32px;height:32px;font-size:0.75rem;" title="Ver detalle" onclick="verDetalle('${i.id}')"><i class="fa-solid fa-eye"></i></button>
          <a href="chat.php?incidencia_id=${i.id}" class="topbar-btn d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.75rem;" title="Ir al Chat de Soporte"><i class="fa-solid fa-comments" style="color:var(--primary)"></i></a>
          ${estKey !== 'resuelta' ? `<button class="topbar-btn" style="width:32px;height:32px;font-size:0.75rem;" title="Resolver" onclick="resolverInc('${i.id}')"><i class="fa-solid fa-check" style="color:var(--success)"></i></button>` : ''}
        </div>
      </td>
    </tr>`;
  }).join('');
}

function filterTable(q) {
  renderTable(incidencias.filter(i =>
    (i.titulo || '').toLowerCase().includes(q.toLowerCase()) ||
    (i.aula_nombre || '').toLowerCase().includes(q.toLowerCase()) ||
    (i.reportado_por || '').toLowerCase().includes(q.toLowerCase())
  ));
}

let filtroEstado = '', filtroPrioridad = '';
function filterByEstado(v)   { filtroEstado = v;    applyFilters(); }
function filterByPrioridad(v){ filtroPrioridad = v; applyFilters(); }
function applyFilters() {
  let data = incidencias;
  if (filtroEstado)    data = data.filter(i => (i.estado||'').toLowerCase() === filtroEstado.toLowerCase());
  if (filtroPrioridad) data = data.filter(i => (i.prioridad||'').toLowerCase() === filtroPrioridad.toLowerCase());
  renderTable(data);
}

async function addIncidencia() {
  const titulo = document.getElementById('incTitulo').value.trim();
  if (!titulo) { alert('El título es obligatorio.'); return; }
  const body = {
    titulo,
    descripcion: document.getElementById('incDesc').value.trim() || '',
    prioridad:   document.getElementById('incPrioridad').value || 'media',
    aula_id:     document.getElementById('incAula').value || null,
    reportado_por: document.getElementById('incReportado').value.trim() || 'Anónimo',
    estado: 'abierta'
  };
  try {
    const res = await fetch(`${API_INCIDENCIAS}?action=create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    if (!res.ok) throw new Error('Error al registrar incidencia');
    bootstrap.Modal.getInstance(document.getElementById('modalInc')).hide();
    document.getElementById('formInc').reset();
    window.location.reload();
  } catch (error) {
    alert('No se pudo guardar la incidencia: ' + error.message);
  }
}

function verDetalle(id) {
  const i = incidencias.find(x => x.id === id);
  if (!i) return;
  const idShort = i.id.substring(0, 8).toUpperCase();
  const df = i.inserted_at ? new Date(i.inserted_at).toLocaleString('es-ES') : '—';
  alert(`📋 Ficha de Incidencia #${idShort}\n\nTítulo: ${i.titulo}\nAula: ${i.aula_nombre || 'No asignada'}\nReportado por: ${i.reportado_por || 'Anónimo'}\nEstado: ${capitalize(i.estado)}\nPrioridad: ${capitalize(i.prioridad)}\nFecha: ${df}\n\nDescripción:\n${i.descripcion || 'Sin descripción'}`);
}

async function resolverInc(id) {
  const i = incidencias.find(x => x.id === id);
  if (!i || i.estado === 'resuelta') return;
  if (confirm(`¿Marcar la incidencia #${id.substring(0,8).toUpperCase()} como resuelta?`)) {
    try {
      const res = await fetch(`${API_INCIDENCIAS}?action=resolver&id=${encodeURIComponent(id)}`, { method: 'PUT' });
      if (!res.ok) throw new Error('Error al resolver la incidencia');
      window.location.reload();
    } catch (error) {
      alert('Error: ' + error.message);
    }
  }
}

function exportCSV() {
  const header = 'ID,Titulo,Aula,Reportado,Estado,Prioridad,Fecha\n';
  const rows = incidencias.map(i => `"${i.id}","${(i.titulo||'').replace(/"/g,'""')}","${(i.aula_nombre||'No asignada').replace(/"/g,'""')}","${(i.reportado_por||'Anónimo').replace(/"/g,'""')}","${i.estado}","${i.prioridad}","${i.inserted_at}"`).join('\n');
  const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
  a.download = 'incidencias.csv'; a.click();
}

function escapeHtml(text) {
  return String(text ?? '').replace(/[&<>"']/g, m =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
  );
}
function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
}

applyFilters();
</script>
<?php
$extra_js = ob_get_clean();
include 'components/footer.php';
?>
