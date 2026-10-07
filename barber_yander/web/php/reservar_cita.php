<?php
// =========================================================
// RESERVAR CITA — BARBERÍA YANDER
// Validación de Sesión, Intervalo de 40 Minutos y Respuesta JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

// Verificar sesión activa (como API)
verificarSesion('cliente', true);

aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$id_usuario = (int)$_SESSION['id_usuario'];

// 1. Verificar que el usuario exista y no esté bloqueado
$checkUser = $conn->prepare("SELECT id, estado, bloqueado_hasta FROM usuarios WHERE id = ? LIMIT 1");
$checkUser->bind_param("i", $id_usuario);
$checkUser->execute();
$resUser = $checkUser->get_result();

if ($resUser->num_rows === 0) {
    cerrarSesionCompleta();
    echo json_encode([
        "success" => false, 
        "sesion_expirada" => true,
        "message" => "Tu cuenta ya no está disponible. Inicia sesión nuevamente.",
        "redirect" => "login.html"
    ]);
    $checkUser->close();
    $conn->close();
    exit;
}

$u = $resUser->fetch_assoc();
$checkUser->close();

if ($u['estado'] === 'bloqueado') {
    $ahora = time();
    $bloqTS = !empty($u['bloqueado_hasta']) ? strtotime($u['bloqueado_hasta']) : null;
    if ($bloqTS && $ahora < $bloqTS) {
        $fechaTxt = date("d/m/Y H:i", $bloqTS);
        echo json_encode([
            "success" => false, 
            "message" => "Tu cuenta está temporalmente suspendida hasta el {$fechaTxt}. No puedes agendar citas."
        ]);
        $conn->close();
        exit;
    }
}

// 2. Obtener datos del formulario (soporta POST y JSON)
$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$telefono = trim($jsonData['telefono'] ?? $_POST['telefono'] ?? '');
$servicio = trim($jsonData['servicio'] ?? $_POST['servicio'] ?? '');
$fecha    = trim($jsonData['fecha'] ?? $_POST['fecha'] ?? '');
$hora     = trim($jsonData['hora'] ?? $_POST['hora'] ?? '');

// Si la hora viene en formato HH:MM, agregar :00
if (strlen($hora) === 5) {
    $hora .= ":00";
}

// 3. Validar campos obligatorios
if (!$telefono || !$servicio || !$fecha || !$hora) {
    echo json_encode([
        "success" => false, 
        "message" => "Todos los campos de la reserva son obligatorios (teléfono, servicio, fecha y hora)."
    ]);
    $conn->close();
    exit;
}

// 4. Validar formato de teléfono (10 dígitos)
if (!preg_match("/^\d{10}$/", $telefono)) {
    echo json_encode([
        "success" => false, 
        "message" => "El teléfono debe contener exactamente 10 dígitos numéricos."
    ]);
    $conn->close();
    exit;
}

// 5. Verificar que el servicio exista en MySQL
$stmtServ = $conn->prepare("SELECT id, nombre, duracion_minutos FROM servicios WHERE nombre = ? AND activo = 1 LIMIT 1");
$stmtServ->bind_param("s", $servicio);
$stmtServ->execute();
$resServ = $stmtServ->get_result();

if ($resServ->num_rows === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "El servicio seleccionado no es válido o ya no está disponible."
    ]);
    $stmtServ->close();
    $conn->close();
    exit;
}

$id_servicio = (int)$resServ->fetch_assoc()['id'];
$stmtServ->close();

// 6. Validar que la cita no sea en el pasado
$timestampCita = strtotime("$fecha $hora");
if ($timestampCita === false || $timestampCita < (time() - 60)) {
    echo json_encode([
        "success" => false, 
        "message" => "No puedes agendar una cita en una fecha u hora que ya ha transcurrido."
    ]);
    $conn->close();
    exit;
}

// 7. Validar intervalo de 40 minutos (1 solo barbero experto)
$fechaHoraSeleccionada = "$fecha $hora";
$inicioIntervalo = date("Y-m-d H:i:s", strtotime("$fechaHoraSeleccionada -40 minutes"));
$finIntervalo    = date("Y-m-d H:i:s", strtotime("$fechaHoraSeleccionada +40 minutes"));

$verificar = $conn->prepare("
    SELECT hora_cita 
    FROM citas 
    WHERE estado IN ('confirmada', 'pendiente')
      AND fecha_cita = ?
      AND CONCAT(fecha_cita, ' ', hora_cita) > ?
      AND CONCAT(fecha_cita, ' ', hora_cita) < ?
    ORDER BY hora_cita ASC
    LIMIT 1
");
$verificar->bind_param("sss", $fecha, $inicioIntervalo, $finIntervalo);
$verificar->execute();
$resConflicto = $verificar->get_result();

if ($resConflicto->num_rows > 0) {
    $rowConflicto = $resConflicto->fetch_assoc();
    $horaOcupada = substr($rowConflicto['hora_cita'], 0, 5);
    $verificar->close();
    $conn->close();
    echo json_encode([
        "success" => false, 
        "message" => "Ya existe una cita agendada a las {$horaOcupada}. Por favor selecciona otro horario con al menos 40 minutos de diferencia."
    ]);
    exit;
}
$verificar->close();

// 8. Insertar la cita en MySQL con estado inicial 'pendiente'
$stmtInsert = $conn->prepare("
    INSERT INTO citas (id_usuario, id_servicio, fecha_cita, hora_cita, estado) 
    VALUES (?, ?, ?, ?, 'pendiente')
");
$stmtInsert->bind_param("iiss", $id_usuario, $id_servicio, $fecha, $hora);

if ($stmtInsert->execute()) {
    $nuevoIdCita = $conn->insert_id;
    echo json_encode([
        "success" => true, 
        "id_cita" => $nuevoIdCita,
        "message" => "✅ ¡Cita enviada con éxito! Queda registrada y pendiente de confirmación."
    ]);
} else {
    error_log("Error al insertar cita: " . $stmtInsert->error);
    echo json_encode([
        "success" => false, 
        "message" => "Ocurrió un error al registrar la cita en el servidor. Por favor intenta de nuevo."
    ]);
}

$stmtInsert->close();
$conn->close();
?>
