<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Infrastructure\Persistence\Sqlsrv\SqlsrvIncidenciaRepository.php

namespace App\Infrastructure\Persistence\Sqlsrv;

use App\Domain\Ports\IncidenciaRepositoryInterface;
use App\Domain\Entities\Incidencia;
use RuntimeException;

class SqlsrvIncidenciaRepository implements IncidenciaRepositoryInterface {
    private $conn;

    public function __construct() {
        $this->conn = SqlsrvConnection::getConnection();
    }

    public function save(Incidencia $incidencia): Incidencia {
        $prioridadMap = ['baja' => 1, 'media' => 2, 'alta' => 3];
        $prioridadId  = $prioridadMap[strtolower($incidencia->prioridad)] ?? 2;

        $estadoMap    = ['abierta' => 1, 'en_proceso' => 2, 'resuelta' => 3];
        $estadoId     = $estadoMap[strtolower($incidencia->estado)] ?? 1;

        // Intentar buscar id_usuario del reportado_por si coincide con un nombre_usuario en la base de datos,
        // de lo contrario usar 1 (Admin) como fallback.
        $usuarioId = 1;
        $rUser = sqlsrv_query($this->conn, "SELECT TOP 1 id_usuario FROM dbo.Usuario WHERE nombre_usuario = ?", [$incidencia->reportado_por]);
        if ($rUser && $rowUser = sqlsrv_fetch_array($rUser, SQLSRV_FETCH_ASSOC)) {
            $usuarioId = (int)$rowUser['id_usuario'];
        }

        if ($incidencia->id === null) {
            $sql = "INSERT INTO dbo.Incidencia (id_aula, id_usuario, id_subcategoria_incidencia, id_estado_incidencia, id_prioridad_incidencia, asunto, descripcion, origen_reporte, fecha_reporte)
                    OUTPUT CAST(INSERTED.id_incidencia AS VARCHAR(20)) AS id,
                           CONVERT(NVARCHAR(30), INSERTED.fecha_reporte, 126) AS inserted_at
                    VALUES (" . ($incidencia->aula_id ? "?" : "NULL") . ", ?, 1, ?, ?, ?, ?, 'Web', GETDATE())";
            
            $params = [];
            if ($incidencia->aula_id) {
                $params[] = (int)$incidencia->aula_id;
            }
            $params[] = $usuarioId;
            $params[] = $estadoId;
            $params[] = $prioridadId;
            $params[] = $incidencia->titulo;
            $params[] = $incidencia->descripcion;

            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                $err = sqlsrv_errors();
                throw new RuntimeException('Error al crear incidencia: ' . ($err[0]['message'] ?? ''));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $incidencia->id = $row['id'];
            $incidencia->inserted_at = $row['inserted_at'];
        } else {
            $sql = "UPDATE dbo.Incidencia
                    SET id_aula = " . ($incidencia->aula_id ? "?" : "NULL") . ",
                        id_estado_incidencia = ?,
                        id_prioridad_incidencia = ?,
                        asunto = ?,
                        descripcion = ?
                    WHERE id_incidencia = ?";
            
            $params = [];
            if ($incidencia->aula_id) {
                $params[] = (int)$incidencia->aula_id;
            }
            $params[] = $estadoId;
            $params[] = $prioridadId;
            $params[] = $incidencia->titulo;
            $params[] = $incidencia->descripcion;
            $params[] = (int)$incidencia->id;

            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                $err = sqlsrv_errors();
                throw new RuntimeException('Error al actualizar incidencia: ' . ($err[0]['message'] ?? ''));
            }
        }
        return $incidencia;
    }

    public function findAll(): array {
        $sql = "SELECT
                    CAST(i.id_incidencia AS VARCHAR(20)) AS id,
                    i.asunto AS titulo,
                    i.descripcion,
                    CASE i.id_estado_incidencia 
                        WHEN 1 THEN 'abierta'
                        WHEN 2 THEN 'en_proceso'
                        WHEN 3 THEN 'resuelta'
                        ELSE 'abierta'
                    END AS estado,
                    CASE i.id_prioridad_incidencia
                        WHEN 1 THEN 'baja'
                        WHEN 2 THEN 'media'
                        WHEN 3 THEN 'alta'
                        ELSE 'media'
                    END AS prioridad,
                    p.nombres AS reportado_por,
                    CONVERT(NVARCHAR(30), i.fecha_reporte, 126) AS inserted_at,
                    CONVERT(NVARCHAR(30), i.fecha_cierre, 126) AS fecha_cierre,
                    CAST(i.id_aula AS VARCHAR(20)) AS aula_id,
                    a.nombre AS aula_nombre
                FROM dbo.Incidencia i
                LEFT JOIN dbo.Aula a ON a.id_aula = i.id_aula
                LEFT JOIN dbo.Usuario u ON u.id_usuario = i.id_usuario
                LEFT JOIN dbo.Persona p ON p.id_persona = u.id_persona
                ORDER BY i.fecha_reporte DESC";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al listar incidencias: ' . ($err[0]['message'] ?? ''));
        }
        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $list[] = new Incidencia(
                $row['id'],
                $row['titulo'],
                $row['descripcion'],
                $row['estado'],
                $row['prioridad'],
                $row['aula_id'],
                $row['reportado_por'],
                null, // asignado_a
                $row['fecha_cierre'],
                $row['inserted_at'],
                $row['inserted_at'], // updated_at maps to inserted_at fallback
                $row['aula_nombre']
            );
        }
        return $list;
    }

    public function findById(string $id): ?Incidencia {
        $sql = "SELECT
                    CAST(i.id_incidencia AS VARCHAR(20)) AS id,
                    i.asunto AS titulo,
                    i.descripcion,
                    CASE i.id_estado_incidencia 
                        WHEN 1 THEN 'abierta'
                        WHEN 2 THEN 'en_proceso'
                        WHEN 3 THEN 'resuelta'
                        ELSE 'abierta'
                    END AS estado,
                    CASE i.id_prioridad_incidencia
                        WHEN 1 THEN 'baja'
                        WHEN 2 THEN 'media'
                        WHEN 3 THEN 'alta'
                        ELSE 'media'
                    END AS prioridad,
                    p.nombres AS reportado_por,
                    CONVERT(NVARCHAR(30), i.fecha_reporte, 126) AS inserted_at,
                    CONVERT(NVARCHAR(30), i.fecha_cierre, 126) AS fecha_cierre,
                    CAST(i.id_aula AS VARCHAR(20)) AS aula_id,
                    a.nombre AS aula_nombre
                FROM dbo.Incidencias i
                LEFT JOIN dbo.Aula a ON a.id_aula = i.id_aula
                LEFT JOIN dbo.Usuario u ON u.id_usuario = i.id_usuario
                LEFT JOIN dbo.Persona p ON p.id_persona = u.id_persona
                WHERE i.id_incidencia = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [(int)$id]);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al buscar incidencia: ' . ($err[0]['message'] ?? ''));
        }
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return new Incidencia(
                $row['id'],
                $row['titulo'],
                $row['descripcion'],
                $row['estado'],
                $row['prioridad'],
                $row['aula_id'],
                $row['reportado_por'],
                null,
                $row['fecha_cierre'],
                $row['inserted_at'],
                $row['inserted_at'],
                $row['aula_nombre']
            );
        }
        return null;
    }

    public function resolve(string $id): bool {
        $sql = "UPDATE dbo.Incidencia
                SET id_estado_incidencia = 3, fecha_cierre = GETDATE()
                WHERE id_incidencia = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [(int)$id]);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al resolver incidencia: ' . ($err[0]['message'] ?? ''));
        }
        return true;
    }
}
