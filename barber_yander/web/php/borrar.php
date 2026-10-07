<?php
// =========================================================
// ELIMINAR USUARIO (ADMIN) — BARBERÍA YANDER
// Protección de Sesión Admin y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();
header("Content-Type: application/json; charset=utf-8");

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID de usuario inválido."]);
    exit;
}

// Proteger administradores
$check = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    $u = $res->fetch_assoc();
    if ($u['rol'] === 'admin') {
        echo json_encode(["success" => false, "message" => "No se puede eliminar a un administrador."]);
        $check->close();
        $conn->close();
        exit;
    }
}
$check->close();

// Eliminar citas asociadas primero (por consistencia de llaves foráneas)
$delCitas = $conn->prepare("DELETE FROM citas WHERE id_usuario = ?");
$delCitas->bind_param("i", $id);
$delCitas->execute();
$delCitas->close();

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Usuario y sus registros asociados han sido eliminados correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar usuario: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
