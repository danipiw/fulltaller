<?php

header('Content-Type: application/json');
error_reporting(0);
session_start();
require_once 'includes/verificar_token.php';
if (!isset($_SESSION['rol'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}
verificarAcceso();
include 'includes/conexion.php';

$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$telefono_nuevo = trim($_POST['telefono'] ?? '');
$imei = $_POST['imei'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$marca = $_POST['marca'] ?? '';
$modelo = $_POST['modelo'] ?? '';
$falla = $_POST['falla'] ?? '';
$presupuesto = (float)($_POST['presupuesto'] ?? 0);

$estado = isset($_POST['estado_inicial']) && $_POST['estado_inicial'] == 'APROBADO' ? 'APROBADO' : 'INGRESADO';

$express = isset($_POST['express']) ? 1 : 0;
$sena = (float)(!empty($_POST['sena']) ? $_POST['sena'] : 0);
$observaciones = $_POST['observaciones'] ?? '';

$token = bin2hex(random_bytes(16));

$stmt = $conn->prepare("
    INSERT INTO ordenes 
    (cliente_id, imei, tipo, marca, modelo, falla, presupuesto, estado, express, sena, observaciones, token)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("isssssdsidss", $cliente_id, $imei, $tipo, $marca, $modelo, $falla, $presupuesto, $estado, $express, $sena, $observaciones, $token);

if ($stmt->execute()) {
    $id = $conn->insert_id;

    if (!empty($telefono_nuevo)) {
        $stmt_check_tel = $conn->prepare("SELECT telefono FROM clientes WHERE id = ?");
        $stmt_check_tel->bind_param("i", $cliente_id);
        $stmt_check_tel->execute();
        $tel_actual = $stmt_check_tel->get_result()->fetch_assoc()['telefono'] ?? '';
        if ($tel_actual !== $telefono_nuevo) {
            $stmt_upd_tel = $conn->prepare("UPDATE clientes SET telefono = ? WHERE id = ?");
            $stmt_upd_tel->bind_param("si", $telefono_nuevo, $cliente_id);
            $stmt_upd_tel->execute();
        }
    }

    $titulo = 'Nueva orden #' . $id;
    $mensaje_notif = 'Nueva orden creada como: ' . $estado;
    $para_rol = 'tecnico';
    $stmt_notif = $conn->prepare("INSERT INTO notificaciones (orden_id, desde_rol, para_rol, titulo, mensaje, leida) VALUES (?, 'recepcion', ?, ?, ?, 0)");
    $stmt_notif->bind_param("isss", $id, $para_rol, $titulo, $mensaje_notif);
    @$stmt_notif->execute();

    $conn->query("CREATE TABLE IF NOT EXISTS estados_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        orden_id INT NOT NULL,
        estado VARCHAR(50) NOT NULL,
        cambiado_por VARCHAR(20) NOT NULL,
        fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (orden_id)
    )");
    $stmt_log = $conn->prepare("INSERT INTO estados_log (orden_id, estado, cambiado_por, fecha) VALUES (?, ?, 'recepcion', NOW())");
    $stmt_log->bind_param("is", $id, $estado);
    @$stmt_log->execute();

    $tel_res = $conn->query("SELECT telefono FROM clientes WHERE id = $cliente_id");
    $tel_fila = $tel_res ? $tel_res->fetch_assoc() : null;
    $telefono_cliente = $tel_fila['telefono'] ?? $telefono_nuevo;

    echo json_encode([
        'success' => true,
        'id' => $id,
        'telefono' => $telefono_cliente,
        'token' => $token,
        'message' => 'Orden Nº ' . $id . ' ingresada correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

?>
