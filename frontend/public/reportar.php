<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\public\reportar.php
// Redirección directa al chat activo o autogenerado por QR (Fricción Cero)

require_once __DIR__ . '/api/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvChatRepository;
use App\Application\UseCases\Incidencia\CreateIncidenciaUseCase;

$aulaId = $_GET['aula_id'] ?? null;

if (!$aulaId) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:3rem;'><h1>⚠️ Error</h1><p>Código QR incorrecto (Aula no especificada).</p></div>";
    exit;
}

try {
    $aulaRepo = new SqlsrvAulaRepository();
    $aula = $aulaRepo->findById($aulaId);
    
    if (!$aula) {
        echo "<div style='font-family:sans-serif; text-align:center; padding:3rem;'><h1>⚠️ Error</h1><p>El aula solicitada no existe.</p></div>";
        exit;
    }
    
    $aulaArray = $aula->toArray();
    $incidenciaRepo = new SqlsrvIncidenciaRepository();

    // 1. Buscar si ya existe una incidencia activa (abierta o en proceso) en esta aula
    $conn = \App\Infrastructure\Persistence\Sqlsrv\SqlsrvConnection::getConnection();
    $sqlActive = "SELECT TOP 1 CAST(id_incidencia AS VARCHAR(20)) AS id 
                  FROM dbo.Incidencia 
                  WHERE id_aula = ? AND id_estado_incidencia IN (1, 2)
                  ORDER BY fecha_reporte DESC";
    $stmtActive = sqlsrv_query($conn, $sqlActive, [(int)$aulaId]);
    
    $incidenciaId = null;
    if ($stmtActive && $rowActive = sqlsrv_fetch_array($stmtActive, SQLSRV_FETCH_ASSOC)) {
        $incidenciaId = $rowActive['id'];
    }

    // 2. Si no hay una incidencia activa para esta aula, crear una automática al instante
    if (!$incidenciaId) {
        $useCase = new CreateIncidenciaUseCase($incidenciaRepo);
        $incidencia = $useCase->execute([
            'titulo'        => "Soporte en " . $aulaArray['nombre'],
            'descripcion'   => "Solicitud iniciada por escaneo de QR en " . $aulaArray['nombre'] . " (Edificio " . ($aulaArray['edificio'] ?? '—') . ").",
            'prioridad'     => 'media',
            'aula_id'       => $aulaArray['id'],
            'reportado_por' => 'Docente',
            'estado'        => 'abierta'
        ]);
        $incidenciaId = $incidencia->id;
    }

    // 3. Redireccionar de inmediato a la interfaz de chat en vivo del aula
    header("Location: chat_docente.php?incidencia_id=" . urlencode($incidenciaId));
    exit;

} catch (Throwable $e) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:3rem;'><h1>⚠️ Error del Servidor</h1><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
    exit;
}
