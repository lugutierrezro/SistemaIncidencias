<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Entities\Incidencia.php

namespace App\Domain\Entities;

class Incidencia {
    public ?string $id;
    public string $titulo;
    public ?string $descripcion;
    public string $estado;
    public string $prioridad;
    public ?string $aula_id;
    public ?string $reportado_por;
    public ?string $asignado_a;
    public ?string $fecha_cierre;
    public ?string $inserted_at;
    public ?string $updated_at;
    public ?string $aula_nombre; // Helper para UI

    public function __construct(
        ?string $id,
        string $titulo,
        ?string $descripcion,
        string $estado,
        string $prioridad,
        ?string $aula_id,
        ?string $reportado_por,
        ?string $asignado_a = null,
        ?string $fecha_cierre = null,
        ?string $inserted_at = null,
        ?string $updated_at = null,
        ?string $aula_nombre = null
    ) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->descripcion = $descripcion;
        $this->estado = $estado;
        $this->prioridad = $prioridad;
        $this->aula_id = $aula_id;
        $this->reportado_por = $reportado_por;
        $this->asignado_a = $asignado_a;
        $this->fecha_cierre = $fecha_cierre;
        $this->inserted_at = $inserted_at;
        $this->updated_at = $updated_at;
        $this->aula_nombre = $aula_nombre;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'prioridad' => $this->prioridad,
            'aula_id' => $this->aula_id,
            'reportado_por' => $this->reportado_por,
            'asignado_a' => $this->asignado_a,
            'fecha_cierre' => $this->fecha_cierre,
            'inserted_at' => $this->inserted_at,
            'updated_at' => $this->updated_at,
            'aula_nombre' => $this->aula_nombre
        ];
    }
}
