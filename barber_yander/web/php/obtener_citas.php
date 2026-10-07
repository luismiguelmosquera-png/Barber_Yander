<?php
// =========================================================
// OBTENER CITAS (ADMINISTRADOR) — BARBERÍA YANDER
// Verificación de Sesión Admin, Resumen de Contadores y Hash de Sincronización
// =========================================================

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

verificarSesion('admin', true);
aplicarCabecerasNoCache();

$sql = "
    SELECT 
        c.id,
        u.id AS id_usuario,
        u.nombre AS cliente,
        u.correo AS correo_cliente,
        u.telefono AS telefono_cliente,
        s.nombre AS servicio,
        s.precio AS precio_servicio,
        c.fecha_cita,
        c.hora_cita,
        c.estado
    FROM citas c
    INNER JOIN usuarios u ON c.id_usuario = u.id
    INNER JOIN servicios s ON c.id_servicio = s.id
    ORDER BY c.fecha_cita DESC, c.hora_cita ASC
";

$result = $conn->query($sql);
$citas = [];

$totales = [
    "total"       => 0,
    "pendientes"  => 0,
    "confirmadas" => 0,
    "completadas" => 0,
    "canceladas"  => 0
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $estado = $row['estado'];
        $totales['total']++;
        if (isset($totales[$estado])) {
            $totales[$estado]++;
        } elseif ($estado === 'pendiente') {
            $totales['pendientes']++;
        } elseif ($estado === 'confirmada') {
            $totales['confirmadas']++;
        } elseif ($estado === 'completada') {
            $totales['completadas']++;
        }

        $citas[] = [
            "id"          => (int)$row['id'],
            "id_usuario"  => (int)$row['id_usuario'],
            "cliente"     => $row['cliente'],
            "correo"      => $row['correo_cliente'],
            "telefono"    => $row['telefono_cliente'],
            "servicio"    => $row['servicio'],
            "precio"      => (float)$row['precio_servicio'],
            "fecha_cita"  => $row['fecha_cita'],
            "hora_cita"   => substr($row['hora_cita'], 0, 5),
            "hora_raw"    => $row['hora_cita'],
            "estado"      => $estado
        ];
    }
}

// Calcular huella (hash) de los datos para sincronización eficiente sin recargas innecesarias
$hashDatos = md5(json_encode($citas));

// Si se solicita explícitamente HTML por compatibilidad
if (isset($_GET['formato']) && $_GET['formato'] === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    if (empty($citas)) {
        echo "<div class='empty-state'><span>📅</span><p>No hay citas registradas en el sistema.</p></div>";
    } else {
        echo "<table class='admin-table'>";
        echo "<thead><tr><th>ID</th><th>Cliente</th><th>Servicio</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>";
        foreach ($citas as $c) {
            $estadoClass = "badge-" . htmlspecialchars($c['estado']);
            $horaFmt = date("g:i A", strtotime($c['hora_raw']));
            echo "<tr>";
            echo "<td data-label='ID'>#{$c['id']}</td>";
            echo "<td data-label='Cliente'><strong>" . htmlspecialchars($c['cliente']) . "</strong></td>";
            echo "<td data-label='Servicio'>" . htmlspecialchars($c['servicio']) . "</td>";
            echo "<td data-label='Fecha'>📅 {$c['fecha_cita']}</td>";
            echo "<td data-label='Hora'>⏰ {$horaFmt}</td>";
            echo "<td data-label='Estado'><span class='status-badge {$estadoClass}'>" . ucfirst($c['estado']) . "</span></td>";
            echo "<td data-label='Acciones'>";
            if ($c['estado'] === 'pendiente') {
                echo "<button class='btn-confirmar' data-id='{$c['id']}'>Confirmar</button> ";
            }
            if ($c['estado'] === 'confirmada') {
                echo "<button class='btn-completada' data-id='{$c['id']}'>Completar</button> ";
            }
            if ($c['estado'] !== 'cancelada') {
                echo "<button class='btn-cancelar' data-id='{$c['id']}'>Cancelar</button>";
            }
            echo "</td></tr>";
        }
        echo "</tbody></table>";
    }
    $conn->close();
    exit;
}

// Respuesta JSON estándar
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "success"    => true,
    "hash"       => $hashDatos,
    "total"      => count($citas),
    "contadores" => $totales,
    "citas"      => $citas
]);

$conn->close();
?>
