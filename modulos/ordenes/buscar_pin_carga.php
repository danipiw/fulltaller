<?php
include 'conexion.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT id, tipo, precio FROM pin_carga ORDER BY id");
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;
echo json_encode($data);
