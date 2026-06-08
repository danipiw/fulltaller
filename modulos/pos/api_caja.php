<?php
session_start();
require_once 'db.php';
require_once 'includes/verificar_token.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
verificarAcceso();

header('Content-Type: application/json');
$db = getDB();
$user_id = (int)$_SESSION['usuario_id'];
$hoy = date('Y-m-d');
$action = $_GET['action'] ?? '';

// Buscar pos_caja abierta hoy
$pos_caja_res = $db->query("SELECT id FROM pos_caja WHERE usuario_id = $user_id AND fecha_apertura = '$hoy' AND estado = 'abierta'");
$pos_caja = $pos_caja_res ? $pos_caja_res->fetch_assoc() : null;

if (!$pos_caja) {
    echo json_encode(['success' => false, 'message' => 'No hay caja abierta']);
    exit;
}

$pos_caja_id = (int)$pos_caja['id'];

if ($action === 'movimiento' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $concepto = trim($_POST['concepto'] ?? '');
    $monto = floatval($_POST['monto'] ?? 0);

    if (!in_array($tipo, ['ingreso', 'egreso'])) {
        echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
        exit;
    }
    if (!$concepto) {
        echo json_encode(['success' => false, 'message' => 'El concepto es obligatorio']);
        exit;
    }
    if ($monto <= 0) {
        echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a 0']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO pos_caja_movimientos (caja_id, tipo, concepto, monto, usuario_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issdi", $pos_caja_id, $tipo, $concepto, $monto, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Movimiento registrado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }
    exit;
}

if ($action === 'saldo') {
    // Calcular saldo esperado
    $res = $db->query("SELECT monto_inicial FROM pos_caja WHERE id = $pos_caja_id");
    $pos_caja_data = $res->fetch_assoc();
    $monto_inicial = (float)$pos_caja_data['monto_inicial'];

    // Ventas en efectivo hoy de este usuario
    $pos_ventas = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM pos_ventas WHERE usuario_id = $user_id AND DATE(created_at) = '$hoy' AND metodo_pago = 'efectivo' AND anulada = 0");
    $total_pos_ventas = (float)$pos_ventas->fetch_assoc()['total'];

    // Ingresos manuales
    $ingresos = $db->query("SELECT COALESCE(SUM(monto), 0) as total FROM pos_caja_movimientos WHERE caja_id = $pos_caja_id AND tipo = 'ingreso'");
    $total_ingresos = (float)$ingresos->fetch_assoc()['total'];

    // Egresos manuales
    $egresos = $db->query("SELECT COALESCE(SUM(monto), 0) as total FROM pos_caja_movimientos WHERE caja_id = $pos_caja_id AND tipo = 'egreso'");
    $total_egresos = (float)$egresos->fetch_assoc()['total'];

    $saldo_esperado = $monto_inicial + $total_pos_ventas + $total_ingresos - $total_egresos;

    echo json_encode([
        'success' => true,
        'monto_inicial' => $monto_inicial,
        'pos_ventas_efectivo' => $total_pos_ventas,
        'ingresos' => $total_ingresos,
        'egresos' => $total_egresos,
        'saldo_esperado' => $saldo_esperado,
        'pos_caja_id' => $pos_caja_id
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
