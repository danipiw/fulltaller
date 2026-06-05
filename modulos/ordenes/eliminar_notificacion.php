<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['recepcion', 'tecnico'])) {
    echo json_encode(['success' => false, 'error' => 'Sin sesión']);
    exit;
}

include 'includes/conexion.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$rol = $_SESSION['rol'];
$stmt = $conn->prepare("DELETE FROM notificaciones WHERE id = ? AND para_rol = ?");
$stmt->bind_param("is", $id, $rol);
$stmt->execute();

echo json_encode(['success' => true]);
