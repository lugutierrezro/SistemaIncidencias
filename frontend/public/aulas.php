<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\aulas.php
// Gestión de Aulas — Adaptador de entrada UI (Arquitectura Hexagonal)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Application\UseCases\Aula\GetAllAulasUseCase;

$aulas         = [];
$backendOnline = false;

try {
    $aulaRepo = new SqlsrvAulaRepository();
    $useCase  = new GetAllAulasUseCase($aulaRepo);
    $aulasEntities = $useCase->execute();
    
    foreach ($aulasEntities as $a) {
        $aulas[] = $a->toArray();
    }
    $backendOnline = true;
} catch (Throwable $e) {
    $backendOnline = false;
}

$page_title = "Gestión de Aulas";
$extra_css  = '
  <style>
    .aula-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.25rem; }
    .filter-tab { padding: 0.5rem 1.25rem; border-radius: 99px; border: 1.5px solid var(--border); background: transparent; font-size: 0.83rem; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: var(--transition); }
    .filter-tab.active, .filter-tab:hover { background: var(--primary); color: white; border-color: var(--primary); }
    .equip-tag { display: inline-flex; align-items: center; gap: 4px; background: var(--sky-100); color: var(--sky-700); padding: 3px 10px; border-radius: 99px; font-size: 0.72rem; font-weight: 600; }
  </style>
';

include 'components/head.php';
include 'components/sidebar.php';
?>

<div class="page-wrapper">
  <?php
  $search_placeholder = "Buscar aulas...";
  $search_oninput     = "filterAulas(this.value)";
  include 'components/topbar.php';
  ?>

  <main class="page-content">
    <div class="page-header" data-aos="fade-up">
      <div class="breadcrumb-custom">
        <a href="index.php">Inicio</a><span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span><span>Gestión de Aulas</span>
      </div>
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h1>Gestión de Aulas</h1>
          <p>Administra, registra y supervisa todas las aulas del centro educativo.</p>
        </div>
        <button class="btn-sky" data-bs-toggle="modal" data-bs-target="#modalAula">
          <i class="fa-solid fa-plus"></i> Nueva Aula
        </button>
      </div>
    </div>

    <!-- Metric mini cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="0">
        <div class="card-panel text-center py-3">
          <div style="font-size:2rem;font-weight:800;color:var(--sky-700);" id="metric-total">0</div>
          <div style="font-size:0.8rem;color:var(--text-muted);">Total Aulas</div>
        </div>
      </div>
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="50">
        <div class="card-panel text-center py-3">
          <div style="font-size:2rem;font-weight:800;color:#10b981;" id="metric-disponibles">0</div>
          <div style="font-size:0.8rem;color:var(--text-muted);">Disponibles</div>
        </div>
      </div>
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
        <div class="card-panel text-center py-3">
          <div style="font-size:2rem;font-weight:800;color:#f59e0b;" id="metric-mantenimiento">0</div>
          <div style="font-size:0.8rem;color:var(--text-muted);">En Mantenimiento</div>
        </div>
      </div>
      <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="150">
        <div class="card-panel text-center py-3">
          <div style="font-size:2rem;font-weight:800;color:#ef4444;" id="metric-incidencias">0</div>
          <div style="font-size:0.8rem;color:var(--text-muted);">Con Incidencias</div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="d-flex gap-2 mb-4 flex-wrap" data-aos="fade-up">
      <button class="filter-tab active" onclick="setFilter('todos', this)">Todas</button>
      <button class="filter-tab" onclick="setFilter('disponible', this)">Disponibles</button>
      <button class="filter-tab" onclick="setFilter('mantenimiento', this)">Mantenimiento</button>
      <button class="filter-tab" onclick="setFilter('incidencia', this)">Con Incidencias</button>
      <div class="ms-auto d-flex gap-2">
        <button class="topbar-btn" title="Vista cuadrícula" id="btnGrid" onclick="setView('grid')" style="border-color:var(--primary);color:var(--primary);"><i class="fa-solid fa-grip"></i></button>
        <button class="topbar-btn" title="Vista lista" id="btnList" onclick="setView('list')"><i class="fa-solid fa-list"></i></button>
      </div>
    </div>

    <!-- Grid de aulas -->
    <div class="aula-grid" id="aulaGrid" data-aos="fade-up" data-aos-delay="100">
      <!-- Se renderizará dinámicamente -->
    </div>

  </main>
</div>

<!-- Modal nueva aula -->
<div class="modal fade modal-custom" id="modalAula" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-school me-2"></i>Registrar Nueva Aula</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAula">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-custom">Nombre del Aula *</label>
              <input type="text" class="form-control-custom" id="aulaNombre" placeholder="Ej: Aula 101, Laboratorio A" required>
            </div>
            <div class="col-md-6">
              <label class="form-label-custom">Edificio / Bloque</label>
              <input type="text" class="form-control-custom" id="aulaEdificio" placeholder="Ej: Edificio Principal, Bloque B">
            </div>
            <div class="col-md-4">
              <label class="form-label-custom">Capacidad (personas) *</label>
              <input type="number" class="form-control-custom" id="aulaCapacidad" placeholder="Ej: 30" min="1" max="200" required>
            </div>
            <div class="col-md-4">
              <label class="form-label-custom">Piso / Nivel</label>
              <select class="form-control-custom form-select-custom" id="aulaPiso">
                <option value="">Seleccionar...</option>
                <option>Planta Baja</option>
                <option>Piso 1</option>
                <option>Piso 2</option>
                <option>Piso 3</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-custom">Estado inicial</label>
              <select class="form-control-custom form-select-custom" id="aulaEstado">
                <option value="disponible">Disponible</option>
                <option value="mantenimiento">Mantenimiento</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label-custom">Equipamiento</label>
              <div class="d-flex gap-2 flex-wrap">
                <label style="cursor:pointer;"><input type="checkbox" name="equip" value="proyector"> <span class="equip-tag ms-1">📽️ Proyector</span></label>
                <label style="cursor:pointer;"><input type="checkbox" name="equip" value="pcs"> <span class="equip-tag ms-1">💻 Computadoras</span></label>
                <label style="cursor:pointer;"><input type="checkbox" name="equip" value="pizarra"> <span class="equip-tag ms-1">🖊️ Pizarra Digital</span></label>
                <label style="cursor:pointer;"><input type="checkbox" name="equip" value="ac"> <span class="equip-tag ms-1">❄️ Aire Acondicionado</span></label>
                <label style="cursor:pointer;"><input type="checkbox" name="equip" value="wifi"> <span class="equip-tag ms-1">📶 WiFi</span></label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer gap-2">
        <button class="btn-sky-outline" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn-sky" onclick="addAula()"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar Aula</button>
      </div>
    </div>
  </div>
</div>

<?php ob_start(); ?>
<script>
const API_AULAS = 'api/aulas.php';

const emojis  = { disponible:['🏫','🖥️','🎓','📚','🔬'], mantenimiento:['🔧','⚙️'], incidencia:['⚠️'] };
const colores = ['#0ea5e9','#6366f1','#10b981','#f59e0b','#0369a1','#7c3aed'];
const equipNames = { proyector:'📽️ Proyector', pcs:'💻 PCs', pizarra:'🖊️ Pizarra Digital', ac:'❄️ A/C', wifi:'📶 WiFi' };
const estadoConfig = {
  disponible:  { label:'Disponible',    bg:'#d1fae5', color:'#065f46' },
  mantenimiento:{ label:'Mantenimiento', bg:'#fef3c7', color:'#92400e' },
  incidencia:  { label:'Con Incidencia',bg:'#fee2e2', color:'#991b1b' }
};

let aulaData = <?php echo json_encode($aulas); ?>;
let filtroActivo = 'todos';

function updateMetrics() {
  document.getElementById('metric-total').textContent       = aulaData.length;
  document.getElementById('metric-disponibles').textContent  = aulaData.filter(a => a.estado==='disponible').length;
  document.getElementById('metric-mantenimiento').textContent= aulaData.filter(a => a.estado==='mantenimiento').length;
  document.getElementById('metric-incidencias').textContent  = aulaData.filter(a => a.estado==='incidencia').length;
}

function renderAulas(data) {
  const grid = document.getElementById('aulaGrid');
  if (!data.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:4rem;color:var(--text-muted);"><i class="fa-solid fa-school" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:1rem;"></i>No se encontraron aulas</div>`;
    return;
  }
  grid.innerHTML = data.map((a, i) => {
    const estadoRaw = (a.estado || '').toLowerCase();
    let estadoKey = 'disponible';
    if (estadoRaw.includes('manten')) estadoKey = 'mantenimiento';
    else if (estadoRaw.includes('inciden')) estadoKey = 'incidencia';
    const ec = estadoConfig[estadoKey] || estadoConfig.disponible;
    const color = colores[i % colores.length];
    const emoji = emojis[estadoKey]?.[Math.floor(Math.random()*5)] ?? '🏫';
    const equipoList = (a.equipamiento || '').split(',').map(e => e.trim()).filter(e => e !== '');
    const equipTags = equipoList.map(e => `<span class="equip-tag">${equipNames[e]||e}</span>`).join('');
    return `
    <div class="aula-card" onclick="openDetalleAula('${a.id}')">
      <div class="aula-status">
        <span style="display:inline-block;background:${ec.bg};color:${ec.color};padding:3px 10px;border-radius:99px;font-size:0.7rem;font-weight:700;">${ec.label}</span>
      </div>
      <div class="aula-card-img" style="background:linear-gradient(135deg,${color},${color}aa);">
        <span style="font-size:2.5rem;">${emoji}</span>
      </div>
      <div class="aula-name">${escapeHtml(a.nombre)}</div>
      <div class="aula-meta" style="margin-bottom:0.75rem;">
        <span><i class="fa-solid fa-building"></i> ${escapeHtml(a.edificio || 'N/A')}</span>
        <span><i class="fa-solid fa-stairs"></i> ${escapeHtml(a.piso || 'N/A')}</span>
        <span><i class="fa-solid fa-users"></i> ${a.capacidad} personas</span>
      </div>
      <div class="d-flex flex-wrap gap-1">${equipTags || `<span style="font-size:0.75rem;color:var(--text-muted);">Sin equipamiento registrado</span>`}</div>
    </div>`;
  }).join('');
}

function setFilter(f, btn) {
  filtroActivo = f;
  document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

function applyFilters() {
  const filtered = filtroActivo === 'todos' ? aulaData : aulaData.filter(a => (a.estado||'').toLowerCase().includes(filtroActivo));
  renderAulas(filtered);
}

function filterAulas(q) {
  renderAulas(aulaData.filter(a =>
    (a.nombre||'').toLowerCase().includes(q.toLowerCase()) ||
    (a.edificio||'').toLowerCase().includes(q.toLowerCase())
  ));
}

async function addAula() {
  const nombre   = document.getElementById('aulaNombre').value.trim();
  const edificio = document.getElementById('aulaEdificio').value.trim() || 'Sin especificar';
  const capacidad= parseInt(document.getElementById('aulaCapacidad').value) || 30;
  const piso     = document.getElementById('aulaPiso').value || 'Planta Baja';
  const estado   = document.getElementById('aulaEstado').value;
  const equipo   = [...document.querySelectorAll('input[name="equip"]:checked')].map(el => el.value);
  if (!nombre) { alert('El nombre del aula es obligatorio.'); return; }
  try {
    const res = await fetch(API_AULAS, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, edificio, capacidad, piso, estado, equipamiento: equipo.join(',') })
    });
    if (!res.ok) throw new Error('Error al crear aula');
    bootstrap.Modal.getInstance(document.getElementById('modalAula')).hide();
    document.getElementById('formAula').reset();
    window.location.reload();
  } catch (error) {
    alert('No se pudo crear el aula: ' + error.message);
  }
}

function openDetalleAula(id) {
  window.location.href = `detalle_aula.php?id=${encodeURIComponent(id)}`;
}

function setView(v) {
  const grid = document.getElementById('aulaGrid');
  if (v === 'list') {
    grid.style.gridTemplateColumns = '1fr';
    document.getElementById('btnList').style.color = 'var(--primary)';
    document.getElementById('btnGrid').style.color = 'var(--text-muted)';
  } else {
    grid.style.gridTemplateColumns = '';
    document.getElementById('btnGrid').style.color = 'var(--primary)';
    document.getElementById('btnList').style.color = 'var(--text-muted)';
  }
}

function escapeHtml(text) {
  return String(text ?? '').replace(/[&<>"']/g, m =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
  );
}

// Iniciar
updateMetrics();
applyFilters();
</script>
<?php
$extra_js = ob_get_clean();
include 'components/footer.php';
?>
