<?php
session_start();
header('Content-Type: application/json');
require_once 'includes/verificar_token.php';
include 'includes/conexion.php';

// Verificar sesión
if (!isset($_SESSION['rol'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}
verificarAcceso();

// El autor se determina automáticamente por el rol de sesión
$autor = $_SESSION['rol']; // 'recepcion' o 'tecnico'
$autor_nombre = $_SESSION['nombre'] ?? '';

if (!isset($_POST['orden_id']) || !is_numeric($_POST['orden_id']) || !isset($_POST['mensaje'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

$orden_id = (int)$_POST['orden_id'];
$mensaje = trim($_POST['mensaje']);

if ($mensaje == '') {
    echo json_encode([
        'success' => false,
        'error' => 'Mensaje vacío'
    ]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO notas (orden_id, autor, autor_nombre, mensaje) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $orden_id, $autor, $autor_nombre, $mensaje);

if ($stmt->execute()) {
    $id = $conn->insert_id;

    // Crear notificación para el OTRO rol
    $para_rol = ($autor === 'recepcion') ? 'tecnico' : 'recepcion';
    $titulo = 'Nuevo mensaje en Orden #' . $orden_id;
    $mensaje_notif = ($autor === 'recepcion' ? 'Recepción' : 'Técnico') . ': ' . substr($mensaje, 0, 50) . (strlen($mensaje) > 50 ? '...' : '');

    $stmt_notif = $conn->prepare("INSERT INTO notificaciones (orden_id, desde_rol, para_rol, titulo, mensaje, leida) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt_notif->bind_param("issss", $orden_id, $autor, $para_rol, $titulo, $mensaje_notif);
    $notif_ok = $stmt_notif->execute();

    error_log("[NOTIF NOTA] Orden #$orden_id, desde: $autor, para: $para_rol, notif_ok: " . ($notif_ok ? 'SI' : 'NO'));

    echo json_encode([
        'success' => true,
        'id' => $id,
        'autor' => $autor,
        'autor_nombre' => $autor_nombre,
        'mensaje' => $mensaje,
        'fecha' => date('d/m/Y H:i')
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}