<?php
header('Content-Type: application/json');
include 'conexion.php';

$fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? '';

if (!$fecha) {
    echo json_encode(["success" => false, "message" => "Fecha no proporcionada", "horarios" => []]);
    exit;
}

// Lista de horarios base de la barbería (intervalos de 40 minutos)
$horariosBase = [
    "09:00:00", "09:40:00", "10:20:00", "11:00:00", "11:40:00",
    "14:00:00", "14:40:00", "15:20:00", "16:00:00", "16:40:00", "17:20:00", "18:00:00"
];

// Consultar citas activas para la fecha
$stmt = $conn->prepare("
    SELECT hora_cita 
    FROM citas 
    WHERE fecha_cita = ? AND estado IN ('confirmada', 'pendiente')
");
$stmt->bind_param("s", $fecha);
$stmt->execute();
$res = $stmt->get_result();

$citasOcupadas = [];
while ($row = $res->fetch_assoc()) {
    $citasOcupadas[] = $row['hora_cita'];
}
$stmt->close();

$ahora = time();
$esHoy = ($fecha === date("Y-m-d"));

$horariosDisponibles = [];

foreach ($horariosBase as $h) {
    $tsSlot = strtotime("$fecha $h");

    // Si es hoy y la hora ya pasó, no mostrar
    if ($esHoy && $tsSlot < ($ahora + 300)) { // 5 min de margen
        continue;
    }

    // Verificar si colisiona con alguna cita activa dentro de 40 minutos
    $ocupado = false;
    foreach ($citasOcupadas as $ocupada) {
        $tsOcupada = strtotime("$fecha $ocupada");
        $diferencia = abs($tsSlot - $tsOcupada);
        if ($diferencia < (40 * 60)) { // Menos de 40 minutos
            $ocupado = true;
            break;
        }
    }

    if (!$ocupado) {
        $horaCorta = substr($h, 0, 5);
        $horaFormato = date("g:i A", $tsSlot);
        $horariosDisponibles[] = [
            "hora" => $horaCorta,
            "hora_completa" => $h,
            "formato" => $horaFormato
        ];
    }
}

$conn->close();

echo json_encode([
    "success" => true,
    "fecha" => $fecha,
    "total" => count($horariosDisponibles),
    "horarios" => $horariosDisponibles
]);
?>
