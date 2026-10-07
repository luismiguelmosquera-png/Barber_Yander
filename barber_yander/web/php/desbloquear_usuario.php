<?php
// =========================================================
// DESBLOQUEAR USUARIO (ADMIN) — BARBERÍA YANDER
// Protección de Sesión Admin y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID de usuario inválido."]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE usuarios 
    SET estado = 'activo', bloqueado_desde = NULL, bloqueado_hasta = NULL, motivo_bloqueo = NULL 
    WHERE id = ?
");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Usuario desbloqueado correctamente. Ya puede iniciar sesión."
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error al desbloquear usuario: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
