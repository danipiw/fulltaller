<?php

session_start();
require_once 'includes/verificar_token.php';
if (!isset($_SESSION['rol'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}
verificarAcceso();
include 'includes/conexion.php';

$nombre = $_POST['nombre'];
$dni = $_POST['dni'];
$telefono = $_POST['telefono'];
$opinion = trim($_POST['opinion'] ?? '');

$stmt = $conn->prepare("
    INSERT INTO clientes (nombre, dni, telefono, opinion) 
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("ssss", $nombre, $dni, $telefono, $opinion);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'id' => $id,
        'nombre' => $nombre,
        'dni' => $dni,
        'telefono' => $telefono
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

?>