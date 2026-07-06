<?php
// ──────────────────────────────────────────────────────────────
// api/dashboard.php — Estadísticas para el Dashboard
// Adaptador de entrada HTTP (Arquitectura Hexagonal)
// ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository;
use App\Application\UseCases\Dashboard\GetDashboardStatsUseCase;

try {
    $incidenciaRepo = new SqlsrvIncidenciaRepository();
    $aulaRepo = new SqlsrvAulaRepository();
    $useCase = new GetDashboardStatsUseCase($incidenciaRepo, $aulaRepo);

    $stats = $useCase->execute();
    jsonResponse($stats);
} catch (Throwable $e) {
    jsonError($e->getMessage());
}
