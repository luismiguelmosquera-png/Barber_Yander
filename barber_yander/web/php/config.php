<?php
// =========================================================
// CONFIGURACIÓN GLOBAL — BARBERÍA YANDER
// Carga segura de variables de entorno y constantes del sistema
// =========================================================

// Cargar variables desde archivo .env si existe (raíz o carpeta web)
function cargarEnv($ruta) {
    if (!file_exists($ruta)) return;
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || strpos($linea, '#') === 0) continue;
        if (strpos($linea, '=') !== false) {
            list($nombre, $valor) = explode('=', $linea, 2);
            $nombre = trim($nombre);
            $valor = trim($valor);
            // Quitar comillas si las tiene
            if (preg_match('/^(["\'])(.*)\1$/', $valor, $matches)) {
                $valor = $matches[2];
            }
            if (!array_key_exists($nombre, $_SERVER) && !array_key_exists($nombre, $_ENV)) {
                putenv("$nombre=$valor");
                $_ENV[$nombre] = $valor;
                $_SERVER[$nombre] = $valor;
            }
        }
    }
}

// Intentar cargar .env desde varios niveles posibles
$posiblesRutasEnv = [
    __DIR__ . '/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
    dirname(__DIR__, 2) . '/.env'
];
foreach ($posiblesRutasEnv as $rutaEnv) {
    if (file_exists($rutaEnv)) {
        cargarEnv($rutaEnv);
        break;
    }
}

// Helper para obtener variable con valor por defecto
if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $val;
    }
}

// --- CONFIGURACIÓN BASE DE DATOS (MYSQL) ---
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', (int)env('DB_PORT', 3306));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'barber_yander'));

// --- CONFIGURACIÓN CORREO ELECTRÓNICO (SMTP) ---
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM', env('SMTP_FROM', 'no-reply@barberyander.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Barbería Yander'));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls')); // 'tls' o 'ssl'

// --- PARÁMETROS DE RECUPERACIÓN Y SEGURIDAD ---
define('RESET_CODE_EXPIRY_MINUTES', 10); // 10 minutos de vigencia
define('RESET_CODE_MAX_ATTEMPTS', 5);    // Máximo 5 intentos erróneos
define('SESSION_LIFETIME', 3600);        // 1 hora de inactividad
?>
