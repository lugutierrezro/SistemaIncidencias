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

    jsonError('Acción no soportada.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage());
}
