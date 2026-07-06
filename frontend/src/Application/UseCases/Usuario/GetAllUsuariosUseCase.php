<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Application\UseCases\Usuario\GetAllUsuariosUseCase.php

namespace App\Application\UseCases\Usuario;

use App\Domain\Ports\UsuarioRepositoryInterface;

class GetAllUsuariosUseCase {
    private $usuarioRepository;

    public function __construct(UsuarioRepositoryInterface $usuarioRepository) {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function execute(): array {
        return $this->usuarioRepository->findAll();
    }
}
