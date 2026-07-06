<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Ports\IncidenciaRepositoryInterface.php

namespace App\Domain\Ports;

use App\Domain\Entities\Incidencia;

interface IncidenciaRepositoryInterface {
    public function save(Incidencia $incidencia): Incidencia;
    public function findAll(): array;
    public function findById(string $id): ?Incidencia;
    public function resolve(string $id): bool;
}
