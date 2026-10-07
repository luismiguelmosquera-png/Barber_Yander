<?php
// =========================================================
// CIERRE DE SESIÓN — BARBERÍA YANDER
// Destruye variables de sesión, cookie y asegura no-caché
// =========================================================

require_once __DIR__ . '/seguridad.php';

cerrarSesionCompleta();

// Si la petición fue vía AJAX/Fetch, devolver JSON consistente
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) || isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "success" => true,
        "message" => "Sesión cerrada correctamente",
        "redirect" => "login.html"
    ]);
    exit;
}

// Si fue por enlace normal GET, redirigir directamente
header("Location: ../login.html");
exit;
?>
