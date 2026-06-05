<?php

header('Content-Type: application/json');
include 'includes/conexion.php';

if (!isset($_GET['orden_id']) || !is_numeric($_GET['orden_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID inválido'
    ]);
    exit;
}

$orden_id = (int)$_GET['orden_id'];

$stmt = $conn->prepare("SELECT * FROM notas WHERE orden_id = ? ORDER BY fecha ASC");
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$result = $stmt->get_result();

$notas = [];
while ($row = $result->fetch_assoc()) {
    $notas[] = [
        'id' => $row['id'],
        'autor' => $row['autor'],
        'mensaje' => $row['mensaje'],
        'fecha' => date('d/m/Y H:i', strtotime($row['fecha']))
    ];
}

echo json_encode([
    'success' => true,
    'notas' => $notas
]);

?>