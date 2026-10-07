<?php
// =========================================================
// SOLICITAR CÓDIGO DE RECUPERACIÓN — BARBERÍA YANDER
// Genera código aleatorio de 6 dígitos, hash en BD y envío por correo
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/mailer.php';

aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$correo = strtolower(trim($jsonData['correo'] ?? $jsonData['usuario'] ?? $_POST['correo'] ?? $_POST['usuario'] ?? ''));

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false, 
        "message" => "Por favor ingresa un correo electrónico válido."
    ]);
    exit;
}

// 1. Verificar si el usuario existe en MySQL
$stmt = $conn->prepare("SELECT id, nombre, correo, estado FROM usuarios WHERE LOWER(correo) = LOWER(?) LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // Por seguridad, mensaje informativo consistente
    echo json_encode([
        "success" => false, 
        "message" => "No existe ninguna cuenta registrada con este correo electrónico."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$usuario = $res->fetch_assoc();
$stmt->close();

if ($usuario['estado'] === 'bloqueado') {
    echo json_encode([
        "success" => false, 
        "message" => "Esta cuenta se encuentra temporalmente suspendida. No es posible restablecer la contraseña."
    ]);
    $conn->close();
    exit;
}

$idUsuario = (int)$usuario['id'];
$nombreUsuario = $usuario['nombre'];

// 2. Generar código aleatorio criptográficamente seguro de 6 dígitos
$codigoPlano = (string)random_int(100000, 999999);

// 3. Generar hash del código (NUNCA guardar el código en texto plano en la BD - requisito 6)
$codigoHash = password_hash($codigoPlano, PASSWORD_BCRYPT);

// 4. Invalidar códigos previos que hayan quedado sin usar para este usuario
$inv = $conn->prepare("UPDATE password_reset_codes SET usado = 1 WHERE usuario_id = ? AND usado = 0");
$inv->bind_param("i", $idUsuario);
$inv->execute();
$inv->close();

// 5. Calcular fecha de expiración (10 minutos)
$minutosValidez = defined('RESET_CODE_EXPIRY_MINUTES') ? RESET_CODE_EXPIRY_MINUTES : 10;
$fechaExpiracion = date('Y-m-d H:i:s', time() + ($minutosValidez * 60));

// 6. Guardar código en la tabla password_reset_codes
$ins = $conn->prepare("INSERT INTO password_reset_codes (usuario_id, codigo_hash, intentos, usado, fecha_expiracion) VALUES (?, ?, 0, 0, ?)");
$ins->bind_param("iss", $idUsuario, $codigoHash, $fechaExpiracion);

if (!$ins->execute()) {
    error_log("Error al guardar código de recuperación: " . $ins->error);
    echo json_encode([
        "success" => false, 
        "message" => "No se pudo procesar la solicitud en este momento. Intenta de nuevo."
    ]);
    $ins->close();
    $conn->close();
    exit;
}
$ins->close();

// 7. Enviar código por correo electrónico registrado
$correoEnviado = enviarCodigoRecuperacion($correo, $nombreUsuario, $codigoPlano);

echo json_encode([
    "success" => true, 
    "message" => "Se ha generado un código de verificación de 6 dígitos y se envió a tu correo. Revisa tu bandeja de entrada o spam.",
    "minutos_expiracion" => $minutosValidez
]);

$conn->close();
?>
