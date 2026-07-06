<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Incidencia\GetAllIncidenciasUseCase.php

namespace App\Application\UseCases\Incidencia;

use App\Domain\Ports\IncidenciaRepositoryInterface;

class GetAllIncidenciasUseCase {
    private $incidenciaRepository;

    public function __construct(IncidenciaRepositoryInterface $incidenciaRepository) {
        $this->incidenciaRepository = $incidenciaRepository;
    }

    public function execute(): array {
        return $this->incidenciaRepository->findAll();
    }
}
