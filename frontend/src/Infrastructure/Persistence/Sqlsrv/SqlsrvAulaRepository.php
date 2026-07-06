<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Infrastructure\Persistence\Sqlsrv\SqlsrvAulaRepository.php

namespace App\Infrastructure\Persistence\Sqlsrv;

use App\Domain\Ports\AulaRepositoryInterface;
use App\Domain\Entities\Aula;
use RuntimeException;

class SqlsrvAulaRepository implements AulaRepositoryInterface {
    private $conn;

    public function __construct() {
        $this->conn = SqlsrvConnection::getConnection();
    }

    private function buildDynamicQrUrl(string $relativeUrl): string {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
        
        $relativeUrl = ltrim($relativeUrl, '/');
        // Si por alguna razón es una URL absoluta guardada previamente en la base de datos, limpiarla a relativa
        if (preg_match('/https?:\/\/[^\/]+(.*)/i', $relativeUrl, $matches)) {
            $relativeUrl = ltrim($matches[1], '/');
            // Quitar subdirectorio si ya viene en la URL absoluta guardada
            if (!empty($scriptDir)) {
                $cleanScriptDir = ltrim($scriptDir, '/');
                if (strpos($relativeUrl, $cleanScriptDir) === 0) {
                    $relativeUrl = substr($relativeUrl, strlen($cleanScriptDir));
                    $relativeUrl = ltrim($relativeUrl, '/');
                }
            }
        }
        
        return "http://" . $host . $scriptDir . "/" . $relativeUrl;
    }

    private function ensureQrCode(int $aulaId): string {
        $stmt = sqlsrv_query($this->conn, "SELECT url_qr FROM dbo.QRAula WHERE id_aula = ?", [$aulaId]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['url_qr'])) {
                return $this->buildDynamicQrUrl($row['url_qr']);
            }
        }
        
        // Guardar como URL relativa para independizar de la IP/Puerto/Servidor
        $relativeUrl = "reportar.php?aula_id=" . $aulaId;
        
        $insert = sqlsrv_query(
            $this->conn, 
            "INSERT INTO dbo.QRAula (id_qr_aula, id_aula, url_qr) 
             VALUES (ISNULL((SELECT MAX(id_qr_aula) FROM dbo.QRAula), 0) + 1, ?, ?)", 
            [$aulaId, $relativeUrl]
        );
        if ($insert === false) {
            $err = sqlsrv_errors();
        }
        
        return $this->buildDynamicQrUrl($relativeUrl);
    }

    public function save(Aula $aula): Aula {
        if ($aula->id === null) {
            $sql = "INSERT INTO dbo.Aula (nombre, pabellon, piso, capacidad, observaciones, estado)
                    OUTPUT CAST(INSERTED.id_aula AS VARCHAR(20)) AS id
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [
                $aula->nombre,
                $aula->edificio,
                $aula->piso,
                $aula->capacidad,
                $aula->equipamiento,
                $aula->estado
            ];
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                $err = sqlsrv_errors();
                throw new RuntimeException('Error al crear aula: ' . ($err[0]['message'] ?? ''));
            }
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $aula->id = $row['id'];
            
            // Generar y guardar QR
            $aula->qr_url = $this->ensureQrCode((int)$aula->id);
        } else {
            $sql = "UPDATE dbo.Aula 
                    SET nombre = ?, pabellon = ?, piso = ?, capacidad = ?, observaciones = ?, estado = ?
                    WHERE id_aula = ?";
            $params = [
                $aula->nombre,
                $aula->edificio,
                $aula->piso,
                $aula->capacidad,
                $aula->equipamiento,
                $aula->estado,
                (int)$aula->id
            ];
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                $err = sqlsrv_errors();
                throw new RuntimeException('Error al actualizar aula: ' . ($err[0]['message'] ?? ''));
            }
            
            // Asegurar que tenga QR
            $aula->qr_url = $this->ensureQrCode((int)$aula->id);
        }
        return $aula;
    }

    public function findAll(): array {
        $sql = "SELECT 
                    CAST(a.id_aula AS VARCHAR(20)) AS id,
                    a.nombre, a.pabellon AS edificio, a.piso, a.capacidad, a.observaciones AS equipamiento, a.estado,
                    qr.url_qr
                FROM dbo.Aula a
                LEFT JOIN dbo.QRAula qr ON qr.id_aula = a.id_aula
                ORDER BY a.nombre";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al listar aulas: ' . ($err[0]['message'] ?? ''));
        }
        $aulas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $qrUrl = $row['url_qr'];
            if (empty($qrUrl) && !empty($row['id'])) {
                $qrUrl = $this->ensureQrCode((int)$row['id']);
            } else {
                $qrUrl = $this->buildDynamicQrUrl($qrUrl);
            }
            $aulas[] = new Aula(
                $row['id'],
                $row['nombre'],
                $row['edificio'],
                $row['piso'],
                (int)$row['capacidad'],
                $row['equipamiento'],
                $row['estado'],
                null,
                null,
                $qrUrl
            );
        }
        return $aulas;
    }

    public function findById(string $id): ?Aula {
        $sql = "SELECT 
                    CAST(a.id_aula AS VARCHAR(20)) AS id,
                    a.nombre, a.pabellon AS edificio, a.piso, a.capacidad, a.observaciones AS equipamiento, a.estado,
                    qr.url_qr
                FROM dbo.Aula a
                LEFT JOIN dbo.QRAula qr ON qr.id_aula = a.id_aula
                WHERE a.id_aula = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [(int)$id]);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al buscar aula: ' . ($err[0]['message'] ?? ''));
        }
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $qrUrl = $row['url_qr'];
            if (empty($qrUrl)) {
                $qrUrl = $this->ensureQrCode((int)$id);
            } else {
                $qrUrl = $this->buildDynamicQrUrl($qrUrl);
            }
            return new Aula(
                $row['id'],
                $row['nombre'],
                $row['edificio'],
                $row['piso'],
                (int)$row['capacidad'],
                $row['equipamiento'],
                $row['estado'],
                null,
                null,
                $qrUrl
            );
        }
        return null;
    }
}
