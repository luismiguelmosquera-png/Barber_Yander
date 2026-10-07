<?php
// =========================================================
// CONEXIÓN A BASE DE DATOS — BARBERÍA YANDER
// Centralizada, con soporte para entorno y manejo seguro de errores
// =========================================================

require_once __DIR__ . '/config.php';

// Desactivar reporte público de errores internos de MySQL para evitar fugas de información
mysqli_report(MYSQLI_REPORT_OFF);

$servername = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;
$port       = DB_PORT;

$sslEnable = filter_var(env('DB_SSL', false), FILTER_VALIDATE_BOOLEAN) || ((int)$port === 4000);
$sslCa     = env('DB_SSL_CA', null);

if ($sslEnable || $sslCa) {
    $conn = mysqli_init();
    if ($sslCa && file_exists($sslCa)) {
        $conn->ssl_set(NULL, NULL, $sslCa, NULL, NULL);
    }
    @$conn->real_connect($servername, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    $conn = @new mysqli($servername, $username, $password, $database, $port);
}

// Fallback inteligente: si falla la contraseña y el entorno es local/XAMPP, probar contraseña vacía o viceversa
if ($conn->connect_error) {
    if (!empty($password) && ($servername === 'localhost' || $servername === '127.0.0.1')) {
        // Intentar con password vacío (típico de XAMPP por defecto)
        $conn = @new mysqli($servername, $username, "", $database, $port);
    }
}

// Verifica la conexión final
if ($conn->connect_error) {
    error_log("Error de conexión a MySQL Barber Yander: " . $conn->connect_error);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        "success" => false, 
        "message" => "No se pudo establecer conexión con la base de datos del sistema. Por favor contacta al administrador."
    ]);
    exit;
}

// Configurar juego de caracteres a utf8mb4 para soporte de emojis, tildes y caracteres especiales
$conn->set_charset("utf8mb4");
?>