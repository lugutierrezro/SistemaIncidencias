<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Chat\GetMessagesUseCase.php

namespace App\Application\UseCases\Chat;

use App\Domain\Ports\ChatRepositoryInterface;

class GetMessagesUseCase {
    private $chatRepository;

    public function __construct(ChatRepositoryInterface $chatRepository) {
        $this->chatRepository = $chatRepository;
    }

    public function execute(string $convId, ?string $desdeId = null): array {
        if ($desdeId) {
            return $this->chatRepository->findMensajesNuevosByConversacion($convId, $desdeId);
        }
        return $this->chatRepository->findMensajesByConversacion($convId);
    }
}
