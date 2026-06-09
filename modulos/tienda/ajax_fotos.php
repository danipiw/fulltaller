<?php
require_once __DIR__ . '/includes/conexion.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['imagen_principal' => '', 'fotos' => []]); exit; }

$rp = $conn->query("SELECT tienda_imagen, tienda_descripcion FROM pos_productos WHERE id = $id");
$imagen_principal = ''; $tienda_descripcion = '';
if ($rp && $fp = $rp->fetch_assoc()) {
    if ($fp['tienda_imagen'] && file_exists(__DIR__ . '/uploads/' . $fp['tienda_imagen'])) $imagen_principal = $fp['tienda_imagen'];
    $tienda_descripcion = $fp['tienda_descripcion'] ?? '';
}

$r = $conn->query("SELECT id, filename FROM tienda_fotos WHERE producto_id = $id ORDER BY orden ASC");
$fotos = [];
while ($f = $r->fetch_assoc()) $fotos[] = $f;
echo json_encode(['imagen_principal' => $imagen_principal, 'descripcion' => $tienda_descripcion, 'fotos' => $fotos]);
