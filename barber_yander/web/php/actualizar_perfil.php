<?php
// =========================================================
// ACTUALIZAR PERFIL — BARBERÍA YANDER
// Protección de Sesión, Validación de Datos y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion(null, true);
aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$id_usuario = (int)$_SESSION['id_usuario'];

$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$nombre   = trim($jsonData['nombre'] ?? $_POST['nombre'] ?? '');
$telefono = trim($jsonData['telefono'] ?? $_POST['telefono'] ?? '');
$correo   = strtolower(trim($jsonData['correo'] ?? $_POST['correo'] ?? ''));
$num_doc  = trim($jsonData['num_doc'] ?? $_POST['num_doc'] ?? '');

if (!$nombre || !$correo || !$telefono) {
    echo json_encode(["success" => false, "message" => "Nombre, teléfono y correo son obligatorios."]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "El correo no tiene un formato válido."]);
    exit;
}

// Verificar que el correo no esté ocupado por otro usuario distinto
$check = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(correo) = LOWER(?) AND id != ? LIMIT 1");
$check->bind_param("si", $correo, $id_usuario);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Este correo ya se encuentra registrado por otro usuario."]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Actualizar datos en MySQL
$stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, correo = ?, num_doc = ? WHERE id = ?");
$stmt->bind_param("ssssi", $nombre, $telefono, $correo, $num_doc, $id_usuario);

if ($stmt->execute()) {
    $_SESSION['usuario'] = $nombre;
    $_SESSION['correo']  = $correo;
    $_SESSION['telefono']= $telefono;
    $_SESSION['num_doc'] = $num_doc;

    echo json_encode([
        "success" => true, 
        "message" => "Tus datos personales han sido actualizados correctamente.",
        "usuario" => [
            "id"       => $id_usuario,
            "nombre"   => $nombre,
            "correo"   => $correo,
            "telefono" => $telefono,
            "num_doc"  => $num_doc,
            "rol"      => $_SESSION['rol']
        ]
    ]);
} else {
    error_log("Error al actualizar perfil: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "Error al actualizar los datos en el servidor."]);
}

$stmt->close();
$conn->close();
?>
