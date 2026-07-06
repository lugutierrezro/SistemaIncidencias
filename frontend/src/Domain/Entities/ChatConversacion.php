<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Entities\ChatConversacion.php

namespace App\Domain\Entities;

class ChatConversacion {
    public ?string $id;
    public string $titulo;
    public ?string $incidencia_id;
    public string $usuario_nombre;
    public string $estado;
    public ?string $inserted_at;
    public ?string $updated_at;
    public ?string $incidencia_titulo; // Helper para UI

    public function __construct(
        ?string $id,
        string $titulo,
        ?string $incidencia_id,
        string $usuario_nombre,
        string $estado = 'activa',
        ?string $inserted_at = null,
        ?string $updated_at = null,
        ?string $incidencia_titulo = null
    ) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->incidencia_id = $incidencia_id;
        $this->usuario_nombre = $usuario_nombre;
        $this->estado = $estado;
        $this->inserted_at = $inserted_at;
        $this->updated_at = $updated_at;
        $this->incidencia_titulo = $incidencia_titulo;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'incidencia_id' => $this->incidencia_id,
            'usuario_nombre' => $this->usuario_nombre,
            'estado' => $this->estado,
            'inserted_at' => $this->inserted_at,
            'updated_at' => $this->updated_at,
            'incidencia_titulo' => $this->incidencia_titulo
        ];
    }
}
