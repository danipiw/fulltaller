<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = (int)$_SESSION['usuario_id'];
$hoy = date('Y-m-d');

// Buscar pos_caja abierta hoy
$pos_caja_res = $db->query("SELECT * FROM pos_caja WHERE usuario_id = $user_id AND fecha_apertura = '$hoy' AND estado = 'abierta'");
$pos_caja = $pos_caja_res->fetch_assoc();

if (!$pos_caja) {
    header('Location: index.php');
    exit;
}

$pos_caja_id = (int)$pos_caja['id'];

// Calcular saldo esperado
$monto_inicial = (float)$pos_caja['monto_inicial'];

$pos_ventas = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM pos_ventas WHERE usuario_id = $user_id AND DATE(created_at) = '$hoy' AND metodo_pago = 'efectivo' AND anulada = 0");
$total_pos_ventas = (float)$pos_ventas->fetch_assoc()['total'];

$ingresos = $db->query("SELECT COALESCE(SUM(monto), 0) as total FROM pos_caja_movimientos WHERE caja_id = $pos_caja_id AND tipo = 'ingreso'");
$total_ingresos = (float)$ingresos->fetch_assoc()['total'];

$egresos = $db->query("SELECT COALESCE(SUM(monto), 0) as total FROM pos_caja_movimientos WHERE caja_id = $pos_caja_id AND tipo = 'egreso'");
$total_egresos = (float)$egresos->fetch_assoc()['total'];

$saldo_esperado = $monto_inicial + $total_pos_ventas + $total_ingresos - $total_egresos;

// Ventas por método de pago (para el reporte completo)
$pos_ventas_metodos = $db->query("SELECT metodo_pago, COUNT(*) as cantidad, SUM(total) as total FROM pos_ventas WHERE usuario_id = $user_id AND DATE(created_at) = '$hoy' AND anulada = 0 GROUP BY metodo_pago");

// Últimos movimientos
$movimientos = $db->query("SELECT * FROM pos_caja_movimientos WHERE caja_id = $pos_caja_id ORDER BY created_at DESC");

$error = '';
$success = '';
$diferencia = 0;

$admin_telefono = '';
$admin_nombre = '';
$resumen_metodos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar'])) {
    $monto_cierre = floatval($_POST['monto_cierre'] ?? 0);
    $diferencia = $monto_cierre - $saldo_esperado;
    $hora = date('H:i:s');

    $stmt = $db->prepare("UPDATE pos_caja SET fecha_cierre = ?, hora_cierre = ?, monto_cierre = ?, monto_esperado = ?, estado = 'cerrada' WHERE id = ?");
    $stmt->bind_param("ssddi", $hoy, $hora, $monto_cierre, $saldo_esperado, $pos_caja_id);
    if ($stmt->execute()) {
        $success = 'Caja cerrada correctamente.';

        $cfg = $db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('admin_nombre','admin_telefono')");
        if ($cfg) {
            while ($c = $cfg->fetch_assoc()) {
                if ($c['clave'] === 'admin_telefono') $admin_telefono = $c['valor'];
                if ($c['clave'] === 'admin_nombre') $admin_nombre = $c['valor'];
            }
        }

        $vmet = $db->query("SELECT metodo_pago, COUNT(*) as cantidad, SUM(total) as total FROM pos_ventas WHERE usuario_id = $user_id AND DATE(created_at) = '$hoy' AND anulada = 0 GROUP BY metodo_pago");
        if ($vmet) {
            while ($vm = $vmet->fetch_assoc()) {
                $resumen_metodos[$vm['metodo_pago']] = ['cantidad' => (int)$vm['cantidad'], 'total' => (float)$vm['total']];
            }
        }
    } else {
        $error = 'Error al cerrar caja: ' . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .corte-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin:16px 0; }
        .corte-item { background:var(--jb-fondo); padding:16px; border-radius:12px; text-align:center; }
        .corte-item .label { font-size:0.85rem; color:#64748b; font-weight:600; text-transform:uppercase; }
        .corte-item .valor { font-size:1.3rem; font-weight:700; margin-top:4px; }
        .corte-total { background:var(--jb-azul-oscuro); color:white; padding:20px; border-radius:12px; text-align:center; margin:16px 0; }
        .corte-total .label { font-size:0.9rem; opacity:0.85; }
        .corte-total .valor { font-size:2rem; font-weight:700; }
        .diferencia { margin-top:8px; font-size:1.1rem; font-weight:600; }
        .diferencia.ok { color:var(--jb-success); }
        .diferencia.error { color:#ef4444; }
        .cierre-form { max-width:400px; margin:20px auto; }
    </style>
</head>
<body>

<?php require 'includes/sidebar.php'; ?>

<nav class="nav-jb">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 0 0 0.25rem;">
        <div class="nav-left" style="display:flex;align-items:center;gap:10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <img src="logo.png?v=<?php echo filemtime('logo.png'); ?>" alt="FullTaller" class="nav-logo" onerror="this.style.display='none'">
                <span style="color:white;font-size:0.95rem;font-weight:500;">Punto de venta</span>
            </a>
        </div>
        <div class="nav-center d-none d-md-flex" style="align-items:center;">
            <a href="index.php" class="nav-btn">🛒 Vender</a>
            <?php if (esAdminPOS()): ?>
            <span class="nav-sep">|</span>
            <a href="usuarios.php" class="nav-btn">👥 Usuarios</a>
            <?php endif; ?>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📥 Ingreso</a>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📤 Egreso</a>
            <span class="nav-sep">|</span>
            <a href="corte_caja.php" class="nav-btn">🔒 Corte</a>
        </div>
        <div class="nav-right">
            <span class="rol-badge" style="margin-right:6px;">
                <?php echo esAdminPOS() ? '👑' : '👤'; ?>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
            <a href="logout.php" class="nav-btn">Salir</a>
        </div>
    </div>
</nav>

<div class="pos-wrapper">
    <div class="page-header">
        <h1>🧾 Corte de Caja</h1>
        <p style="color:#64748b;">
            <?php echo htmlspecialchars($_SESSION['nombre']); ?> — <?php echo date('d/m/Y'); ?>
        </p>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><?php echo $success; ?></div>
    <div style="text-align:center;margin-top:16px;">
        <div class="corte-total">
            <div class="label">MONTO ESPERADO</div>
            <div class="valor">$<?php echo number_format($saldo_esperado, 2); ?></div>
        </div>
        <div class="corte-item <?php echo abs($diferencia) < 0.01 ? 'ok' : 'error'; ?>">
            <div class="label">Diferencia</div>
            <div class="valor">$<?php echo number_format($diferencia, 2); ?></div>
        </div>

        <?php if (!empty($admin_telefono)): ?>
        <div style="margin-top:16px;">
            <p style="color:#64748b;margin-bottom:8px;">📤 Enviar resumen al administrador</p>
            <a href="whatsapp://send?phone=<?php echo urlencode($admin_telefono); ?>&text=<?php echo urlencode(
                '🧾 *CIERRE DE CAJA*' . "\n" .
                'Taller: ' . ($_SESSION['taller_nombre'] ?? 'FullTaller') . "\n" .
                'Cajero: ' . $_SESSION['nombre'] . "\n" .
                'Fecha: ' . date('d/m/Y') . "\n" .
                'Hora cierre: ' . date('H:i') . "\n\n" .
                '💰 *Resumen del turno*' . "\n" .
                '• Ventas efectivo: ' . ($resumen_metodos['efectivo']['cantidad'] ?? 0) . ' ventas - $' . number_format($resumen_metodos['efectivo']['total'] ?? 0, 2) . "\n" .
                '• Ventas tarjeta: ' . ($resumen_metodos['tarjeta']['cantidad'] ?? 0) . ' ventas - $' . number_format($resumen_metodos['tarjeta']['total'] ?? 0, 2) . "\n" .
                '• Ventas transferencia: ' . ($resumen_metodos['transferencia']['cantidad'] ?? 0) . ' ventas - $' . number_format($resumen_metodos['transferencia']['total'] ?? 0, 2) . "\n\n" .
                '💵 Total efectivo: $' . number_format($total_pos_ventas, 2) . "\n" .
                '💰 Saldo esperado: $' . number_format($saldo_esperado, 2) . "\n" .
                '📊 Diferencia: $' . number_format($diferencia, 2)
            ); ?>" target="_blank" class="btn-whatsapp btn-guardar" style="text-decoration:none;display:inline-block;font-size:1.1rem;">
                📱 Enviar WhatsApp a <?php echo htmlspecialchars($admin_nombre ?: 'Administrador'); ?>
            </a>
        </div>
        <?php endif; ?>

        <a href="index.php" class="btn-guardar" style="text-decoration:none;display:inline-block;margin-top:16px;">👉 Volver al POS</a>
    </div>
    <?php else: ?>

    <div class="panel">
        <div class="corte-grid">
            <div class="corte-item">
                <div class="label">💰 Monto Inicial</div>
                <div class="valor">$<?php echo number_format($monto_inicial, 2); ?></div>
            </div>
            <div class="corte-item">
                <div class="label">💵 Ventas Efectivo</div>
                <div class="valor" style="color:var(--jb-success);">+$<?php echo number_format($total_pos_ventas, 2); ?></div>
            </div>
            <div class="corte-item">
                <div class="label">📥 Ingresos Manuales</div>
                <div class="valor" style="color:var(--jb-success);">+$<?php echo number_format($total_ingresos, 2); ?></div>
            </div>
            <div class="corte-item">
                <div class="label">📤 Egresos Manuales</div>
                <div class="valor" style="color:#ef4444;">-$<?php echo number_format($total_egresos, 2); ?></div>
            </div>
        </div>

        <div class="corte-total">
            <div class="label">💰 SALDO ESPERADO EN CAJA</div>
            <div class="valor">$<?php echo number_format($saldo_esperado, 2); ?></div>
        </div>
    </div>

    <?php if ($pos_ventas_metodos && $pos_ventas_metodos->num_rows > 0): ?>
    <div class="panel">
        <h2>📊 Ventas del turno</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Método de Pago</th>
                    <th>Cantidad</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($v = $pos_ventas_metodos->fetch_assoc()): ?>
                <tr>
                    <td><?php
                        $iconos = ['efectivo' => '💵', 'tarjeta' => '💳', 'transferencia' => '🏦'];
                        echo ($iconos[$v['metodo_pago']] ?? '') . ' ' . ucfirst($v['metodo_pago']);
                    ?></td>
                    <td><?php echo $v['cantidad']; ?></td>
                    <td class="precio">$<?php echo number_format($v['total'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($movimientos && $movimientos->num_rows > 0): ?>
    <div class="panel">
        <h2>📋 Movimientos del turno</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $movimientos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $m['tipo'] === 'ingreso' ? '📥 Ingreso' : '📤 Egreso'; ?></td>
                    <td><?php echo htmlspecialchars($m['concepto']); ?></td>
                    <td class="precio" style="color:<?php echo $m['tipo'] === 'ingreso' ? 'var(--jb-success)' : '#ef4444'; ?>;">
                        <?php echo $m['tipo'] === 'ingreso' ? '+' : '-'; ?>$<?php echo number_format($m['monto'], 2); ?>
                    </td>
                    <td><?php echo date('H:i', strtotime($m['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="panel cierre-form">
        <h2>🔒 Cerrar Caja</h2>
        <p style="color:#64748b;margin-bottom:16px;">
            Ingresá el dinero <strong>físico</strong> que hay en la caja para cerrar el turno.
        </p>
        <form method="POST">
            <div class="form-group">
                <label>Dinero físico en la caja ($)</label>
                <input type="number" name="monto_cierre" step="0.01" min="0" required autofocus
                    value="<?php echo $saldo_esperado; ?>"
                    style="font-size:1.5rem;text-align:center;padding:16px;border:2px solid var(--jb-azul);border-radius:12px;width:100%;">
            </div>
            <button type="submit" name="cerrar" value="1" class="btn-guardar" style="width:100%;padding:16px;font-size:1.1rem;margin-top:8px;"
                onclick="return confirm('¿Estás seguro de cerrar la caja?')">
                🔒 Cerrar Caja
            </button>
            <a href="index.php" class="btn-cancelar" style="display:block;text-align:center;margin-top:8px;">Volver al POS</a>
        </form>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('jb_dark_mode', isDark ? '1' : '0');
    updateDarkModeIcon();
}
function updateDarkModeIcon() {
    const sidebarIcon = document.getElementById('sidebarIconDark');
    const sidebarText = document.getElementById('sidebarTextDark');
    if (document.body.classList.contains('dark-mode')) {
        if (sidebarIcon) sidebarIcon.className = 'bi bi-sun-fill';
        if (sidebarText) sidebarText.textContent = 'Modo Claro';
    } else {
        if (sidebarIcon) sidebarIcon.className = 'bi bi-moon-stars-fill';
        if (sidebarText) sidebarText.textContent = 'Modo Nocturno';
    }
}
if (localStorage.getItem('jb_dark_mode') === '1') {
    document.body.classList.add('dark-mode');
}
updateDarkModeIcon();
</script>
</body>
</html>
<?php $db->close(); ?>