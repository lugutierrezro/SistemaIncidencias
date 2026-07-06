<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Infrastructure\Persistence\Sqlsrv\SqlsrvConnection.php

namespace App\Infrastructure\Persistence\Sqlsrv;

use RuntimeException;

class SqlsrvConnection {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            // Cargar config.php si no se han definido las constantes de la BD
            if (!defined('DB_SERVER')) {
                $configFile = dirname(__DIR__, 4) . '/public/api/config.php';
                if (file_exists($configFile)) {
                    require_once $configFile;
                } else {
                    define('DB_SERVER', 'GUTIERREZ\\MSSQLSERVERMULTI');
                    define('DB_NAME', 'SistemaIncidencias');
                }
            }

            $connectionInfo = [
                'Database'               => DB_NAME,
                'CharacterSet'           => 'UTF-8',
                'ReturnDatesAsStrings'   => true,
                'TrustServerCertificate' => true,
            ];

            $conn = sqlsrv_connect(DB_SERVER, $connectionInfo);

            if ($conn === false) {
                $errors = sqlsrv_errors();
                $msg = ($errors && isset($errors[0]['message'])) ? $errors[0]['message'] : 'Error desconocido';
                throw new RuntimeException('No se pudo conectar a SQL Server (' . DB_SERVER . '): ' . $msg);
            }

            self::$connection = $conn;
        }

        return self::$connection;
    }
}
