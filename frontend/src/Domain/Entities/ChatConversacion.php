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
    public ?string $categoria_nombre;
    public ?string $subcategoria_nombre;
    public ?string $prioridad;
    public ?string $aula_nombre;

    public function __construct(
        ?string $id,
        string $titulo,
        ?string $incidencia_id,
        string $usuario_nombre,
        string $estado = 'activa',
        ?string $inserted_at = null,
        ?string $updated_at = null,
        ?string $incidencia_titulo = null,
        ?string $categoria_nombre = null,
        ?string $subcategoria_nombre = null,
        ?string $prioridad = null,
        ?string $aula_nombre = null
    ) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->incidencia_id = $incidencia_id;
        $this->usuario_nombre = $usuario_nombre;
        $this->estado = $estado;
        $this->inserted_at = $inserted_at;
        $this->updated_at = $updated_at;
        $this->incidencia_titulo = $incidencia_titulo;
        $this->categoria_nombre = $categoria_nombre;
        $this->subcategoria_nombre = $subcategoria_nombre;
        $this->prioridad = $prioridad;
        $this->aula_nombre = $aula_nombre;
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
            'incidencia_titulo' => $this->incidencia_titulo,
            'categoria_nombre' => $this->categoria_nombre,
            'subcategoria_nombre' => $this->subcategoria_nombre,
            'prioridad' => $this->prioridad,
            'aula_nombre' => $this->aula_nombre
        ];
    }
}
