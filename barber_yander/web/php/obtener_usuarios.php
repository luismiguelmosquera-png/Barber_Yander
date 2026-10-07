<?php
// =========================================================
// OBTENER USUARIOS (ADMIN) — BARBERÍA YANDER
// Protección de Sesión y Soporte Dual (JSON y HTML)
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();

$query = "SELECT id, nombre, correo, telefono, rol, estado, bloqueado_hasta FROM usuarios ORDER BY id ASC";
$result = $conn->query($query);

$usuarios = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = [
            "id"             => (int)$row['id'],
            "nombre"         => $row['nombre'],
            "correo"         => $row['correo'],
            "telefono"       => $row['telefono'] ?? '',
            "rol"            => $row['rol'],
            "estado"         => $row['estado'] ?? 'activo',
            "bloqueado_hasta"=> $row['bloqueado_hasta']
        ];
    }
}

// Si se solicita explícitamente en formato HTML
if (isset($_GET['formato']) && $_GET['formato'] === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<table class='admin-table'>";
    echo "<thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>";

    foreach ($usuarios as $row) {
        $estado = $row['estado'];
        $bloqueadoHasta = $row['bloqueado_hasta'];
        
        $badgeEstado = "<span class='status-badge badge-confirmada'>Activo</span>";
        if ($estado === 'bloqueado') {
            $fechaTxt = $bloqueadoHasta ? date("d/m/Y H:i", strtotime($bloqueadoHasta)) : 'Indefinido';
            $badgeEstado = "<span class='status-badge badge-cancelada' title='Bloqueado hasta {$fechaTxt}'>Bloqueado ({$fechaTxt})</span>";
        }

        $rolBadge = ($row['rol'] === 'admin') 
            ? "<span class='status-badge badge-confirmada'>Admin</span>" 
            : "<span class='status-badge badge-completada'>Cliente</span>";

        echo "<tr>";
        echo "<td data-label='ID'>#{$row['id']}</td>";
        echo "<td data-label='Nombre'><strong>" . htmlspecialchars($row['nombre']) . "</strong></td>";
        echo "<td data-label='Correo'>" . htmlspecialchars($row['correo']) . "</td>";
        echo "<td data-label='Rol'>{$rolBadge}</td>";
        echo "<td data-label='Estado'>{$badgeEstado}</td>";
        echo "<td data-label='Acciones'>";
        
        if ($row['rol'] !== 'admin') {
            $nombreSafe = htmlspecialchars($row['nombre'], ENT_QUOTES);
            $correoSafe = htmlspecialchars($row['correo'], ENT_QUOTES);
            if ($estado === 'bloqueado') {
                echo "<button class='btn-desbloquear-usuario' data-id='{$row['id']}' data-nombre='{$nombreSafe}' data-correo='{$correoSafe}'>🔓 Desbloquear</button> ";
            } else {
                echo "<button class='btn-bloquear-usuario' data-id='{$row['id']}' data-nombre='{$nombreSafe}' data-correo='{$correoSafe}'>🔒 Bloquear</button> ";
            }
            echo "<button class='btn-cancelar-cita' data-id='{$row['id']}' data-nombre='{$nombreSafe}' data-correo='{$correoSafe}'>🗑️ Eliminar</button>";
        } else {
            echo "<span class='sin-accion'>Protegido</span>";
        }

        echo "</td></tr>";
    }

    echo "</tbody></table>";
    $conn->close();
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "success"  => true,
    "total"    => count($usuarios),
    "usuarios" => $usuarios
]);

$conn->close();
?>
