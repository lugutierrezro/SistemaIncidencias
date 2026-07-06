<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Incidencia\ResolveIncidenciaUseCase.php

namespace App\Application\UseCases\Incidencia;

use App\Domain\Ports\IncidenciaRepositoryInterface;

class ResolveIncidenciaUseCase {
    private $incidenciaRepository;

    public function __construct(IncidenciaRepositoryInterface $incidenciaRepository) {
        $this->incidenciaRepository = $incidenciaRepository;
    }

    public function execute(string $id): bool {
        return $this->incidenciaRepository->resolve($id);
    }
}
