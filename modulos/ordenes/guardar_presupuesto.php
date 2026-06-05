<?php
include 'includes/verificar_sesion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$orden_id = (int)($_POST['orden_id'] ?? 0);
$presupuesto = str_replace(',', '.', $_POST['presupuesto'] ?? '0');
$sena = str_replace(',', '.', $_POST['sena'] ?? '0');
$presupuesto = max(0, (float)$presupuesto);
$sena = max(0, (float)$sena);

if ($orden_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

// Get current values to detect changes
$q = $conn->query("SELECT presupuesto, sena FROM ordenes WHERE id = $orden_id");
$actual = $q->fetch_assoc();
if (!$actual) {
    echo json_encode(['ok' => false, 'error' => 'Orden no encontrada']);
    exit;
}

$cambios = [];
if ((float)$actual['presupuesto'] !== $presupuesto) {
    $cambios[] = 'Presupuesto: $' . number_format($actual['presupuesto'], 2) . ' → $' . number_format($presupuesto, 2);
}
if ((float)$actual['sena'] !== $sena) {
    $cambios[] = 'Seña: $' . number_format($actual['sena'], 2) . ' → $' . number_format($sena, 2);
}

if (empty($cambios)) {
    echo json_encode(['ok' => true]);
    exit;
}

$stmt = $conn->prepare("UPDATE ordenes SET presupuesto = ?, sena = ? WHERE id = ?");
$stmt->bind_param('ddi', $presupuesto, $sena, $orden_id);
$stmt->execute();

// Log each change
foreach ($cambios as $detalle) {
    $estado_log = strpos($detalle, 'Seña') !== false ? 'SEÑA' : 'PRESUPUESTO';
    $stmt_log = $conn->prepare("INSERT INTO estados_log (orden_id, estado, cambiado_por, cambiado_por_usuario) VALUES (?, ?, ?, ?)");
    $rol = $_SESSION['rol'] ?? 'recepcion';
    $stmt_log->bind_param('isss', $orden_id, $estado_log, $rol, $NOMBRE_USUARIO);
    $stmt_log->execute();
}

echo json_encode(['ok' => true]);