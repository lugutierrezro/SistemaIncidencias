<?php
// ──────────────────────────────────────────────────────────────
// config.php — Configuración de conexión a SQL Server
// Reemplaza la dependencia al backend Elixir (localhost:4000)
// ──────────────────────────────────────────────────────────────

require_once dirname(__DIR__, 2) . '/src/autoload.php';

define('DB_SERVER',   'GUTIERREZ\\MSSQLSERVERMULTI');  // Instancia SQL Server
define('DB_NAME',     'SistemaIncidencias');

/**
 * Retorna una conexión activa a SQL Server usando sqlsrv
 * con Autenticación de Windows (Trusted_Connection).
 * Lanza una excepción si no puede conectar.
 */
function getDbConnection() {
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

    return $conn;
}

/**
 * Envía una respuesta JSON y termina la ejecución.
 */
function jsonResponse($data, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Envía una respuesta de error JSON.
 */
function jsonError(string $message, int $httpCode = 500): void {
    jsonResponse(['error' => $message], $httpCode);
}

/**
 * Obtiene el cuerpo de la petición como array PHP.
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// Manejo de preflight CORS
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}
