<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = $_SERVER['HTTP_HOST'] ?? '';
$partes = explode('.', $host);
$subdominio = '';
if (count($partes) >= 2) {
    $p = $partes[0];
    if ($p !== 'www' && $p !== 'admin') $subdominio = $p;
}

$nombre = 'Punto de Venta';
if (!empty($subdominio)) {
    $configPath = __DIR__ . '/../includes/conexion_central.php';
    if (file_exists($configPath)) {
        include $configPath;
        if (isset($conn_central)) {
            $s = $conn_central->real_escape_string($subdominio);
            $r = $conn_central->query("SELECT nombre FROM talleres WHERE subdominio = '$s' AND activo = 1 LIMIT 1");
            if ($row = $r->fetch_assoc()) $nombre = 'POS - ' . $row['nombre'];
            $conn_central->close();
        }
    }
}

$iconV = filemtime(__DIR__ . '/../ordenes/logocel.png');
echo json_encode([
    'name' => $nombre,
    'short_name' => 'POS',
    'description' => 'Punto de venta',
    'start_url' => 'index.php',
    'display' => 'standalone',
    'background_color' => '#001845',
    'theme_color' => '#001845',
    'icons' => [
        ['src' => '../ordenes/icon.php?size=192&v=' . $iconV, 'sizes' => '192x192', 'type' => 'image/png'],
        ['src' => '../ordenes/icon.php?size=512&v=' . $iconV, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
