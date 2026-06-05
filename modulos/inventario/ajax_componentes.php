<?php
header('Content-Type: application/json');
include 'includes/verificar_sesion.php';

$modelo_id = intval($_GET['modelo_id'] ?? 0);
$stmt = $GLOBALS['pdo']->prepare("SELECT componente_nombre FROM modelo_componentes WHERE modelo_id = ? ORDER BY componente_nombre");
$stmt->execute([$modelo_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
