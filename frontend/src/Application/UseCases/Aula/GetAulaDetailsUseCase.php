<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Aula\GetAulaDetailsUseCase.php

namespace App\Application\UseCases\Aula;

use App\Domain\Ports\AulaRepositoryInterface;
use App\Domain\Ports\IncidenciaRepositoryInterface;

class GetAulaDetailsUseCase {
    private $aulaRepository;
    private $incidenciaRepository;

    public function __construct(
        AulaRepositoryInterface $aulaRepository,
        IncidenciaRepositoryInterface $incidenciaRepository
    ) {
        $this->aulaRepository = $aulaRepository;
        $this->incidenciaRepository = $incidenciaRepository;
    }

    public function execute(string $id): array {
        $aula = $this->aulaRepository->findById($id);
        if (!$aula) {
            return [];
        }

        // Obtener todas las incidencias y filtrar las de este aula
        $todas = $this->incidenciaRepository->findAll();
        $incidenciasAula = [];
        foreach ($todas as $inc) {
            if ($inc->aula_id === $id) {
                $incidenciasAula[] = $inc->toArray();
            }
        }

        return [
            'aula' => $aula->toArray(),
            'incidencias' => $incidenciasAula
        ];
    }
}
