<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\reportar.php
// Reportar Incidencia desde QR (Público, sin credenciales)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvChatRepository;
use App\Application\UseCases\Incidencia\CreateIncidenciaUseCase;
use App\Domain\Entities\ChatMensaje;

$aulaId = $_GET['aula_id'] ?? null;
$aula = null;
$errorMsg = '';
$successId = null;

try {
    $aulaRepo = new SqlsrvAulaRepository();
    if ($aulaId) {
        $aula = $aulaRepo->findById($aulaId);
        if ($aula) {
            $aula = $aula->toArray();
        }
    }
} catch (Throwable $e) {
    $errorMsg = 'Error al conectar con el servidor.';
}

if (!$aula) {
    $errorMsg = 'Aula no válida o código QR incorrecto.';
}

// Procesar el formulario de envío
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aula) {
    $titulo        = $_POST['titulo'] ?? '';
    $descripcion   = $_POST['descripcion'] ?? '';
    $reportado_por = $_POST['reportado_por'] ?? 'Docente';
    $subcategoria  = $_POST['subcategoria_id'] ?? '1'; // Default Proyector
    $prioridad     = $_POST['prioridad'] ?? 'media';

    if (empty($titulo)) {
        $errorMsg = 'El asunto o título es requerido.';
    } else {
        try {
            $incidenciaRepo = new SqlsrvIncidenciaRepository();
            $chatRepo = new SqlsrvChatRepository();
            
            // 1. Crear incidencia en DB
            $useCase = new CreateIncidenciaUseCase($incidenciaRepo);
            $incidencia = $useCase->execute([
                'titulo'        => $titulo,
                'descripcion'   => $descripcion,
                'prioridad'     => $prioridad,
                'aula_id'       => $aula['id'],
                'reportado_por' => $reportado_por,
                'estado'        => 'abierta'
            ]);

            // Actualizar la subcategoría en la base de datos ya que el caso de uso base usa default
            $conn = \App\Infrastructure\Persistence\Sqlsrv\SqlsrvConnection::getConnection();
            sqlsrv_query($conn, 
                "UPDATE dbo.Incidencia SET id_subcategoria_incidencia = ? WHERE id_incidencia = ?", 
                [(int)$subcategoria, (int)$incidencia->id]
            );

            // 2. Insertar primer mensaje del chat para abrir la conversación
            $primerMsgContenido = "Incidencia reportada por el docente. Detalle: " . $descripcion;
            $msgEntity = new ChatMensaje(
                null,
                $incidencia->id,
                $primerMsgContenido,
                $reportado_por,
                'usuario'
            );
            $chatRepo->saveMensaje($msgEntity);

            // 3. Redireccionar al chat de soporte para el docente
            header("Location: chat_docente.php?incidencia_id=" . urlencode($incidencia->id));
            exit;
        } catch (Throwable $e) {
            $errorMsg = 'Error al registrar el reporte: ' . $e->getMessage();
        }
    }
}

// Cargar subcategorías para el selector
$subcategorias = [];
if ($aula) {
    try {
        $conn = \App\Infrastructure\Persistence\Sqlsrv\SqlsrvConnection::getConnection();
        $rSub = sqlsrv_query($conn, "SELECT id_subcategoria_incidencia AS id, nombre FROM dbo.SubcategoriaIncidencia WHERE estado = 'Activo'");
        while ($rSub && $rowSub = sqlsrv_fetch_array($rSub, SQLSRV_FETCH_ASSOC)) {
            $subcategorias[] = $rowSub;
        }
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportar Incidencia — Adex</title>
  
  <!-- FontAwesome + Google Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --primary: #0ea5e9;
      --primary-dark: #0284c7;
      --bg: #f8fafc;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --card-bg: rgba(255, 255, 255, 0.9);
    }
    
    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #e0f2fe, #f8fafc);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
    }

    .report-card {
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: 24px;
      box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
      max-width: 520px;
      width: 100%;
      padding: 2.25rem;
      position: relative;
      overflow: hidden;
    }

    .report-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 6px;
      background: linear-gradient(90deg, #0ea5e9, #6366f1);
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: linear-gradient(135deg, #38bdf8, #0ea5e9);
      color: white;
      font-size: 1.5rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    }

    .form-control-custom {
      background: #f8fafc;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      transition: all 0.2s;
    }

    .form-control-custom:focus {
      background: #fff;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
      outline: none;
    }

    .form-label-custom {
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--text-dark);
      margin-bottom: 0.4rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    .btn-sky {
      background: linear-gradient(135deg, #38bdf8, #0ea5e9);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 0.85rem 1.5rem;
      font-weight: 600;
      transition: all 0.25s;
      box-shadow: 0 4px 15px rgba(14, 165, 233, 0.25);
    }

    .btn-sky:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(14, 165, 233, 0.35);
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
    }

    .classroom-banner {
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-radius: 14px;
      padding: 0.85rem 1.25rem;
      margin-bottom: 1.75rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .classroom-banner i {
      font-size: 1.6rem;
      color: var(--primary);
    }
  </style>
</head>
<body>

  <div class="report-card">
    <div class="text-center">
      <div class="brand-logo">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <h3 class="fw-bold mb-1">Reportar Incidencia</h3>
      <p class="text-muted mb-4" style="font-size:0.9rem;">Soporte Técnico Inmediato sin inicio de sesión.</p>
    </div>

    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2" style="border-radius:12px; font-size:0.9rem;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?php echo htmlspecialchars($errorMsg); ?></div>
      </div>
    <?php endif; ?>

    <?php if ($aula): ?>
      <div class="classroom-banner">
        <i class="fa-solid fa-school"></i>
        <div>
          <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Aula Detectada</div>
          <strong style="font-size:1.05rem; color:var(--text-dark);"><?php echo htmlspecialchars($aula['nombre']); ?></strong>
          <span style="font-size:0.8rem; color:var(--text-muted);">— <?php echo htmlspecialchars($aula['edificio'] ?? 'Pabellón'); ?></span>
        </div>
      </div>

      <form method="POST">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label-custom">Nombre Completo (Opcional)</label>
            <input type="text" name="reportado_por" class="form-control-custom w-100" placeholder="Ej: Prof. Gómez" value="Docente">
          </div>

          <div class="col-md-6">
            <label class="form-label-custom">Categoría del problema *</label>
            <select name="subcategoria_id" class="form-control-custom form-select w-100" required>
              <?php foreach ($subcategorias as $sub): ?>
                <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label-custom">Prioridad estimada</label>
            <select name="prioridad" class="form-control-custom form-select w-100">
              <option value="media">🟡 Media</option>
              <option value="alta">🔴 Alta (Afecta la clase)</option>
              <option value="baja">🟢 Baja (Falla menor)</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label-custom">Asunto / Resumen *</label>
            <input type="text" name="titulo" class="form-control-custom w-100" placeholder="Ej: El proyector parpadea y se apaga" required>
          </div>

          <div class="col-12">
            <label class="form-label-custom">Mensaje o Detalles del Problema</label>
            <textarea name="descripcion" class="form-control-custom w-100" rows="3" placeholder="Describe brevemente lo que ocurre para poder ayudarte mejor..." required></textarea>
          </div>

          <div class="col-12 mt-4 text-center">
            <button type="submit" class="btn-sky w-100 py-3">
              <i class="fa-solid fa-paper-plane me-1"></i> Enviar Reporte e Iniciar Chat
            </button>
          </div>
        </div>
      </form>
    <?php else: ?>
      <div class="text-center py-4">
        <i class="fa-solid fa-circle-xmark text-danger" style="font-size:3.5rem; opacity:0.8;"></i>
        <h5 class="fw-bold mt-3">Código QR Inválido</h5>
        <p class="text-muted" style="font-size:0.9rem;">Por favor, asegúrate de haber escaneado el código correcto pegado en el aula.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/bootstrap.bundle.min.js"></script>
</body>
</html>
