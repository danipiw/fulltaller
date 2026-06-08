<?php
header('Content-Type: application/json');
session_start();
require_once 'db.php';
require_once 'includes/verificar_token.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
verificarAcceso();

$db = getDB();

$nombre = $_POST['nombre'];
$dni = $_POST['dni'];
$telefono = $_POST['telefono'];
$opinion = trim($_POST['opinion'] ?? '');

$stmt = $db->prepare("INSERT INTO clientes (nombre, dni, telefono, opinion) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $dni, $telefono, $opinion);

if ($stmt->execute()) {
    $id = $db->insert_id;
    echo json_encode([
        'success' => true,
        'id' => $id,
        'nombre' => $nombre,
        'dni' => $dni,
        'telefono' => $telefono
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
