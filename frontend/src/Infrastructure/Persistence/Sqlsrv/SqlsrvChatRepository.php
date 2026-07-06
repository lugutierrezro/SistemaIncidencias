<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Infrastructure\Persistence\Sqlsrv\SqlsrvChatRepository.php

namespace App\Infrastructure\Persistence\Sqlsrv;

use App\Domain\Ports\ChatRepositoryInterface;
use App\Domain\Entities\ChatConversacion;
use App\Domain\Entities\ChatMensaje;
use RuntimeException;

class SqlsrvChatRepository implements ChatRepositoryInterface {
    private $conn;

    public function __construct() {
        $this->conn = SqlsrvConnection::getConnection();
    }

    public function findAllConversaciones(): array {
        $sql = "SELECT
                    CAST(i.id_incidencia AS VARCHAR(20)) AS id,
                    i.asunto AS titulo,
                    ISNULL(a.nombre, p.nombres) AS usuario_nombre,
                    'activa' AS estado,
                    CAST(i.id_incidencia AS VARCHAR(20)) AS incidencia_id,
                    i.asunto + ISNULL(' (' + a.nombre + ')', '') AS incidencia_titulo,
                    CONVERT(NVARCHAR(30), i.fecha_reporte, 126) AS inserted_at,
                    CONVERT(NVARCHAR(30), ISNULL((SELECT MAX(fecha_envio) FROM dbo.MensajeIncidencia WHERE id_incidencia = i.id_incidencia), i.fecha_reporte), 126) AS updated_at,
                    cat.nombre AS categoria_nombre,
                    sub.nombre AS subcategoria_nombre,
                    CASE i.id_prioridad_incidencia WHEN 1 THEN 'baja' WHEN 2 THEN 'media' WHEN 3 THEN 'alta' ELSE 'media' END AS prioridad,
                    a.nombre AS aula_nombre
                FROM dbo.Incidencia i
                LEFT JOIN dbo.Aula a ON a.id_aula = i.id_aula
                LEFT JOIN dbo.Usuario u ON u.id_usuario = i.id_usuario
                LEFT JOIN dbo.Persona p ON p.id_persona = u.id_persona
                LEFT JOIN dbo.SubcategoriaIncidencia sub ON sub.id_subcategoria_incidencia = i.id_subcategoria_incidencia
                LEFT JOIN dbo.CategoriaIncidencia cat ON cat.id_categoria_incidencia = sub.id_categoria_incidencia
                WHERE i.id_estado_incidencia IN (1, 2)
                ORDER BY ISNULL((SELECT MAX(fecha_envio) FROM dbo.MensajeIncidencia WHERE id_incidencia = i.id_incidencia), i.fecha_reporte) DESC";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al listar conversaciones: ' . ($err[0]['message'] ?? ''));
        }
        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $list[] = new ChatConversacion(
                $row['id'],
                $row['titulo'],
                $row['incidencia_id'],
                $row['usuario_nombre'],
                $row['estado'],
                $row['inserted_at'],
                $row['updated_at'],
                $row['incidencia_titulo'],
                $row['categoria_nombre'],
                $row['subcategoria_nombre'],
                $row['prioridad'],
                $row['aula_nombre']
            );
        }
        return $list;
    }

    public function saveConversacion(ChatConversacion $conversacion): ChatConversacion {
        if ($conversacion->id === null) {
            // Crear una nueva incidencia para representar esta conversación de chat
            $usuarioId = 1; // Default Admin
            $rUser = sqlsrv_query($this->conn, "SELECT TOP 1 id_usuario FROM dbo.Usuario WHERE nombre_usuario = ?", [$conversacion->usuario_nombre]);
            if ($rUser && $rowUser = sqlsrv_fetch_array($rUser, SQLSRV_FETCH_ASSOC)) {
                $usuarioId = (int)$rowUser['id_usuario'];
            }

            $sql = "INSERT INTO dbo.Incidencia (id_incidencia, id_aula, id_usuario, id_subcategoria_incidencia, id_estado_incidencia, id_prioridad_incidencia, asunto, descripcion, origen_reporte, fecha_reporte)
                    OUTPUT CAST(INSERTED.id_incidencia AS VARCHAR(20)) AS id,
                           CONVERT(NVARCHAR(30), INSERTED.fecha_reporte, 126) AS inserted_at
                    VALUES (ISNULL((SELECT MAX(id_incidencia) FROM dbo.Incidencia), 0) + 1, " . ($conversacion->incidencia_id ? "?" : "NULL") . ", ?, 1, 1, 2, ?, ?, 'Chat', GETDATE())";
            
            $params = [];
            if ($conversacion->incidencia_id) {
                $params[] = (int)$conversacion->incidencia_id;
            }
            $params[] = $usuarioId;
            $params[] = $conversacion->titulo;
            $params[] = "Chat iniciado por: " . $conversacion->usuario_nombre;

            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                $err = sqlsrv_errors();
                throw new RuntimeException('Error al crear conversación de chat (Incidencia): ' . ($err[0]['message'] ?? ''));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $conversacion->id = $row['id'];
            $conversacion->inserted_at = $row['inserted_at'];
            $conversacion->updated_at = $row['inserted_at'];
        } else {
            // Actualizar el estado de la incidencia asociada
            $estadoId = (strtolower($conversacion->estado) === 'cerrada') ? 3 : 1;
            $sql = "UPDATE dbo.Incidencia SET id_estado_incidencia = ? WHERE id_incidencia = ?";
            $stmt = sqlsrv_query($this->conn, $sql, [$estadoId, (int)$conversacion->id]);
            if ($stmt === false) {
                $err = sqlsrv_errors();
                throw new RuntimeException('Error al actualizar conversación de chat (Incidencia): ' . ($err[0]['message'] ?? ''));
            }
        }
        return $conversacion;
    }

    public function closeConversacion(string $id): bool {
        $sql = "UPDATE dbo.Incidencia SET id_estado_incidencia = 3, fecha_cierre = GETDATE() WHERE id_incidencia = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [(int)$id]);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al cerrar conversación de chat: ' . ($err[0]['message'] ?? ''));
        }
        return true;
    }

    public function findMensajesByConversacion(string $convId): array {
        $sql = "SELECT
                    CAST(id_mensaje_incidencia AS VARCHAR(20)) AS id,
                    CAST(id_incidencia AS VARCHAR(20)) AS conversacion_id,
                    mensaje AS contenido,
                    CASE tipo_actor WHEN 'Soporte' THEN 'Admin Operaciones' ELSE 'Usuario' END AS remitente,
                    LOWER(tipo_actor) AS tipo_remitente,
                    CONVERT(NVARCHAR(30), fecha_envio, 126) AS inserted_at
                FROM dbo.MensajeIncidencia
                WHERE id_incidencia = ?
                ORDER BY fecha_envio ASC";
        $stmt = sqlsrv_query($this->conn, $sql, [(int)$convId]);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al buscar mensajes: ' . ($err[0]['message'] ?? ''));
        }
        $msgs = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $msgs[] = new ChatMensaje(
                $row['id'],
                $row['conversacion_id'],
                $row['contenido'],
                $row['remitente'],
                $row['tipo_remitente'],
                $row['inserted_at']
            );
        }
        return $msgs;
    }

    public function findMensajesNuevosByConversacion(string $convId, ?string $desdeId): array {
        if ($desdeId) {
            $r = sqlsrv_query($this->conn,
                "SELECT CONVERT(NVARCHAR(30), fecha_envio, 126) AS ts FROM dbo.MensajeIncidencia WHERE id_mensaje_incidencia = ?",
                [(int)$desdeId]
            );
            $lastTs = null;
            if ($r && $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_ASSOC)) {
                $lastTs = $row['ts'];
            }
            if ($lastTs) {
                $sql = "SELECT
                            CAST(id_mensaje_incidencia AS VARCHAR(20)) AS id,
                            CAST(id_incidencia AS VARCHAR(20)) AS conversacion_id,
                            mensaje AS contenido,
                            CASE tipo_actor WHEN 'Soporte' THEN 'Admin Operaciones' ELSE 'Usuario' END AS remitente,
                            LOWER(tipo_actor) AS tipo_remitente,
                            CONVERT(NVARCHAR(30), fecha_envio, 126) AS inserted_at
                        FROM dbo.MensajeIncidencia
                        WHERE id_incidencia = ? AND fecha_envio > ?
                        ORDER BY fecha_envio ASC";
                $stmt = sqlsrv_query($this->conn, $sql, [(int)$convId, $lastTs]);
            } else {
                return [];
            }
        } else {
            return $this->findMensajesByConversacion($convId);
        }

        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al buscar mensajes nuevos: ' . ($err[0]['message'] ?? ''));
        }
        $msgs = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $msgs[] = new ChatMensaje(
                $row['id'],
                $row['conversacion_id'],
                $row['contenido'],
                $row['remitente'],
                $row['tipo_remitente'],
                $row['inserted_at']
            );
        }
        return $msgs;
    }

    public function saveMensaje(ChatMensaje $mensaje): ChatMensaje {
        // Mapear remitente para que quede guardado como 'Soporte' o 'Usuario'
        $actorType = (strtolower($mensaje->tipo_remitente) === 'soporte') ? 'Soporte' : 'Usuario';

        $sql = "INSERT INTO dbo.MensajeIncidencia (id_mensaje_incidencia, id_incidencia, tipo_actor, mensaje, fecha_envio)
                OUTPUT CAST(INSERTED.id_mensaje_incidencia AS VARCHAR(20)) AS id,
                       CONVERT(NVARCHAR(30), INSERTED.fecha_envio, 126) AS inserted_at
                VALUES (ISNULL((SELECT MAX(id_mensaje_incidencia) FROM dbo.MensajeIncidencia), 0) + 1, ?, ?, ?, GETDATE())";
        $params = [
            (int)$mensaje->conversacion_id,
            $actorType,
            $mensaje->contenido
        ];
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al enviar mensaje: ' . ($err[0]['message'] ?? ''));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $mensaje->id = $row['id'];
        $mensaje->inserted_at = $row['inserted_at'];

        return $mensaje;
    }
}
