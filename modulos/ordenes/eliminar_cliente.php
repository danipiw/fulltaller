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

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$id = (int)$_POST['id'];

// Verificar si tiene órdenes asociadas
$check = $conn->prepare("SELECT COUNT(*) as total FROM ordenes WHERE cliente_id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result()->fetch_assoc();

if ($result['total'] > 0) {
    echo json_encode(['success' => false, 'error' => 'El cliente tiene ' . $result['total'] . ' órdenes asociadas. No se puede eliminar.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
