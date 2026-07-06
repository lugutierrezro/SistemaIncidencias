<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Incidencia\CreateIncidenciaUseCase.php

namespace App\Application\UseCases\Incidencia;

use App\Domain\Ports\IncidenciaRepositoryInterface;
use App\Domain\Entities\Incidencia;

class CreateIncidenciaUseCase {
    private $incidenciaRepository;

    public function __construct(IncidenciaRepositoryInterface $incidenciaRepository) {
        $this->incidenciaRepository = $incidenciaRepository;
    }

    public function execute(array $data): Incidencia {
        $incidencia = new Incidencia(
            null,
            $data['titulo'],
            $data['descripcion'] ?? null,
            $data['estado'] ?? 'abierta',
            $data['prioridad'] ?? 'media',
            $data['aula_id'] ?? null,
            $data['reportado_por'] ?? 'Anónimo'
        );
        return $this->incidenciaRepository->save($incidencia);
    }
}
