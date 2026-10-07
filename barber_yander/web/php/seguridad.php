<?php
// =========================================================
// SEGURIDAD Y CONTROL DE SESIONES — BARBERÍA YANDER
// Cabeceras Anti-Caché, Validación de Permisos y Protección CSRF/Sesión
// =========================================================

require_once __DIR__ . '/config.php';

/**
 * Aplica cabeceras HTTP estrictas para evitar que el navegador guarde
 * en memoria caché páginas privadas o respuestas sensibles (requisitos 14, 15, 16).
 */
function aplicarCabecerasNoCache() {
    if (!headers_sent()) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
    }
}

/**
 * Inicia la sesión PHP con parámetros de cookie seguros
 */
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        $esHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                   (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        $cookieParams = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $esHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);
        session_start();
    }
}

/**
 * Verifica la sesión activa del usuario y valida su rol de forma estricta.
 *
 * @param string|null $rolRequerido 'admin', 'cliente' o null (cualquier autenticado)
 * @param bool $esApi Si es true devuelve JSON 401/403, si es false redirige a login.html
 */
function verificarSesion($rolRequerido = null, $esApi = false) {
    aplicarCabecerasNoCache();
    iniciarSesionSegura();

    // 1. Verificar si existe identificación de sesión
    if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
        if ($esApi) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode([
                "success" => false, 
                "sesion_expirada" => true,
                "message" => "Sesión no válida o expirada. Por favor inicia sesión nuevamente.",
                "redirect" => "login.html"
            ]);
            exit;
        } else {
            header("Location: login.html");
            exit;
        }
    }

    // 2. Control de expiración por inactividad
    $ahora = time();
    $tiempoLimite = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
    if (isset($_SESSION['ultimo_acceso']) && ($ahora - $_SESSION['ultimo_acceso']) > $tiempoLimite) {
        cerrarSesionCompleta();
        if ($esApi) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode([
                "success" => false, 
                "sesion_expirada" => true,
                "message" => "Tu sesión ha expirado por inactividad.",
                "redirect" => "login.html"
            ]);
            exit;
        } else {
            header("Location: login.html?expirada=1");
            exit;
        }
    }
    $_SESSION['ultimo_acceso'] = $ahora;

    // 3. Validación de permisos por rol
    if ($rolRequerido !== null) {
        $rolUsuario = $_SESSION['rol'] ?? 'cliente';
        if ($rolRequerido === 'admin' && $rolUsuario !== 'admin') {
            if ($esApi) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode([
                    "success" => false, 
                    "message" => "Acceso restringido: no tienes permisos de administrador."
                ]);
                exit;
            } else {
                header("Location: login.html?error=no_autorizado");
                exit;
            }
        }
    }
}

/**
 * Destruye la sesión por completo invalidando cookie y variables (requisito 18).
 */
function cerrarSesionCompleta() {
    aplicarCabecerasNoCache();
    iniciarSesionSegura();

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}
?>
