<?php
// ──────────────────────────────────────────────────────────────
// api/incidencias.php — CRUD de Incidencias
// Adaptador de entrada HTTP (Arquitectura Hexagonal)
// ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Application\UseCases\Incidencia\GetAllIncidenciasUseCase;
use App\Application\UseCases\Incidencia\CreateIncidenciaUseCase;
use App\Application\UseCases\Incidencia\ResolveIncidenciaUseCase;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? null;

try {
    $incidenciaRepo = new SqlsrvIncidenciaRepository();

    if ($method === 'GET' && $action === 'list') {
        $useCase = new GetAllIncidenciasUseCase($incidenciaRepo);
        $incidencias = $useCase->execute();
        
        $result = [];
        foreach ($incidencias as $inc) {
            $result[] = $inc->toArray();
        }
        jsonResponse($result);
    }

    if ($method === 'POST' && $action === 'create') {
        $body = getJsonBody();
        $useCase = new CreateIncidenciaUseCase($incidenciaRepo);
        $inc = $useCase->execute($body);
        jsonResponse($inc->toArray(), 201);
    }

    if ($method === 'PUT' && $action === 'resolver' && $id) {
        $useCase = new ResolveIncidenciaUseCase($incidenciaRepo);
        $useCase->execute($id);
        jsonResponse(['ok' => true, 'id' => $id]);
    }

    if ($method === 'PUT' && $action === 'clasificar' && $id) {
        $body = getJsonBody();
        $subId = $body['subcategoria_id'] ?? null;
        $prioVal = $body['prioridad'] ?? null;
        
        $conn = \App\Infrastructure\Persistence\Sqlsrv\SqlsrvConnection::getConnection();
        
        $prioMap = ['baja' => 1, 'media' => 2, 'alta' => 3];
        $prioId = $prioMap[strtolower($prioVal)] ?? null;
        
        $sql = "UPDATE dbo.Incidencia SET ";
        $sets = [];
        $params = [];
        if ($subId !== null) {
            $sets[] = "id_subcategoria_incidencia = ?";
            $params[] = (int)$subId;
        }
        if ($prioId !== null) {
            $sets[] = "id_prioridad_incidencia = ?";
            $params[] = (int)$prioId;
        }
        
        if (empty($sets)) {
            jsonError('Faltan parámetros de clasificación.', 400);
        }
        
        $sql .= implode(', ', $sets) . " WHERE id_incidencia = ?";
        $params[] = (int)$id;
        
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            jsonError('Error al clasificar: ' . ($err[0]['message'] ?? ''));
        }
        
        jsonResponse(['ok' => true]);
    }

    jsonError('Acción no soportada.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage());
}
