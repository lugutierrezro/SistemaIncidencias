<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\detalle_aula.php
// Vista de Detalle de Aula — Adaptador de entrada UI (Arquitectura Hexagonal)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Application\UseCases\Aula\GetAulaDetailsUseCase;

$id = $_GET['id'] ?? null;
$aula = null;
$incidencias = [];
$backendOnline = false;

if ($id) {
    try {
        $aulaRepo = new SqlsrvAulaRepository();
        $incidenciaRepo = new SqlsrvIncidenciaRepository();
        $useCase = new GetAulaDetailsUseCase($aulaRepo, $incidenciaRepo);
        
        $details = $useCase->execute($id);
        if (!empty($details)) {
            $aula = $details['aula'];
            $incidencias = $details['incidencias'];
            $backendOnline = true;
        }
    } catch (Throwable $e) {
        $backendOnline = false;
    }
}

if (!$aula) {
    header("Location: aulas.php");
    exit;
}

$page_title = "Detalle de Aula — " . htmlspecialchars($aula['nombre']);
include 'components/head.php';
include 'components/sidebar.php';
?>

<div class="page-wrapper">
  <?php
  $search_placeholder = "Buscar incidencias en esta aula...";
  $search_oninput     = "filterLocalTable(this.value)";
  include 'components/topbar.php';
  ?>

  <main class="page-content">
    <!-- Breadcrumb & Header -->
    <div class="page-header" data-aos="fade-up">
      <div class="breadcrumb-custom">
        <a href="index.php">Inicio</a>
        <span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span>
        <a href="aulas.php">Aulas</a>
        <span><i class="fa-solid fa-chevron-right" style="font-size:0.6rem"></i></span>
        <span>Detalle</span>
      </div>
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h1>Aula: <?php echo htmlspecialchars($aula['nombre']); ?></h1>
          <p>Ficha de información técnica y código de acceso QR para reportes rápidos.</p>
        </div>
        <a href="aulas.php" class="btn-sky-outline">
          <i class="fa-solid fa-arrow-left me-1"></i> Volver a Aulas
        </a>
      </div>
    </div>

    <div class="row g-4">
      <!-- Ficha de Datos -->
      <div class="col-lg-7" data-aos="fade-up" data-aos-delay="50">
        <div class="card-panel h-100">
          <div class="card-panel-header">
            <h5><i class="fa-solid fa-circle-info" style="color:var(--primary)"></i> Información General</h5>
            <span class="badge-estado badge-dot <?php 
              $est = strtolower($aula['estado']);
              if (strpos($est, 'manten') !== false) echo 'badge-proceso';
              elseif (strpos($est, 'inciden') !== false) echo 'badge-critica';
              else echo 'badge-resuelta';
            ?>">
              <?php echo htmlspecialchars(ucfirst($aula['estado'])); ?>
            </span>
          </div>
          
          <div class="row g-3 mt-1" style="font-size:0.92rem;">
            <div class="col-sm-6">
              <label class="text-muted" style="font-size:0.8rem; display:block;">Edificio / Pabellón</label>
              <strong><i class="fa-solid fa-building me-1 text-primary"></i> <?php echo htmlspecialchars($aula['edificio'] ?? 'No definido'); ?></strong>
            </div>
            <div class="col-sm-6">
              <label class="text-muted" style="font-size:0.8rem; display:block;">Piso / Nivel</label>
              <strong><i class="fa-solid fa-stairs me-1 text-primary"></i> <?php echo htmlspecialchars($aula['piso'] ?? 'No definido'); ?></strong>
            </div>
            <div class="col-sm-6">
              <label class="text-muted" style="font-size:0.8rem; display:block;">Capacidad Máxima</label>
              <strong><i class="fa-solid fa-users me-1 text-primary"></i> <?php echo htmlspecialchars($aula['capacidad']); ?> alumnos</strong>
            </div>
            <div class="col-sm-6">
              <label class="text-muted" style="font-size:0.8rem; display:block;">Tipo de Aula</label>
              <strong><i class="fa-solid fa-school me-1 text-primary"></i> Laboratorio / Especial</strong>
            </div>
            
            <div class="col-12 mt-4">
              <label class="text-muted" style="font-size:0.8rem; display:block; margin-bottom:0.4rem;">Equipamiento del Aula</label>
              <div class="d-flex gap-2 flex-wrap">
                <?php 
                  $eqList = array_filter(explode(',', $aula['equipamiento'] ?? ''));
                  $equipNames = ['proyector' => '📽️ Proyector', 'pcs' => '💻 PCs', 'pizarra' => '🖊️ Pizarra Digital', 'ac' => '❄️ Aire Acondicionado', 'wifi' => '📶 WiFi'];
                  if (empty($eqList)):
                ?>
                  <span class="text-muted" style="font-size:0.85rem;">Ningún equipo registrado actualmente.</span>
                <?php else: foreach ($eqList as $eq): ?>
                  <span class="badge bg-light text-dark border py-2 px-3" style="font-size:0.78rem; font-weight:600; border-radius:8px;">
                    <?php echo htmlspecialchars($equipNames[trim($eq)] ?? trim($eq)); ?>
                  </span>
                <?php endforeach; endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sección QR -->
      <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
        <div class="card-panel h-100 text-center d-flex flex-column justify-content-between">
          <div class="card-panel-header text-start">
            <h5><i class="fa-solid fa-qrcode" style="color:var(--accent)"></i> Acceso Rápido QR</h5>
          </div>
          
          <div class="py-3">
            <div style="background:#fff; padding:12px; border-radius:16px; box-shadow:0 8px 30px rgba(0,0,0,0.06); display:inline-block;">
              <img id="imgQr" src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($aula['qr_url']); ?>" alt="QR Aula" style="width:200px; height:200px;">
            </div>
            <p class="text-muted mt-3" style="font-size:0.8rem; max-width:280px; margin:0.8rem auto 0;">
              Imprime y pega este código en la entrada del aula. Los docentes podrán escanearlo para reportar fallas al instante.
            </p>
          </div>

          <div class="d-flex gap-2 justify-content-center mt-2">
            <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=<?php echo urlencode($aula['qr_url']); ?>" target="_blank" download="QR_<?php echo htmlspecialchars($aula['nombre']); ?>.png" class="btn-sky py-2 px-4" style="font-size:0.85rem;">
              <i class="fa-solid fa-download me-1"></i> Descargar QR (HD)
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Historial de Incidencias del Aula -->
    <div class="card-panel mt-4" data-aos="fade-up" data-aos-delay="150">
      <div class="card-panel-header">
        <h5><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Historial de Incidencias del Aula</h5>
        <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo count($incidencias); ?> incidencia(s) registradas</span>
      </div>
      
      <div style="overflow-x:auto;">
        <table class="table-custom" id="tablaIncAula">
          <thead>
            <tr>
              <th>ID</th>
              <th>Título / Reporte</th>
              <th>Reportado por</th>
              <th>Estado</th>
              <th>Prioridad</th>
              <th>Fecha de Reporte</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyIncAula">
            <?php 
              if (empty($incidencias)): 
            ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Sin incidencias registradas en esta aula.</td>
              </tr>
            <?php else: foreach ($incidencias as $inc): 
              $idShort = substr($inc['id'], 0, 8);
              $estVal = strtolower($inc['estado']);
              $estadoCls = ['abierta' => 'badge-abierta', 'en_proceso' => 'badge-proceso', 'resuelta' => 'badge-resuelta', 'critica' => 'badge-critica'][$estVal] ?? 'badge-abierta';
              
              $prioVal = strtolower($inc['prioridad'] ?? 'media');
              $prioColors = ['alta' => 'background:#fee2e2;color:#991b1b;', 'media' => 'background:#fef3c7;color:#92400e;', 'baja' => 'background:#d1fae5;color:#065f46;'];
              $prioStyle = $prioColors[$prioVal] ?? 'background:#e2e8f0;color:#334155;';
            ?>
              <tr>
                <td><span style="color:var(--primary); font-weight:700;">#<?php echo strtoupper($idShort); ?></span></td>
                <td style="font-weight:500; max-width:280px;"><?php echo htmlspecialchars($inc['titulo']); ?></td>
                <td class="text-muted"><?php echo htmlspecialchars($inc['reportado_por'] ?? 'Anónimo'); ?></td>
                <td><span class="badge-estado <?php echo $estadoCls; ?> badge-dot"><?php echo htmlspecialchars(ucfirst($inc['estado'])); ?></span></td>
                <td><span class="badge-estado" style="<?php echo $prioStyle; ?>"><?php echo htmlspecialchars(ucfirst($prioVal)); ?></span></td>
                <td class="text-muted" style="font-size:0.82rem;"><?php echo htmlspecialchars($inc['inserted_at'] ? date('d/m/Y H:i', strtotime($inc['inserted_at'])) : '—'); ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="chat.php?incidencia_id=<?php echo htmlspecialchars($inc['id']); ?>" class="topbar-btn d-inline-flex align-items-center justify-content-center" style="width:32px; height:32px; font-size:0.75rem;" title="Ver Chat de Soporte"><i class="fa-solid fa-comments" style="color:var(--primary)"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php ob_start(); ?>
<script>
function filterLocalTable(q) {
  const tbody = document.getElementById('tbodyIncAula');
  const rows = tbody.getElementsByTagName('tr');
  for (let i = 0; i < rows.length; i++) {
    const cells = rows[i].getElementsByTagName('td');
    if (cells.length < 2) continue;
    const match = cells[1].textContent.toLowerCase().includes(q.toLowerCase()) || 
                  cells[2].textContent.toLowerCase().includes(q.toLowerCase());
    rows[i].style.display = match ? '' : 'none';
  }
}
</script>
<?php
$extra_js = ob_get_clean();
include 'components/footer.php';
?>
