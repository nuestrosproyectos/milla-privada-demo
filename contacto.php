<?php
// Formulario de contacto de Milla Privada -> reservas@millaprivada.com
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }
$destino = 'reservas@millaprivada.com';
$c = function ($k, $max = 200) { return trim(mb_substr(str_replace(["\r", "\n"], ' ', (string)($_POST[$k] ?? '')), 0, $max)); };
if ($c('web') !== '') { echo '{"ok":true}'; exit; }            // trampa para robots
$nombre = $c('nombre', 80); $tel = $c('telefono', 30); $email = $c('email', 120);
$ciudad = $c('ciudad', 30); $origen = $c('origen', 200);
$msg = trim(mb_substr((string)($_POST['mensaje'] ?? ''), 0, 3000));
if ($nombre === '' || ($tel === '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || empty($_POST['privacidad'])) {
  http_response_code(422); echo '{"ok":false,"error":"faltan_datos"}'; exit;
}
$asunto = '=?UTF-8?B?' . base64_encode("Nueva solicitud web · $ciudad · $nombre") . '?=';
$cuerpo = "Nueva solicitud desde millaprivada.com\n\nNombre: $nombre\nTeléfono: $tel\nEmail: $email\nCiudad: $ciudad\nPágina: $origen\n\nMensaje:\n$msg\n\nFecha: " . date('d/m/Y H:i') . "\nAceptó la política de privacidad: sí\n";
$cab = "From: Milla Privada web <reservas@millaprivada.com>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
if (filter_var($email, FILTER_VALIDATE_EMAIL)) { $cab .= "Reply-To: $email\r\n"; }
$ok = @mail($destino, $asunto, $cuerpo, $cab, '-freservas@millaprivada.com');
echo json_encode(['ok' => (bool)$ok]);
