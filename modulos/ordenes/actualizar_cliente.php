<?php
header('Content-Type: application/json');
session_start();
require_once 'includes/verificar_token.php';
if (!isset($_SESSION['rol'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}
verificarAcceso();
include 'includes/conexion.php';

if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['nombre']) || !isset($_POST['dni']) || !isset($_POST['telefono'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$id = (int)$_POST['id'];
$nombre = trim($_POST['nombre']);
$dni = trim($_POST['dni']);
$telefono = trim($_POST['telefono']);
$opinion = trim($_POST['opinion'] ?? '');

if (empty($nombre) || empty($dni) || empty($telefono)) {
    echo json_encode(['success' => false, 'error' => 'Campos vacíos']);
    exit;
}

$stmt = $conn->prepare("UPDATE clientes SET nombre = ?, dni = ?, telefono = ?, opinion = ? WHERE id = ?");
$stmt->bind_param("ssssi", $nombre, $dni, $telefono, $opinion, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
