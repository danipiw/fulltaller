<?php
include 'includes/verificar_sesion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['ok' => false, 'error' => 'Solicitud inválida']);
    exit;
}

$id = (int)$_POST['id'];
$q = $conn->query("SELECT filename FROM fotos WHERE id = $id");
$foto = $q->fetch_assoc();
if (!$foto) {
    echo json_encode(['ok' => false, 'error' => 'Foto no encontrada']);
    exit;
}

$file = __DIR__ . '/uploads/' . $foto['filename'];
if (file_exists($file)) unlink($file);

$conn->query("DELETE FROM fotos WHERE id = $id");
echo json_encode(['ok' => true]);
