<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Entities\Usuario.php

namespace App\Domain\Entities;

class Usuario {
    public ?string $id;
    public string $nombre;
    public string $email;
    public string $rol;
    public string $estado;
    public ?string $inserted_at;
    public ?string $updated_at;
    public ?string $nombre_usuario; // Helper para UI (@usuario)

    public function __construct(
        ?string $id,
        string $nombre,
        string $email,
        string $rol,
        string $estado,
        ?string $inserted_at = null,
        ?string $updated_at = null,
        ?string $nombre_usuario = null
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->rol = $rol;
        $this->estado = $estado;
        $this->inserted_at = $inserted_at;
        $this->updated_at = $updated_at;
        $this->nombre_usuario = $nombre_usuario ?? strtolower(explode('@', $email)[0]);
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'rol' => $this->rol,
            'estado' => $this->estado,
            'inserted_at' => $this->inserted_at,
            'updated_at' => $this->updated_at,
            'nombre_usuario' => $this->nombre_usuario
        ];
    }
}
