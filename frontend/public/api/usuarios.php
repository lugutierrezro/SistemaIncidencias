<?php
// ──────────────────────────────────────────────────────────────
// api/usuarios.php — Listar usuarios
// Adaptador de entrada HTTP (Arquitectura Hexagonal)
// ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvUsuarioRepository;
use App\Application\UseCases\Usuario\GetAllUsuariosUseCase;

$method = $_SERVER['REQUEST_METHOD'];

try {
    $usuarioRepo = new SqlsrvUsuarioRepository();

    if ($method === 'GET') {
        $useCase = new GetAllUsuariosUseCase($usuarioRepo);
        $usuarios = $useCase->execute();
        $result = [];
        foreach ($usuarios as $u) {
            $result[] = $u->toArray();
        }
        jsonResponse($result);
    }

    jsonError('Método no permitido.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage());
}
