<?php
header('Content-Type: application/json');
include 'includes/verificar_sesion.php';

$marca_id = intval($_GET['marca_id'] ?? 0);
$stmt = $GLOBALS['pdo']->prepare("SELECT id, nombre FROM modelos WHERE marca_id = ? ORDER BY nombre");
$stmt->execute([$marca_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
