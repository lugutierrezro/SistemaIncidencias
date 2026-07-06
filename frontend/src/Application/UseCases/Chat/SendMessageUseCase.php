<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Chat\SendMessageUseCase.php

namespace App\Application\UseCases\Chat;

use App\Domain\Ports\ChatRepositoryInterface;
use App\Domain\Entities\ChatMensaje;

class SendMessageUseCase {
    private $chatRepository;

    public function __construct(ChatRepositoryInterface $chatRepository) {
        $this->chatRepository = $chatRepository;
    }

    public function execute(string $convId, array $data): ChatMensaje {
        $mensaje = new ChatMensaje(
            null,
            $convId,
            $data['contenido'],
            $data['remitente'],
            $data['tipo_remitente']
        );
        return $this->chatRepository->saveMensaje($mensaje);
    }
}
