<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\usuarios.php
// Gestión de Usuarios — Adaptador de entrada UI (Arquitectura Hexagonal)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvUsuarioRepository;
use App\Application\UseCases\Usuario\GetAllUsuariosUseCase;

$usuarios      = [];
$backendOnline = false;

try {
    $usuarioRepo = new SqlsrvUsuarioRepository();
    $useCase = new GetAllUsuariosUseCase($usuarioRepo);
    $usuariosEntities = $useCase->execute();
    
    foreach ($usuariosEntities as $u) {
        $usuarios[] = $u->toArray();
    }
    $backendOnline = true;
} catch (Throwable $e) {
    $backendOnline = false;
}

$page_title = "Gestión de Usuarios";
include 'components/head.php';
include 'components/sidebar.php';
?>
<div class="page-wrapper">
  <?php
  $search_placeholder = "Buscar usuarios...";
  $search_oninput     = "filterTable(this.value)";
  include 'components/topbar.php';
  ?>
  <main class="page-content">
    <div class="page-header" data-aos="fade-up">
      <div class="breadcrumb-custom">
        <a href="index.php">Inicio</a><span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span><span>Usuarios</span>
      </div>
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h1>Gestión de Usuarios</h1>
          <p>Administra los roles y accesos del personal técnico, docentes y administradores.</p>
        </div>
      </div>
    </div>

    <!-- TABLA -->
    <div class="card-panel" data-aos="fade-up" data-aos-delay="50">
      <div class="card-panel-header">
        <h5><i class="fa-solid fa-users" style="color:var(--primary)"></i> Personal del Sistema</h5>
        <span id="totalCount" style="font-size:0.82rem;color:var(--text-muted);"></span>
      </div>
      <div style="overflow-x:auto;">
        <table class="table-custom" id="tablaUsers">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre Completo</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="tbodyUsers"></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php ob_start(); ?>
<script>
let usuarios = <?php echo json_encode($usuarios); ?>;
const backendOnline = <?php echo $backendOnline ? 'true' : 'false'; ?>;

const rolColors = {
  'Admin':        '#e0e7ff|#3730a3',
  'Administrador':'#e0e7ff|#3730a3',
  'Soporte':      '#d1fae5|#065f46',
  'Profesor':     '#fef3c7|#92400e',
  'default':      '#f1f5f9|#334155'
};

function renderTable(data) {
  const tbody = document.getElementById('tbodyUsers');
  document.getElementById('totalCount').textContent = `${data.length} usuario(s)`;
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);">Sin usuarios para mostrar</td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(u => {
    const rolKey = u.rol || 'default';
    const [rbg, rcolor] = (rolColors[rolKey] || rolColors['default']).split('|');
    const estadoActivo = (u.estado || '').toLowerCase() === 'activo';
    const initials = (u.nombre || '?').split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
    return `<tr>
      <td><span style="color:var(--primary);font-weight:700;">#${u.id.substring(0,8).toUpperCase()}</span></td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;flex-shrink:0;">${escapeHtml(initials)}</div>
          <span style="font-weight:500;">${escapeHtml(u.nombre || 'Sin nombre')}</span>
        </div>
      </td>
      <td style="color:var(--text-muted);font-family:monospace;">@${escapeHtml(u.nombre_usuario || '')}</td>
      <td style="color:var(--text-muted);">${escapeHtml(u.email || '—')}</td>
      <td><span class="badge-estado" style="background:${rbg};color:${rcolor};">${escapeHtml(u.rol || 'Sin rol')}</span></td>
      <td>
        <span class="badge-estado ${estadoActivo ? 'badge-abierta' : 'badge-resuelta'}" style="font-size:0.72rem;">
          ${estadoActivo ? '● Activo' : '○ Inactivo'}
        </span>
      </td>
    </tr>`;
  }).join('');
}

function filterTable(q) {
  renderTable(usuarios.filter(u =>
    (u.nombre||'').toLowerCase().includes(q.toLowerCase()) ||
    (u.nombre_usuario||'').toLowerCase().includes(q.toLowerCase()) ||
    (u.email||'').toLowerCase().includes(q.toLowerCase()) ||
    (u.rol||'').toLowerCase().includes(q.toLowerCase())
  ));
}

function escapeHtml(text) {
  return String(text ?? '').replace(/[&<>"']/g, m =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
  );
}

renderTable(usuarios);
</script>
<?php
$extra_js = ob_get_clean();
include 'components/footer.php';
?>
