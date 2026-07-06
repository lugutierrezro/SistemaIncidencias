<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Chat\GetAllConversacionesUseCase.php

namespace App\Application\UseCases\Chat;

use App\Domain\Ports\ChatRepositoryInterface;

class GetAllConversacionesUseCase {
    private $chatRepository;

    public function __construct(ChatRepositoryInterface $chatRepository) {
        $this->chatRepository = $chatRepository;
    }

    public function execute(): array {
        return $this->chatRepository->findAllConversaciones();
    }
}
