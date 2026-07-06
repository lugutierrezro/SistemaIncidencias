<?php
// ──────────────────────────────────────────────────────────────
// api/chat.php — API de Chat (Conversaciones + Mensajes)
// Adaptador de entrada HTTP (Arquitectura Hexagonal)
// ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

use App\Infrastructure\Persistence\Sqlsrv\SqlsrvChatRepository;
use App\Application\UseCases\Chat\GetAllConversacionesUseCase;
use App\Application\UseCases\Chat\CreateConversacionUseCase;
use App\Application\UseCases\Chat\CloseConversacionUseCase;
use App\Application\UseCases\Chat\GetMessagesUseCase;
use App\Application\UseCases\Chat\SendMessageUseCase;

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action']  ?? '';
$convId  = $_GET['conv_id'] ?? null;
$desde   = $_GET['desde']   ?? null;
$id      = $_GET['id']      ?? null;

try {
    $chatRepo = new SqlsrvChatRepository();

    if ($method === 'GET' && $action === 'conversaciones') {
        $useCase = new GetAllConversacionesUseCase($chatRepo);
        $convs = $useCase->execute();
        $result = [];
        foreach ($convs as $c) {
            $result[] = $c->toArray();
        }
        jsonResponse($result);
    }

    if ($method === 'POST' && $action === 'nueva_conversacion') {
        $body = getJsonBody();
        $useCase = new CreateConversacionUseCase($chatRepo);
        $conv = $useCase->execute($body);
        jsonResponse($conv->toArray(), 201);
    }

    if ($method === 'PUT' && $action === 'cerrar_conversacion' && $id) {
        $useCase = new CloseConversacionUseCase($chatRepo);
        $useCase->execute($id);
        jsonResponse(['ok' => true]);
    }

    if ($method === 'GET' && $action === 'mensajes' && $convId) {
        $useCase = new GetMessagesUseCase($chatRepo);
        $msgs = $useCase->execute($convId);
        $result = [];
        foreach ($msgs as $m) {
            $result[] = $m->toArray();
        }
        jsonResponse($result);
    }

    if ($method === 'GET' && $action === 'mensajes_nuevos' && $convId) {
        $useCase = new GetMessagesUseCase($chatRepo);
        $msgs = $useCase->execute($convId, $desde);
        $result = [];
        foreach ($msgs as $m) {
            $result[] = $m->toArray();
        }
        jsonResponse($result);
    }

    if ($method === 'POST' && $action === 'nuevo_mensaje' && $convId) {
        $body = getJsonBody();
        $useCase = new SendMessageUseCase($chatRepo);
        $msg = $useCase->execute($convId, $body);
        jsonResponse($msg->toArray(), 201);
    }

    jsonError('Acción no reconocida.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage());
}
