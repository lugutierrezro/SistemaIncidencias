<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Chat\CloseConversacionUseCase.php

namespace App\Application\UseCases\Chat;

use App\Domain\Ports\ChatRepositoryInterface;

class CloseConversacionUseCase {
    private $chatRepository;

    public function __construct(ChatRepositoryInterface $chatRepository) {
        $this->chatRepository = $chatRepository;
    }

    public function execute(string $id): bool {
        return $this->chatRepository->closeConversacion($id);
    }
}
