<?php
session_start();
header('Content-Type: application/json');
include 'includes/conexion.php';

// Aceptar rol desde POST o sesión
$rol = null;
if (isset($_POST['rol']) && in_array($_POST['rol'], ['recepcion', 'tecnico'])) {
    $rol = $_POST['rol'];
} elseif (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['recepcion', 'tecnico'])) {
    $rol = $_SESSION['rol'];
}

if (!$rol || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$id = (int)$_POST['id'];

$stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND para_rol = ?");
$stmt->bind_param("is", $id, $rol);
$ok = $stmt->execute();

echo json_encode(['success' => $ok && $stmt->affected_rows > 0]);