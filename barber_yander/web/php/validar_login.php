<?php
// =========================================================
// VALIDAR INICIO DE SESIÓN — BARBERÍA YANDER
// Autenticación por Correo + Contraseña, Detección Automática de Rol en BD,
// Regeneración de ID de Sesión y Hashing Seguro
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

// Leer datos enviados (soporta JSON y POST tradicional)
$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$correo = trim($jsonData['correo'] ?? $_POST['correo'] ?? $_POST['usuario'] ?? '');
$contrasena = trim($jsonData['contrasena'] ?? $_POST['contrasena'] ?? $_POST['password'] ?? '');

if (empty($correo) || empty($contrasena)) {
    echo json_encode([
        "success" => false, 
        "message" => "Por favor ingresa tu correo y contraseña."
    ]);
    exit;
}

// 1. Consultar usuario por correo únicamente (EL ROL LO DETERMINA LA BASE DE DATOS)
$sql = "SELECT id, nombre, correo, telefono, tipo_doc, num_doc, contrasena, rol, estado, bloqueado_hasta, motivo_bloqueo FROM usuarios WHERE LOWER(correo) = LOWER(?) LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Mensaje genérico para no revelar si el correo existe
    echo json_encode([
        "success" => false, 
        "message" => "Credenciales incorrectas. Verifica tu correo y contraseña."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$fila = $result->fetch_assoc();
$stmt->close();

// 2. Verificar contraseña con Bcrypt (password_verify) o migración automática desde texto plano
$passValida = false;
$necesitaRehash = false;

if (password_verify($contrasena, $fila['contrasena'])) {
    $passValida = true;
    if (password_needs_rehash($fila['contrasena'], PASSWORD_BCRYPT)) {
        $necesitaRehash = true;
    }
} elseif ($fila['contrasena'] === $contrasena) {
    // Si coincidió en texto plano legado (ej. migración inicial), validar y migrar inmediatamente a hash
    $passValida = true;
    $necesitaRehash = true;
}

if (!$passValida) {
    echo json_encode([
        "success" => false, 
        "message" => "Credenciales incorrectas. Verifica tu correo y contraseña."
    ]);
    $conn->close();
    exit;
}

// Si correspondía actualizar el hash en la BD
if ($necesitaRehash) {
    $nuevoHash = password_hash($contrasena, PASSWORD_BCRYPT);
    $updHash = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
    $updHash->bind_param("si", $nuevoHash, $fila['id']);
    $updHash->execute();
    $updHash->close();
}

// 3. Verificar estado de bloqueo del usuario
if ($fila['estado'] === 'bloqueado') {
    $ahora = time();
    $bloqueadoHastaTS = !empty($fila['bloqueado_hasta']) ? strtotime($fila['bloqueado_hasta']) : null;

    // Desbloqueo automático si el período de suspensión ya expiró
    if ($bloqueadoHastaTS && $ahora >= $bloqueadoHastaTS) {
        $unblock = $conn->prepare("UPDATE usuarios SET estado = 'activo', bloqueado_desde = NULL, bloqueado_hasta = NULL, motivo_bloqueo = NULL WHERE id = ?");
        $unblock->bind_param("i", $fila['id']);
        $unblock->execute();
        $unblock->close();
        $fila['estado'] = 'activo';
    } else {
        $fechaDesbloqueo = $bloqueadoHastaTS ? date("d/m/Y H:i", $bloqueadoHastaTS) : "indefinido";
        $motivo = !empty($fila['motivo_bloqueo']) ? " Motivo: " . $fila['motivo_bloqueo'] : "";
        echo json_encode([
            "success" => false, 
            "message" => "Tu cuenta se encuentra suspendida temporalmente hasta el {$fechaDesbloqueo}.{$motivo}"
        ]);
        $conn->close();
        exit;
    }
}

// 4. Iniciar sesión y regenerar Session ID para prevenir Session Fixation (requisito 21)
iniciarSesionSegura();
session_regenerate_id(true);

$_SESSION['id_usuario']   = (int)$fila['id'];
$_SESSION['usuario']      = $fila['nombre'];
$_SESSION['correo']       = $fila['correo'];
$_SESSION['telefono']     = $fila['telefono'] ?? '';
$_SESSION['tipo_doc']     = $fila['tipo_doc'] ?? '';
$_SESSION['num_doc']      = $fila['num_doc'] ?? '';
$_SESSION['rol']          = $fila['rol']; // Determinado exclusivamente por MySQL
$_SESSION['ultimo_acceso'] = time();

// Redirección segura según el rol autenticado en la base de datos
$redirect = ($fila['rol'] === 'admin') ? 'inicio_admin.php' : 'inicio_cliente.php';

echo json_encode([
    "success"  => true, 
    "redirect" => $redirect,
    "usuario"  => [
        "id"       => (int)$fila['id'],
        "nombre"   => $fila['nombre'],
        "correo"   => $fila['correo'],
        "telefono" => $fila['telefono'] ?? '',
        "tipo_doc" => $fila['tipo_doc'] ?? '',
        "num_doc"  => $fila['num_doc'] ?? '',
        "rol"      => $fila['rol']
    ]
]);

$conn->close();
?>
