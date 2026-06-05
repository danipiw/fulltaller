<?php
error_reporting(0);
session_start();
header('Content-Type: application/json');

include 'includes/conexion.php';

if (!isset($_SESSION['rol'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}
if (!isset($_POST['orden_id']) || !is_numeric($_POST['orden_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$orden_id = (int)$_POST['orden_id'];
$campos = ['imagen','touch','brillo','receiver','camaras','microfono','altavoz','sensor','wifi','botones','pegado','carga'];

foreach ($campos as $c) {
    if (!isset($_POST[$c]) || ($_POST[$c] !== '0' && $_POST[$c] !== '1')) {
        echo json_encode(['success' => false, 'error' => 'Falta responder: ' . $c]);
        exit;
    }
}

$values = [];
foreach ($campos as $c) {
    $values[] = (int)$_POST[$c];
}

$cols = implode(',', $campos);
$vals = implode(',', $values);
$rol = $_SESSION['rol'];
$usr = $_SESSION['nombre'] ?? '';

$r1 = $conn->query("INSERT INTO chequeo_final (orden_id, $cols, creado_por, creado_por_usuario) VALUES ($orden_id, $vals, '$rol', '$usr')");
if (!$r1) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar chequeo']);
    exit;
}

$conn->query("INSERT INTO estados_log (orden_id, estado, cambiado_por, cambiado_por_usuario, fecha) VALUES ($orden_id, 'CHEQUEO FINAL', '$rol', '$usr', NOW())");

$all_ok = array_sum($values) === count($values);
$response = ['success' => true, 'all_ok' => $all_ok];

if ($all_ok) {
    $cfg_result = $conn->query("SELECT clave, valor FROM configuracion WHERE clave IN ('taller_nombre')");
    $taller_nom = 'FullTaller';
    if ($cfg_result) {
        while ($cfg_row = $cfg_result->fetch_assoc()) {
            if ($cfg_row['clave'] === 'taller_nombre') $taller_nom = $cfg_row['valor'];
        }
    }
    $ord_result = $conn->query("SELECT o.token, c.telefono FROM ordenes o INNER JOIN clientes c ON o.cliente_id = c.id WHERE o.id = $orden_id");
    $ord_data = $ord_result ? $ord_result->fetch_assoc() : null;
    $response['orden_id'] = $orden_id;
    $response['telefono'] = $ord_data['telefono'] ?? '';
    $response['token'] = $ord_data['token'] ?? '';
    $response['taller_nombre'] = $taller_nom;
}

echo json_encode($response);
