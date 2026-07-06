<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Infrastructure\Persistence\Sqlsrv\SqlsrvUsuarioRepository.php

namespace App\Infrastructure\Persistence\Sqlsrv;

use App\Domain\Ports\UsuarioRepositoryInterface;
use App\Domain\Entities\Usuario;
use RuntimeException;

class SqlsrvUsuarioRepository implements UsuarioRepositoryInterface {
    private $conn;

    public function __construct() {
        $this->conn = SqlsrvConnection::getConnection();
    }

    public function findAll(): array {
        $sql = "SELECT
                    CAST(u.id_usuario AS VARCHAR(20)) AS id,
                    p.nombres AS nombre,
                    p.correo AS email,
                    r.nombres AS rol,
                    u.estado,
                    u.nombre_usuario
                FROM dbo.Usuario u
                JOIN dbo.Persona p ON p.id_persona = u.id_persona
                JOIN dbo.Rol r ON r.id_rol = u.id_rol
                ORDER BY p.nombres";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            throw new RuntimeException('Error al listar usuarios: ' . ($err[0]['message'] ?? ''));
        }
        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $list[] = new Usuario(
                $row['id'],
                $row['nombre'],
                $row['email'],
                $row['rol'],
                $row['estado'],
                null,
                null,
                $row['nombre_usuario']
            );
        }
        return $list;
    }
}
