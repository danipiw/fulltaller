<?php

header('Content-Type: application/json');
include 'includes/conexion.php';

if (!isset($_POST['nombre'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No llegó nombre'
    ]);
    exit;
}

$nombre = trim($_POST['nombre']);

if ($nombre == '') {
    echo json_encode([
        'success' => false,
        'error' => 'Nombre vacío'
    ]);
    exit;
}

// Verificar si ya existe
$check = $conn->prepare("SELECT id FROM tipos WHERE nombre = ?");
$check->bind_param("s", $nombre);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'error' => 'El tipo ya existe'
    ]);
    exit;
}

// Insertar
$stmt = $conn->prepare("INSERT INTO tipos (nombre) VALUES (?)");
$stmt->bind_param("s", $nombre);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'nombre' => $nombre
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

exit;
?>