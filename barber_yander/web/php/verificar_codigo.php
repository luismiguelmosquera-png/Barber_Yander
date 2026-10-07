<?php
// =========================================================
// VERIFICAR CÓDIGO Y CAMBIAR CONTRASEÑA — BARBERÍA YANDER
// Verificación Segura de 6 Dígitos, Control de Intentos y Expiración
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$correo           = strtolower(trim($jsonData['correo'] ?? $jsonData['usuario'] ?? $_POST['correo'] ?? $_POST['usuario'] ?? ''));
$codigo           = trim($jsonData['codigo'] ?? $_POST['codigo'] ?? '');
$nuevaContrasena  = trim($jsonData['nuevaContrasena'] ?? $jsonData['password'] ?? $_POST['nuevaContrasena'] ?? $_POST['password'] ?? '');

// 1. Validar campos obligatorios
if (empty($correo) || empty($codigo) || empty($nuevaContrasena)) {
    echo json_encode([
        "success" => false, 
        "message" => "Todos los campos son obligatorios (correo, código de 6 dígitos y nueva contraseña)."
    ]);
    exit;
}

// 2. Validar formato del código (exactamente 6 dígitos numéricos)
if (!preg_match("/^\d{6}$/", $codigo)) {
    echo json_encode([
        "success" => false, 
        "message" => "El código de verificación debe contener exactamente 6 dígitos numéricos."
    ]);
    exit;
}

// 3. Validar longitud de la nueva contraseña
if (strlen($nuevaContrasena) < 4) {
    echo json_encode([
        "success" => false, 
        "message" => "La nueva contraseña debe tener al menos 4 caracteres."
    ]);
    exit;
}

// 4. Buscar usuario asociado
$stmtUser = $conn->prepare("SELECT id, nombre, estado FROM usuarios WHERE LOWER(correo) = LOWER(?) LIMIT 1");
$stmtUser->bind_param("s", $correo);
$stmtUser->execute();
$resUser = $stmtUser->get_result();

if ($resUser->num_rows === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Usuario no encontrado."
    ]);
    $stmtUser->close();
    $conn->close();
    exit;
}

$userRow = $resUser->fetch_assoc();
$idUsuario = (int)$userRow['id'];
$stmtUser->close();

if ($userRow['estado'] === 'bloqueado') {
    echo json_encode([
        "success" => false, 
        "message" => "Esta cuenta está temporalmente suspendida."
    ]);
    $conn->close();
    exit;
}

// 5. Consultar el código de recuperación activo más reciente
$stmtCod = $conn->prepare("
    SELECT id, codigo_hash, intentos, usado, fecha_expiracion 
    FROM password_reset_codes 
    WHERE usuario_id = ? AND usado = 0 
    ORDER BY id DESC 
    LIMIT 1
");
$stmtCod->bind_param("i", $idUsuario);
$stmtCod->execute();
$resCod = $stmtCod->get_result();

if ($resCod->num_rows === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "No tienes solicitudes activas de recuperación. Por favor solicita un nuevo código."
    ]);
    $stmtCod->close();
    $conn->close();
    exit;
}

$codRow = $resCod->fetch_assoc();
$idCodigo = (int)$codRow['id'];
$intentosActuales = (int)$codRow['intentos'];
$maxIntentos = defined('RESET_CODE_MAX_ATTEMPTS') ? RESET_CODE_MAX_ATTEMPTS : 5;
$stmtCod->close();

// 6. Verificar si el código ha expirado (10 minutos)
$ahoraTS = time();
$expiraTS = strtotime($codRow['fecha_expiracion']);

if ($ahoraTS > $expiraTS) {
    // Marcar como usado/invalidado para que no pueda usarse
    $updExp = $conn->prepare("UPDATE password_reset_codes SET usado = 1 WHERE id = ?");
    $updExp->bind_param("i", $idCodigo);
    $updExp->execute();
    $updExp->close();

    echo json_encode([
        "success" => false, 
        "message" => "El código de verificación ha vencido (validez de 10 minutos). Solicita uno nuevo."
    ]);
    $conn->close();
    exit;
}

// 7. Verificar límite de intentos fallidos
if ($intentosActuales >= $maxIntentos) {
    $updMax = $conn->prepare("UPDATE password_reset_codes SET usado = 1 WHERE id = ?");
    $updMax->bind_param("i", $idCodigo);
    $updMax->execute();
    $updMax->close();

    echo json_encode([
        "success" => false, 
        "message" => "Has superado el límite de intentos permitidos ($maxIntentos). Por seguridad, este código ha sido invalidado. Solicita un nuevo código."
    ]);
    $conn->close();
    exit;
}

// 8. Validar el código de 6 dígitos comparando con el hash seguro
if (!password_verify($codigo, $codRow['codigo_hash'])) {
    $nuevosIntentos = $intentosActuales + 1;
    $intentosRestantes = max(0, $maxIntentos - $nuevosIntentos);

    $updInt = $conn->prepare("UPDATE password_reset_codes SET intentos = ? WHERE id = ?");
    $updInt->bind_param("ii", $nuevosIntentos, $idCodigo);
    $updInt->execute();
    $updInt->close();

    if ($intentosRestantes === 0) {
        $updUsado = $conn->prepare("UPDATE password_reset_codes SET usado = 1 WHERE id = ?");
        $updUsado->bind_param("i", $idCodigo);
        $updUsado->execute();
        $updUsado->close();

        echo json_encode([
            "success" => false, 
            "message" => "Código incorrecto. Has alcanzado el número máximo de intentos. Solicita un nuevo código."
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Código de verificación incorrecto. Te quedan {$intentosRestantes} intento(s)."
        ]);
    }

    $conn->close();
    exit;
}

// 9. Si el código es correcto: Generar hash de la nueva contraseña con Bcrypt
$hashNuevaContrasena = password_hash($nuevaContrasena, PASSWORD_BCRYPT);

// 10. Actualizar la contraseña en la tabla usuarios
$updPass = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
$updPass->bind_param("si", $hashNuevaContrasena, $idUsuario);

if (!$updPass->execute()) {
    error_log("Error al actualizar contraseña de usuario: " . $updPass->error);
    echo json_encode([
        "success" => false, 
        "message" => "Ocurrió un error al actualizar la contraseña. Intenta nuevamente."
    ]);
    $updPass->close();
    $conn->close();
    exit;
}
$updPass->close();

// 11. Invalidar inmediatamente el código de verificación para que no pueda ser reutilizado (requisito 9)
$updFinal = $conn->prepare("UPDATE password_reset_codes SET usado = 1 WHERE id = ?");
$updFinal->bind_param("i", $idCodigo);
$updFinal->execute();
$updFinal->close();

// 12. Devolver respuesta de éxito
echo json_encode([
    "success" => true, 
    "message" => "✅ ¡Contraseña restablecida correctamente! Ya puedes iniciar sesión con tu nueva contraseña."
]);

$conn->close();
?>
