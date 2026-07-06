<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Aula\GetAllAulasUseCase.php

namespace App\Application\UseCases\Aula;

use App\Domain\Ports\AulaRepositoryInterface;

class GetAllAulasUseCase {
    private $aulaRepository;

    public function __construct(AulaRepositoryInterface $aulaRepository) {
        $this->aulaRepository = $aulaRepository;
    }

    public function execute(): array {
        return $this->aulaRepository->findAll();
    }
}
