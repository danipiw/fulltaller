<?php
session_start();
header('Content-Type: application/json');
require_once 'includes/verificar_token.php';
include 'includes/conexion.php';
include 'includes/estados_helper.php';

// Verificar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}
verificarAcceso();

$rol_actual = $_SESSION['rol'];
$es_full = ($rol_actual === 'full');
$es_admin = ($rol_actual === 'admin');

// Verificar que lleguen los datos necesarios
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['estado'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

$id = (int)$_POST['id'];
$estado = $_POST['estado'];

// Estados permitidos
$estados_permitidos = obtenerTodosEstados($conn);

if (!in_array($estado, $estados_permitidos)) {
    echo json_encode([
        'success' => false,
        'error' => 'Estado no válido'
    ]);
    exit;
}

// Validar permisos según rol
$estados_recepcion = array_merge(obtenerEstadosRecepcion($conn), ['ENTREGADO']);
$estados_tecnico = obtenerEstadosTecnico($conn);

if (!$es_full && !$es_admin) {
    if ($rol_actual === 'recepcion' && !in_array($estado, $estados_recepcion)) {
        echo json_encode([
            'success' => false,
            'error' => 'No tienes permiso para cambiar a este estado'
        ]);
        exit;
    }
}
if ($rol_actual === 'tecnico' && !in_array($estado, $estados_tecnico)) {
    echo json_encode([
        'success' => false,
        'error' => 'No tienes permiso para cambiar a este estado'
    ]);
    exit;
}

// Actualizar estado
$stmt = $conn->prepare("UPDATE ordenes SET estado = ? WHERE id = ?");
$stmt->bind_param("si", $estado, $id);

if ($stmt->execute()) {
    // Registrar en historial de estados
    $conn->query("CREATE TABLE IF NOT EXISTS estados_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        orden_id INT NOT NULL,
        estado VARCHAR(50) NOT NULL,
        cambiado_por VARCHAR(20) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (orden_id)
    )");
    $usuario_nombre = $_SESSION['nombre'] ?? '';
    $stmt_log = $conn->prepare("INSERT INTO estados_log (orden_id, estado, cambiado_por, cambiado_por_usuario, fecha) VALUES (?, ?, ?, ?, NOW())");
    $stmt_log->bind_param("isss", $id, $estado, $rol_actual, $usuario_nombre);
    $stmt_log->execute();

    // Crear notificación
    $titulo = 'Estado actualizado - Orden #' . $id;
    $notif_ok = false;

    if ($es_full || $es_admin) {
        $notif_rol = $es_admin ? 'admin' : 'full';
        $notif_nombre = $es_admin ? 'Admin' : 'Full';
        $mensaje_notif = $notif_nombre . ' cambió el estado a: ' . $estado;
        foreach (['recepcion', 'tecnico'] as $destino) {
            $stmt_n = $conn->prepare("INSERT INTO notificaciones (orden_id, desde_rol, para_rol, titulo, mensaje, leida) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt_n->bind_param("issss", $id, $notif_rol, $destino, $titulo, $mensaje_notif);
            $notif_ok = $stmt_n->execute();
            $stmt_n->close();
        }
    } else {
        $para_rol = ($rol_actual === 'recepcion') ? 'tecnico' : 'recepcion';
        $mensaje_notif = ($rol_actual === 'recepcion' ? 'Recepción' : 'Técnico') . ' cambió el estado a: ' . $estado;
        $stmt_notif = $conn->prepare("INSERT INTO notificaciones (orden_id, desde_rol, para_rol, titulo, mensaje, leida) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt_notif->bind_param("issss", $id, $rol_actual, $para_rol, $titulo, $mensaje_notif);
        $notif_ok = $stmt_notif->execute();
        $stmt_notif->close();
    }

    echo json_encode([
        'success' => true,
        'estado' => $estado,
        'id' => $id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

exit;