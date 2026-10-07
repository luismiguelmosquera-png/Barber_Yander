<?php
// =========================================================
// RECUPERAR CONTRASEÑA (COMPATIBILIDAD) — BARBERÍA YANDER
// Redirige o procesa según el nuevo flujo de código de 6 dígitos
// =========================================================

require_once __DIR__ . '/seguridad.php';

aplicarCabecerasNoCache();
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true) ?? $_POST;

if (!empty($data['codigo'])) {
    // Si incluye código, transferir a verificar_codigo.php
    require __DIR__ . '/verificar_codigo.php';
} else {
    // Si solo incluye correo, transferir a solicitar_codigo.php
    require __DIR__ . '/solicitar_codigo.php';
}
?>
