<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Entities\Aula.php

namespace App\Domain\Entities;

class Aula {
    public ?string $id;
    public string $nombre;
    public ?string $edificio;
    public ?string $piso;
    public int $capacidad;
    public ?string $equipamiento;
    public string $estado;
    public ?string $inserted_at;
    public ?string $updated_at;
    public ?string $qr_url;

    public function __construct(
        ?string $id,
        string $nombre,
        ?string $edificio,
        ?string $piso,
        int $capacidad,
        ?string $equipamiento,
        string $estado = 'disponible',
        ?string $inserted_at = null,
        ?string $updated_at = null,
        ?string $qr_url = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->edificio = $edificio;
        $this->piso = $piso;
        $this->capacidad = $capacidad;
        $this->equipamiento = $equipamiento;
        $this->estado = $estado;
        $this->inserted_at = $inserted_at;
        $this->updated_at = $updated_at;
        $this->qr_url = $qr_url;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'edificio' => $this->edificio,
            'piso' => $this->piso,
            'capacidad' => $this->capacidad,
            'equipamiento' => $this->equipamiento,
            'estado' => $this->estado,
            'inserted_at' => $this->inserted_at,
            'updated_at' => $this->updated_at,
            'qr_url' => $this->qr_url
        ];
    }
}
