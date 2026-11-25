<?php
// Contador anónimo de pulsaciones en los botones de WhatsApp de millaprivada.com.
// No guarda IP, cookies ni datos personales: solo cuántas veces se pulsa, por día, página y origen.
//   POST (lo envía la web al pulsar):  p=/madrid/lamborghini/  a=1 (viene de Google Ads) / 0
//   Ver resultados:                    /contador-wa.php?ver=QNk1nV8AwccEtzZZ
header('Cache-Control: no-store');
$CLAVE = 'QNk1nV8AwccEtzZZ';
$dir = is_writable(dirname(__DIR__)) ? dirname(__DIR__) : __DIR__;
$fich = $dir . '/.contador-wa-millaprivada.json';

if (($_GET['ver'] ?? '') === $CLAVE) {
    header('Content-Type: application/json; charset=utf-8');
    $d = @json_decode((string)@file_get_contents($fich), true) ?: [];
    $tot = ['total' => 0, 'anuncio' => 0, 'web' => 0];
    foreach ($d as $dia => $o) foreach ($o as $origen => $pags) foreach ($pags as $n) { $tot['total'] += $n; $tot[$origen] += $n; }
    echo json_encode(['resumen' => $tot, 'por_dia' => $d], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(404); exit; }

// Límite por conexión (sin guardar la IP en claro): 30 pulsaciones cada 10 minutos
$lim = sys_get_temp_dir() . '/mp_wa_' . md5(($_SERVER['REMOTE_ADDR'] ?? 'x') . $CLAVE);
$ahora = time();
$marcas = array_filter(@json_decode((string)@file_get_contents($lim), true) ?: [], function ($t) use ($ahora) { return $t > $ahora - 600; });
if (count($marcas) >= 30) { http_response_code(204); exit; }
$marcas[] = $ahora; @file_put_contents($lim, json_encode(array_values($marcas)), LOCK_EX);

$p = preg_replace('~[^a-z0-9/\-]~', '', strtolower(substr((string)($_POST['p'] ?? '/'), 0, 80))) ?: '/';
$origen = (($_POST['a'] ?? '0') === '1') ? 'anuncio' : 'web';
date_default_timezone_set('Europe/Madrid');
$dia = date('Y-m-d');

$h = @fopen($fich, 'c+');
if ($h && flock($h, LOCK_EX)) {
    $d = @json_decode((string)stream_get_contents($h), true) ?: [];
    $d[$dia][$origen][$p] = ($d[$dia][$origen][$p] ?? 0) + 1;
    ftruncate($h, 0); rewind($h);
    fwrite($h, json_encode($d, JSON_UNESCAPED_SLASHES));
    fflush($h); flock($h, LOCK_UN);
}
if ($h) fclose($h);
http_response_code(204);
