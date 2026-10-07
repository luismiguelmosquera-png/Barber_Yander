<?php
// =========================================================
// VERIFICAR SESIÓN ACTIVA — BARBERÍA YANDER
// Endpoint para comprobar estado de autenticación y rol en el servidor
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

aplicarCabecerasNoCache();
iniciarSesionSegura();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
    echo json_encode([
        "autenticado" => false,
        "usuario" => null
    ]);
    exit;
}

$idUsuario = (int)$_SESSION['id_usuario'];

// Consultar datos actualizados en la base de datos
$stmt = $conn->prepare("SELECT id, nombre, correo, telefono, tipo_doc, num_doc, rol, estado, bloqueado_hasta FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // Usuario ya no existe
    cerrarSesionCompleta();
    echo json_encode([
        "autenticado" => false,
        "usuario" => null,
        "motivo" => "usuario_inexistente"
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$u = $res->fetch_assoc();
$stmt->close();

// Si fue bloqueado, cerrar sesión
if ($u['estado'] === 'bloqueado') {
    $ahora = time();
    $bloqueadoHastaTS = !empty($u['bloqueado_hasta']) ? strtotime($u['bloqueado_hasta']) : null;
    if (!$bloqueadoHastaTS || $ahora < $bloqueadoHastaTS) {
        cerrarSesionCompleta();
        echo json_encode([
            "autenticado" => false,
            "usuario" => null,
            "motivo" => "bloqueado"
        ]);
        $conn->close();
        exit;
    }
}

// Sincronizar datos de sesión
$_SESSION['usuario'] = $u['nombre'];
$_SESSION['correo']  = $u['correo'];
$_SESSION['rol']     = $u['rol'];

echo json_encode([
    "autenticado" => true,
    "usuario" => [
        "id" => (int)$u['id'],
        "nombre" => $u['nombre'],
        "correo" => $u['correo'],
        "telefono" => $u['telefono'] ?? '',
        "tipo_doc" => $u['tipo_doc'] ?? '',
        "num_doc" => $u['num_doc'] ?? '',
        "rol" => $u['rol']
    ]
]);

$conn->close();
?>
