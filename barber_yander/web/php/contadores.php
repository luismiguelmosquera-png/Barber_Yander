<?php 
// =========================================================
// CONTADORES DE CITAS (ADMIN) — BARBERÍA YANDER
// Protección de Sesión Admin y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

function contar($estado, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM citas WHERE estado = ?");
    $stmt->bind_param("s", $estado);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($result['total'] ?? 0);
}

$resTotal = $conn->query("SELECT COUNT(*) AS total FROM citas");
$total = (int)($resTotal->fetch_assoc()['total'] ?? 0);

echo json_encode([
    "success"     => true,
    "total"       => $total,
    "pendientes"  => contar("pendiente", $conn),
    "confirmadas" => contar("confirmada", $conn),
    "completadas" => contar("completada", $conn)
]);

$conn->close();
?>
