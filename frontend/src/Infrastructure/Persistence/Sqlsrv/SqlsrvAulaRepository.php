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
        }
        return $aula;
    }

    public function findAll(): array {
        $sql = "SELECT 
                    CAST(id_aula AS VARCHAR(20)) AS id,
                    nombre, pabellon AS edificio, piso, capacidad, observaciones AS equipamiento, estado
                FROM dbo.Aula
                ORDER BY nombre";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al listar aulas: ' . ($err[0]['message'] ?? ''));
        }
        $aulas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $aulas[] = new Aula(
                $row['id'],
                $row['nombre'],
                $row['edificio'],
                $row['piso'],
                (int)$row['capacidad'],
                $row['equipamiento'],
                $row['estado']
            );
        }
        return $aulas;
    }

    public function findById(string $id): ?Aula {
        $sql = "SELECT 
                    CAST(id_aula AS VARCHAR(20)) AS id,
                    nombre, pabellon AS edificio, piso, capacidad, observaciones AS equipamiento, estado
                FROM dbo.Aula
                WHERE id_aula = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [(int)$id]);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al buscar aula: ' . ($err[0]['message'] ?? ''));
        }
        if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return new Aula(
                $row['id'],
                $row['nombre'],
                $row['edificio'],
                $row['piso'],
                (int)$row['capacidad'],
                $row['equipamiento'],
                $row['estado']
            );
        }
        return null;
    }
}
