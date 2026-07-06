<?php
// ──────────────────────────────────────────────────────────────
// api/aulas.php — CRUD de Aulas
// Adaptador de entrada HTTP (Arquitectura Hexagonal)
// ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Application\UseCases\Aula\GetAllAulasUseCase;
use App\Application\UseCases\Aula\CreateAulaUseCase;

$method = $_SERVER['REQUEST_METHOD'];

try {
    $aulaRepo = new SqlsrvAulaRepository();

    if ($method === 'GET') {
        $useCase = new GetAllAulasUseCase($aulaRepo);
        $aulas = $useCase->execute();
        
        $result = [];
        foreach ($aulas as $aula) {
            $result[] = $aula->toArray();
        }
        jsonResponse($result);
    }

    if ($method === 'POST') {
        $body = getJsonBody();
        $useCase = new CreateAulaUseCase($aulaRepo);
        $aula = $useCase->execute($body);
        jsonResponse($aula->toArray(), 201);
    }

    jsonError('Método no permitido.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage());
}
