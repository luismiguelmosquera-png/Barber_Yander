<?php
// =========================================================
// BLOQUEAR USUARIO (ADMIN) — BARBERÍA YANDER
// Protección de Sesión Admin y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$duracion = $_POST['duracion'] ?? $_GET['duracion'] ?? '1d';
$motivo = trim($_POST['motivo'] ?? 'Bloqueo administrativo temporal');

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID de usuario inválido."]);
    exit;
}

// Verificar que el usuario no sea admin
$check = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "El usuario no existe."]);
    $check->close();
    $conn->close();
    exit;
}

$userRow = $res->fetch_assoc();
if ($userRow['rol'] === 'admin') {
    echo json_encode(["success" => false, "message" => "No se puede bloquear a un administrador."]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

$ahora = time();
$hasta = null;

switch ($duracion) {
    case '1h':
        $hasta = date("Y-m-d H:i:s", strtotime("+1 hour", $ahora));
        break;
    case '1d':
        $hasta = date("Y-m-d H:i:s", strtotime("+1 day", $ahora));
        break;
    case '3d':
        $hasta = date("Y-m-d H:i:s", strtotime("+3 days", $ahora));
        break;
    case '7d':
        $hasta = date("Y-m-d H:i:s", strtotime("+7 days", $ahora));
        break;
    case '30d':
        $hasta = date("Y-m-d H:i:s", strtotime("+30 days", $ahora));
        break;
    default:
        if (strtotime($duracion)) {
            $hasta = date("Y-m-d H:i:s", strtotime($duracion));
        } else {
            $hasta = date("Y-m-d H:i:s", strtotime("+1 day", $ahora));
        }
        break;
}

$desde = date("Y-m-d H:i:s", $ahora);

$stmt = $conn->prepare("
    UPDATE usuarios 
    SET estado = 'bloqueado', bloqueado_desde = ?, bloqueado_hasta = ?, motivo_bloqueo = ? 
    WHERE id = ?
");
$stmt->bind_param("sssi", $desde, $hasta, $motivo, $id);

if ($stmt->execute()) {
    $fechaTexto = date("d/m/Y H:i", strtotime($hasta));
    echo json_encode([
        "success" => true,
        "message" => "Usuario bloqueado temporalmente hasta el " . $fechaTexto,
        "bloqueado_hasta" => $hasta
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error al bloquear usuario: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
