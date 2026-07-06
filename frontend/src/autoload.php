<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\autoload.php
// Autoloader PSR-4 personalizado para la arquitectura hexagonal

spl_autoload_register(function ($class) {
    // Prefijo de namespace del proyecto
    $prefix = 'App\\';

    // Directorio base para el prefijo del namespace
    $base_dir = __DIR__ . '/';

    // ¿La clase utiliza el prefijo del namespace?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, mover al siguiente autoloader registrado
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);

    // Reemplazar el prefijo del namespace con el directorio base, reemplazar los
    // separadores de namespace con separadores de directorio en el nombre de la
    // clase relativa, agregar .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require $file;
    }
});
