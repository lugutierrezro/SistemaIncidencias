<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Ports\AulaRepositoryInterface.php

namespace App\Domain\Ports;

use App\Domain\Entities\Aula;

interface AulaRepositoryInterface {
    public function save(Aula $aula): Aula;
    public function findAll(): array;
    public function findById(string $id): ?Aula;
}
