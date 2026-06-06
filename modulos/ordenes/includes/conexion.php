<?php

$MODO_LOCAL = false;

if (file_exists(__DIR__ . '/config_local.php')) {
    $config = include __DIR__ . '/config_local.php';
    if (isset($config['modo_local']) && $config['modo_local']) {
        $MODO_LOCAL = true;
    }
}

$host_actual = $_SERVER['HTTP_HOST'] ?? '';

if ($MODO_LOCAL) {
    $GLOBALS['taller_subdominio'] = $config['subdominio'] ?? 'demo';
    $GLOBALS['taller_nombre'] = $config['taller_nombre'] ?? 'Taller Local';
    $GLOBALS['taller_id'] = $config['taller_id'] ?? 0;
    $conn = new mysqli(
        $config['db_host'] ?? '127.0.0.1',
        $config['db_user'] ?? 'root',
        $config['db_pass'] ?? '',
        $config['db_name'] ?? 'taller_demo'
    );
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    return;
}

$subdominio = '';
$partes = explode('.', $host_actual);

if (count($partes) >= 3) {
    $primer_parte = $partes[0];
    if ($primer_parte !== 'www') {
        $subdominio = $primer_parte;
    }
} elseif (count($partes) === 2 && in_array($partes[0], ['localhost', '127-0-0-1'])) {
    $subdominio = $partes[0];
}

if (empty($subdominio)) {
    die("Acceso denegado. Use un subdominio válido (ej: sutaller.sudominio.com)");
}

require_once __DIR__ . '/conexion_central.php';

$subdominio_escaped = $conn_central->real_escape_string($subdominio);
$result = $conn_central->query("SELECT id, nombre, db_host, db_user, db_pass, db_name, suscripcion_activa, fecha_vencimiento, activo FROM talleres WHERE subdominio = '$subdominio_escaped' LIMIT 1");
$taller = $result->fetch_assoc();

if (!$taller) {
    die("Taller no encontrado para el subdominio: " . htmlspecialchars($subdominio));
}

if (!$taller['activo']) {
    die("Este taller se encuentra desactivado. Contacte al administrador.");
}

if (!$taller['suscripcion_activa'] || strtotime($taller['fecha_vencimiento']) < strtotime(date('Y-m-d'))) {
    header('Location: ../../../suscripcion_vencida.php');
    exit;
}

$GLOBALS['taller_id'] = (int)$taller['id'];
$GLOBALS['taller_nombre'] = $taller['nombre'];
$GLOBALS['taller_subdominio'] = $subdominio;

$conn = new mysqli($taller['db_host'], $taller['db_user'], $taller['db_pass'], $taller['db_name']);

if ($conn->connect_error) {
    die("Error de conexión a la base del taller: " . $conn->connect_error);
}

$conn_central->close();
