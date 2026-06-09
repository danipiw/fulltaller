<?php
session_start();
require_once 'db.php';
require_once 'includes/verificar_token.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}
verificarAcceso();

$action = $_GET['action'] ?? '';

if ($action === 'buscar' && isset($_GET['codigo'])) {
    $db = getDB();
    $codigo = $db->real_escape_string($_GET['codigo']);
    
    $result = $db->query("SELECT * FROM pos_productos WHERE codigo = '$codigo' AND activo = 1 LIMIT 1");
    $producto = $result->fetch_assoc();
    $db->close();
    
    if ($producto) {
        echo json_encode(['success' => true, 'producto' => $producto]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    exit;
}

$action_post = $_POST['action'] ?? '';
if ($action_post === 'anular' && isset($_POST['id'])) {
    $db = getDB();
    $id = intval($_POST['id']);

    $check = $db->query("SELECT anulada FROM pos_ventas WHERE id = $id");
    $venta = $check ? $check->fetch_assoc() : null;
    if (!$venta) {
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
        $db->close(); exit;
    }
    if ($venta['anulada']) {
        echo json_encode(['success' => false, 'message' => 'La venta ya está anulada']);
        $db->close(); exit;
    }

    $db->query("UPDATE pos_ventas SET anulada = 1 WHERE id = $id");

    $comun = $db->query("SELECT id FROM pos_productos WHERE codigo = 'COMUN' LIMIT 1")->fetch_assoc();
    $comun_id = $comun['id'] ?? 0;
    $detalle = $db->query("SELECT producto_id, cantidad FROM pos_venta_detalle WHERE venta_id = $id");
    while ($d = $detalle->fetch_assoc()) {
        if ($d['producto_id'] != $comun_id) {
            $db->query("UPDATE pos_productos SET stock = stock + {$d['cantidad']} WHERE id = {$d['producto_id']}");
        }
    }

    echo json_encode(['success' => true, 'message' => 'Venta anulada y stock restaurado']);
    $db->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>