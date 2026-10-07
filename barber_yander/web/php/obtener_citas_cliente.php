<?php
// =========================================================
// OBTENER CITAS DEL CLIENTE — BARBERÍA YANDER
// Aislamiento por Sesión (solo ve sus propias citas) y Soporte JSON
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('cliente', true);
aplicarCabecerasNoCache();

$id_usuario = (int)$_SESSION['id_usuario'];

$sql = "
    SELECT 
        c.id, 
        s.nombre AS servicio, 
        s.precio,
        c.fecha_cita, 
        c.hora_cita, 
        c.estado
    FROM citas c
    INNER JOIN servicios s ON c.id_servicio = s.id
    WHERE c.id_usuario = ?
    ORDER BY c.fecha_cita DESC, c.hora_cita ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$citas = [];
while ($row = $result->fetch_assoc()) {
    $citas[] = [
        "id"         => (int)$row['id'],
        "servicio"   => $row['servicio'],
        "precio"     => (float)$row['precio'],
        "fecha_cita" => $row['fecha_cita'],
        "hora_cita"  => substr($row['hora_cita'], 0, 5),
        "hora_raw"   => $row['hora_cita'],
        "estado"     => $row['estado']
    ];
}
$stmt->close();

$hashDatos = md5(json_encode($citas));

// Si se solicita explícitamente en formato HTML
if (isset($_GET['formato']) && $_GET['formato'] === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    if (empty($citas)) {
        echo "<div class='empty-state'><span>📅</span><p>Aún no tienes citas agendadas.</p><small>Selecciona un servicio arriba y agenda tu horario favorito.</small></div>";
    } else {
        echo "<table class='admin-table'>";
        echo "<thead><tr><th>Servicio</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>";
        foreach ($citas as $c) {
            $estadoClass = "badge-" . htmlspecialchars($c['estado']);
            $horaFmt = date("g:i A", strtotime($c['hora_raw']));
            echo "<tr>";
            echo "<td data-label='Servicio'><strong>" . htmlspecialchars($c['servicio']) . "</strong></td>";
            echo "<td data-label='Fecha'>📅 " . htmlspecialchars($c['fecha_cita']) . "</td>";
            echo "<td data-label='Hora'>⏰ {$horaFmt}</td>";
            echo "<td data-label='Estado'><span class='status-badge {$estadoClass}'>" . ucfirst($c['estado']) . "</span></td>";
            echo "<td data-label='Acciones'>";
            if ($c['estado'] !== 'cancelada' && $c['estado'] !== 'completada') {
                echo "<button class='btn-cancelar' data-id='{$c['id']}'>Cancelar Cita</button>";
            } else {
                echo "<span class='sin-accion'>-</span>";
            }
            echo "</td></tr>";
        }
        echo "</tbody></table>";
    }
    $conn->close();
    exit;
}

// Formato JSON por defecto
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "success" => true,
    "hash"    => $hashDatos,
    "total"   => count($citas),
    "citas"   => $citas
]);

$conn->close();
?>
