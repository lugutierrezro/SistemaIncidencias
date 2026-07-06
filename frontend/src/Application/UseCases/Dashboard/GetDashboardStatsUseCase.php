<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Dashboard\GetDashboardStatsUseCase.php

namespace App\Application\UseCases\Dashboard;

use App\Domain\Ports\IncidenciaRepositoryInterface;
use App\Domain\Ports\AulaRepositoryInterface;
use App\Infrastructure\Persistence\Sqlsrv\SqlsrvConnection;

class GetDashboardStatsUseCase {
    private $incidenciaRepository;
    private $aulaRepository;

    public function __construct(
        IncidenciaRepositoryInterface $incidenciaRepository,
        AulaRepositoryInterface $aulaRepository
    ) {
        $this->incidenciaRepository = $incidenciaRepository;
        $this->aulaRepository = $aulaRepository;
    }

    public function execute(): array {
        $conn = SqlsrvConnection::getConnection();

        // 1. Contar incidencias abiertas
        $openCount = 0;
        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM dbo.Incidencia WHERE id_estado_incidencia IN (1, 2)");
        if ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $openCount = (int)$row['cnt'];
        }

        // 2. Contar resueltas este mes
        $resolvedCount = 0;
        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM dbo.Incidencia WHERE id_estado_incidencia = 3 AND MONTH(fecha_reporte) = MONTH(GETDATE()) AND YEAR(fecha_reporte) = YEAR(GETDATE())");
        if ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $resolvedCount = (int)$row['cnt'];
        }

        // 3. Contar aulas activas (disponibles)
        $aulasCount = 0;
        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM dbo.Aula WHERE estado = 'Operativo'");
        if ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $aulasCount = (int)$row['cnt'];
        }

        // 4. Tiempo promedio de resolución (horas)
        $avgHours = 0.0;
        $r = sqlsrv_query($conn, "
            SELECT AVG(CAST(DATEDIFF(MINUTE, fecha_reporte, ISNULL(fecha_cierre, GETDATE())) AS FLOAT) / 60.0) AS avg_h
            FROM dbo.Incidencia
            WHERE id_estado_incidencia = 3 AND fecha_cierre IS NOT NULL
        ");
        if ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $avgHours = round((float)($row['avg_h'] ?? 0), 1);
        }

        // 5. Últimas 5 incidencias
        $incidencias = $this->incidenciaRepository->findAll();
        $recentIncidents = [];
        for ($i = 0; $i < min(5, count($incidencias)); $i++) {
            $recentIncidents[] = $incidencias[$i]->toArray();
        }

        // 6. Donut
        $chartDonut = [0, 0, 0];
        $r = sqlsrv_query($conn, "
            SELECT
              SUM(CASE WHEN id_estado_incidencia = 1 THEN 1 ELSE 0 END) AS abiertas,
              SUM(CASE WHEN id_estado_incidencia = 2 THEN 1 ELSE 0 END) AS en_proceso,
              SUM(CASE WHEN id_estado_incidencia = 3 THEN 1 ELSE 0 END) AS resueltas
            FROM dbo.Incidencia
        ");
        if ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $chartDonut = [(int)$row['abiertas'], (int)$row['en_proceso'], (int)$row['resueltas']];
        }

        // 7. Timeline: últimas 8 actividades
        $timeline = [];
        $estadoEmojis = [
            'abierta'    => ['type' => 'warning', 'verb' => 'Nueva incidencia'],
            'en_proceso' => ['type' => 'primary', 'verb' => 'Incidencia en proceso'],
            'resuelta'   => ['type' => 'success', 'verb' => 'Incidencia resuelta'],
            'critica'    => ['type' => 'danger',  'verb' => 'Incidencia crítica'],
        ];
        $r = sqlsrv_query($conn, "
            SELECT TOP 8
                asunto AS titulo,
                CASE id_estado_incidencia 
                    WHEN 1 THEN 'abierta'
                    WHEN 2 THEN 'en_proceso'
                    WHEN 3 THEN 'resuelta'
                    ELSE 'abierta'
                END AS estado,
                fecha_reporte AS updated_at
            FROM dbo.Incidencia
            ORDER BY fecha_reporte DESC
        ");
        while ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
            $est = $row['estado'] ?? 'abierta';
            $cfg = $estadoEmojis[$est] ?? $estadoEmojis['abierta'];
            $timeline[] = [
                'type'     => $cfg['type'],
                'text'     => $cfg['verb'] . ': ' . $row['titulo'],
                'time_str' => $row['updated_at'],
            ];
        }

        return [
            'open_count'            => $openCount,
            'resolved_count'        => $resolvedCount,
            'aulas_count'           => $aulasCount,
            'avg_resolution_hours'  => $avgHours,
            'recent_incidents'      => $recentIncidents,
            'chart_donut'           => $chartDonut,
            'timeline'              => $timeline,
        ];
    }
}
