<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Aula\CreateAulaUseCase.php

namespace App\Application\UseCases\Aula;

use App\Domain\Ports\AulaRepositoryInterface;
use App\Domain\Entities\Aula;

class CreateAulaUseCase {
    private $aulaRepository;

    public function __construct(AulaRepositoryInterface $aulaRepository) {
        $this->aulaRepository = $aulaRepository;
    }

    public function execute(array $data): Aula {
        $aula = new Aula(
            null,
            $data['nombre'],
            $data['edificio'] ?? null,
            $data['piso'] ?? null,
            (int)($data['capacidad'] ?? 30),
            $data['equipamiento'] ?? null,
            $data['estado'] ?? 'disponible'
        );
        return $this->aulaRepository->save($aula);
    }
}
