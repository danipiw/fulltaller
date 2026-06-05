<?php
session_start();
header('Content-Type: application/json');
include 'includes/conexion.php';

// Aceptar rol desde POST (para fetch AJAX) o desde sesión (fallback)
$rol = null;
if (isset($_POST['rol']) && in_array($_POST['rol'], ['recepcion', 'tecnico'])) {
    $rol = $_POST['rol'];
} elseif (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['recepcion', 'tecnico'])) {
    $rol = $_SESSION['rol'];
}

if (!$rol) {
    echo json_encode(['success' => false, 'error' => 'Sin sesión o rol inválido']);
    exit;
}

$marcar_todas = isset($_POST['marcar_todas']) && $_POST['marcar_todas'] == '1';

// Si se solicita marcar todas como leídas (al abrir el dropdown)
if ($marcar_todas) {
    $stmt_mark = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE para_rol = ?");
    $stmt_mark->bind_param("s", $rol);
    $stmt_mark->execute();
}

// Obtener las últimas 20 notificaciones (tanto leídas como no leídas)
$stmt = $conn->prepare("
    SELECT * FROM notificaciones 
    WHERE para_rol = ? 
    ORDER BY fecha DESC 
    LIMIT 20
");
$stmt->bind_param("s", $rol);
$stmt->execute();
$result = $stmt->get_result();

$notificaciones = [];
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'id' => (int)$row['id'],
        'orden_id' => (int)$row['orden_id'],
        'desde_rol' => $row['desde_rol'],
        'titulo' => $row['titulo'],
        'mensaje' => $row['mensaje'],
        'fecha' => date('d/m H:i', strtotime($row['fecha'])),
        'leida' => (int)$row['leida']
    ];
}

// Contar total no leídas (para el badge)
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE para_rol = ? AND leida = 0");
$stmt_count->bind_param("s", $rol);
$stmt_count->execute();
$count_result = $stmt_count->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'notificaciones' => $notificaciones,
    'total_no_leidas' => (int)$count_result['total']
]);
