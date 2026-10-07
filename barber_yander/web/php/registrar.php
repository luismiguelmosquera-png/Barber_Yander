<?php
// =========================================================
// REGISTRO DE USUARIO — BARBERÍA YANDER
// Validaciones en Servidor, Encriptación Bcrypt y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

// Leer datos enviados (soporta FormData POST y JSON)
$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$nombre    = trim($jsonData['nombre'] ?? $_POST['nombre'] ?? '');
$correo    = strtolower(trim($jsonData['usuario'] ?? $jsonData['correo'] ?? $_POST['usuario'] ?? $_POST['correo'] ?? ''));
$telefono  = trim($jsonData['telefono'] ?? $_POST['telefono'] ?? '');
$tipo_doc  = trim($jsonData['tipo_doc'] ?? $_POST['tipo_doc'] ?? '');
$num_doc   = trim($jsonData['num_doc'] ?? $_POST['num_doc'] ?? '');
$password  = trim($jsonData['password'] ?? $_POST['password'] ?? '');

// 1. Validaciones de presencia
if (!$nombre || !$correo || !$telefono || !$tipo_doc || !$num_doc || !$password) {
    echo json_encode([
        "success" => false, 
        "message" => "Todos los campos son obligatorios para completar el registro."
    ]);
    exit;
}

// 2. Validar que el nombre contenga solo letras y espacios
if (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u", $nombre)) {
    echo json_encode([
        "success" => false, 
        "message" => "El nombre solo puede contener letras y espacios."
    ]);
    exit;
}

if (mb_strlen($nombre) > 70) {
    echo json_encode([
        "success" => false, 
        "message" => "El nombre no puede exceder los 70 caracteres."
    ]);
    exit;
}

// 3. Validar formato de correo electrónico
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false, 
        "message" => "El formato del correo electrónico ingresado no es válido."
    ]);
    exit;
}

// 4. Validar teléfono (10 dígitos numéricos)
if (!preg_match("/^\d{10}$/", $telefono)) {
    echo json_encode([
        "success" => false, 
        "message" => "El teléfono debe contener exactamente 10 dígitos numéricos."
    ]);
    exit;
}

// 5. Validar longitud mínima de contraseña
if (strlen($password) < 4) {
    echo json_encode([
        "success" => false, 
        "message" => "La contraseña debe tener un mínimo de 4 caracteres."
    ]);
    exit;
}

// 6. Evitar duplicados de correo en la base de datos
$check = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(correo) = LOWER(?) LIMIT 1");
$check->bind_param("s", $correo);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Este correo electrónico ya se encuentra registrado en el sistema."
    ]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// 7. Encriptar contraseña de forma segura con Bcrypt
$hashContrasena = password_hash($password, PASSWORD_BCRYPT);

// 8. Insertar usuario en MySQL
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, telefono, tipo_doc, num_doc, contrasena, rol, estado) VALUES (?, ?, ?, ?, ?, ?, 'cliente', 'activo')");
$stmt->bind_param("ssssss", $nombre, $correo, $telefono, $tipo_doc, $num_doc, $hashContrasena);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true, 
        "message" => "¡Usuario registrado correctamente! Ya puedes iniciar sesión con tu correo."
    ]);
} else {
    error_log("Error al registrar usuario: " . $stmt->error);
    echo json_encode([
        "success" => false, 
        "message" => "Ocurrió un error al registrar el usuario. Por favor intenta nuevamente."
    ]);
}

$stmt->close();
$conn->close();
?>
