<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Entities\ChatMensaje.php

namespace App\Domain\Entities;

class ChatMensaje {
    public ?string $id;
    public string $conversacion_id;
    public string $contenido;
    public string $remitente;
    public string $tipo_remitente;
    public ?string $inserted_at;

    public function __construct(
        ?string $id,
        string $conversacion_id,
        string $contenido,
        string $remitente,
        string $tipo_remitente,
        ?string $inserted_at = null
    ) {
        $this->id = $id;
        $this->conversacion_id = $conversacion_id;
        $this->contenido = $contenido;
        $this->remitente = $remitente;
        $this->tipo_remitente = $tipo_remitente;
        $this->inserted_at = $inserted_at;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'conversacion_id' => $this->conversacion_id,
            'contenido' => $this->contenido,
            'remitente' => $this->remitente,
            'tipo_remitente' => $this->tipo_remitente,
            'inserted_at' => $this->inserted_at
        ];
    }
}
