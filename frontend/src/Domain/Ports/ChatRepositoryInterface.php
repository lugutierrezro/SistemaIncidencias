<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Ports\ChatRepositoryInterface.php

namespace App\Domain\Ports;

use App\Domain\Entities\ChatConversacion;
use App\Domain\Entities\ChatMensaje;

interface ChatRepositoryInterface {
    public function findAllConversaciones(): array;
    public function saveConversacion(ChatConversacion $conversacion): ChatConversacion;
    public function closeConversacion(string $id): bool;
    public function findMensajesByConversacion(string $convId): array;
    public function findMensajesNuevosByConversacion(string $convId, ?string $desdeId): array;
    public function saveMensaje(ChatMensaje $mensaje): ChatMensaje;
}
