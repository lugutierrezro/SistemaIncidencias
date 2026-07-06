<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Chat\CreateConversacionUseCase.php

namespace App\Application\UseCases\Chat;

use App\Domain\Ports\ChatRepositoryInterface;
use App\Domain\Entities\ChatConversacion;

class CreateConversacionUseCase {
    private $chatRepository;

    public function __construct(ChatRepositoryInterface $chatRepository) {
        $this->chatRepository = $chatRepository;
    }

    public function execute(array $data): ChatConversacion {
        $conversacion = new ChatConversacion(
            null,
            $data['titulo'],
            $data['incidencia_id'] ?? null,
            $data['usuario_nombre'],
            $data['estado'] ?? 'activa'
        );
        return $this->chatRepository->saveConversacion($conversacion);
    }
}
