<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT v.*, u.nombre as cajero FROM pos_ventas v JOIN usuarios u ON v.usuario_id = u.id WHERE v.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    $db->close();
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT vd.*, COALESCE(vd.descripcion, p.descripcion) as descripcion, p.codigo FROM pos_venta_detalle vd JOIN pos_productos p ON vd.producto_id = p.id WHERE vd.venta_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$detalle = $stmt->get_result();
$stmt->close();
$db->close();

$metodo_icono = ['efectivo' => '💵', 'tarjeta' => '💳', 'transferencia' => '🏦'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?php echo $id; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #1e293b;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }
        .ticket { text-align: center; }
        .ticket-header { border-bottom: 1px dashed #94a3b8; padding-bottom: 10px; margin-bottom: 10px; }
        .ticket-header h1 { font-size: 16px; color: #001845; }
        .ticket-header p { color: #64748b; font-size: 11px; }
        .ticket-body { text-align: left; }
        .ticket-item { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
        .ticket-item .desc { flex: 1; }
        .ticket-item .cant { text-align: center; width: 30px; }
        .ticket-item .importe { text-align: right; width: 70px; }
        .ticket-total { border-top: 1px dashed #94a3b8; margin-top: 8px; padding-top: 8px; }
        .ticket-total .line { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; }
        .ticket-footer { border-top: 1px dashed #94a3b8; margin-top: 10px; padding-top: 10px; font-size: 11px; color: #64748b; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print button { padding: 10px 20px; background: #001845; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.location.href='index.php'" style="background:#64748b;margin-left:8px;">Volver</button>
    </div>

    <div class="ticket">
        <div class="ticket-header">
            <h1>FullTaller POS</h1>
            <p>Ticket #<?php echo $id; ?></p>
            <p><?php echo date('d/m/Y H:i', strtotime($venta['created_at'])); ?></p>
            <p>Cajero: <?php echo htmlspecialchars($venta['cajero']); ?></p>
        </div>

        <div class="ticket-body">
            <div style="display:flex;justify-content:space-between;font-weight:bold;border-bottom:1px solid #e2e8f0;padding-bottom:4px;margin-bottom:4px;">
                <span class="desc">Producto</span>
                <span class="cant">Cant</span>
                <span class="importe">Importe</span>
            </div>
            <?php while ($d = $detalle->fetch_assoc()): ?>
            <div class="ticket-item">
                <span class="desc"><?php echo htmlspecialchars($d['descripcion']); ?></span>
                <span class="cant"><?php echo $d['cantidad']; ?></span>
                <span class="importe">$<?php echo number_format($d['subtotal'], 2); ?></span>
            </div>
            <?php endwhile; ?>

            <div class="ticket-total">
                <div class="line">
                    <span>TOTAL</span>
                    <span>$<?php echo number_format($venta['total'], 2); ?></span>
                </div>
                <div style="text-align:right;font-size:11px;color:#64748b;margin-top:4px;">
                    <?php echo ($metodo_icono[$venta['metodo_pago']] ?? '💵') . ' ' . htmlspecialchars(ucfirst($venta['metodo_pago'])); ?>
                </div>
            </div>
        </div>

        <div class="ticket-footer">
            <p>¡Gracias por su compra!</p>
            <p style="font-size:10px;">FullTaller - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
