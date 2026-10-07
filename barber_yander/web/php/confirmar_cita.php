<?php
// =========================================================
// CONFIRMAR CITA (ADMIN) — BARBERÍA YANDER
// Protección de Sesión Admin y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$id = intval($jsonData['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de cita inválido."]);
    exit;
}

$stmt = $conn->prepare("UPDATE citas SET estado = 'confirmada' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cita #$id confirmada correctamente."]);
} else {
    error_log("Error al confirmar cita: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "No se pudo confirmar la cita."]);
}

$stmt->close();
$conn->close();
?>
