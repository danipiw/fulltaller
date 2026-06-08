<?php
session_start();
if (!isset($_SESSION['rol'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }
if (!empty($_SESSION['login_host']) && $_SESSION['login_host'] !== ($_SERVER['HTTP_HOST'] ?? '')) { session_destroy(); header('Content-Type: application/json'); echo json_encode([]); exit; }
include 'includes/conexion.php';

$marca = $_GET['marca'] ?? '';
if (!$marca) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, marca, modelo, precio FROM modulos WHERE marca = ? ORDER BY modelo");
$stmt->bind_param("s", $marca);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
