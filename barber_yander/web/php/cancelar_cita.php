<?php
// =========================================================
// CANCELAR CITA — BARBERÍA YANDER
// Verificación de Permisos (Admin o Dueño de la Cita) y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion(null, true);
aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$idUsuarioSesion = (int)$_SESSION['id_usuario'];
$rolSesion = $_SESSION['rol'] ?? 'cliente';

$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$idCita = intval($jsonData['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);

if ($idCita <= 0) {
    echo json_encode(["success" => false, "message" => "ID de cita inválido."]);
    exit;
}

// 1. Verificar si la cita existe y obtener su estado e id_usuario
$stmt = $conn->prepare("SELECT id, id_usuario, estado FROM citas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $idCita);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "La cita no existe o ya fue eliminada."]);
    $stmt->close();
    $conn->close();
    exit;
}

$cita = $res->fetch_assoc();
$stmt->close();

// 2. Si no es admin, solo puede cancelar sus propias citas (requisito 24)
if ($rolSesion !== 'admin' && (int)$cita['id_usuario'] !== $idUsuarioSesion) {
    echo json_encode(["success" => false, "message" => "No tienes autorización para cancelar esta cita."]);
    $conn->close();
    exit;
}

// 3. Validar si ya está completada o cancelada
if ($cita['estado'] === 'cancelada') {
    echo json_encode(["success" => false, "message" => "Esta cita ya se encuentra cancelada."]);
    $conn->close();
    exit;
}

if ($cita['estado'] === 'completada' && $rolSesion !== 'admin') {
    echo json_encode(["success" => false, "message" => "No es posible cancelar una cita que ya fue completada."]);
    $conn->close();
    exit;
}

// 4. Actualizar estado a 'cancelada'
$upd = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
$upd->bind_param("i", $idCita);

if ($upd->execute()) {
    echo json_encode([
        "success" => true, 
        "status"  => "ok",
        "message" => "Cita #$idCita cancelada correctamente."
    ]);
} else {
    error_log("Error al cancelar cita: " . $upd->error);
    echo json_encode(["success" => false, "message" => "No se pudo cancelar la cita."]);
}

$upd->close();
$conn->close();
?>
