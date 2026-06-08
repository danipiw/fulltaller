<?php
session_start();
if (!isset($_SESSION['rol'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }
if (!empty($_SESSION['login_host']) && $_SESSION['login_host'] !== ($_SERVER['HTTP_HOST'] ?? '')) { session_destroy(); header('Content-Type: application/json'); echo json_encode([]); exit; }
include 'conexion.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT id, tipo, precio FROM pin_carga ORDER BY id");
$data = [];
while ($row = $result->fetch_assoc()) $data[] = $row;
echo json_encode($data);
