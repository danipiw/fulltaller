<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
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
    header('Location: ventas.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta #<?php echo $id; ?> - POS FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📥 Ingreso</a>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📤 Egreso</a>
            <span class="nav-sep">|</span>
            <a href="corte_caja.php" class="nav-btn">🔒 Corte</a>
        </div>
        <div class="nav-right">
            <span class="rol-badge">
                <?php echo esAdminPOS() ? '👑' : '👤'; ?>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
        </div>
    </div>
</nav>

<div class="pos-wrapper">

    <div class="panel">
        <div class="page-header" style="margin-bottom:0;">
            <h1>🧾 Detalle de Venta #<?php echo $id; ?></h1>
        </div>
    </div>

    <?php if ($venta['anulada'] ?? 0): ?>
    <div class="alert error" style="text-align:center;font-size:1.1rem;">❌ Esta venta fue anulada</div>
    <?php endif; ?>

    <div class="panel" style="<?php echo ($venta['anulada'] ?? 0) ? 'text-decoration:line-through;opacity:0.7;' : ''; ?>">
        <div class="detalle-info">
            <div class="detalle-row">
                <span class="label">Fecha:</span>
                <span class="value"><?php echo date('d/m/Y H:i', strtotime($venta['created_at'])); ?></span>
            </div>
            <div class="detalle-row">
                <span class="label">Cajero:</span>
                <span class="value"><?php echo htmlspecialchars($venta['cajero']); ?></span>
            </div>
            <div class="detalle-row">
                <span class="label">Método de pago:</span>
                <span class="value"><?php echo ($metodo_icono[$venta['metodo_pago']] ?? '💵') . ' ' . ucfirst($venta['metodo_pago']); ?></span>
            </div>
            <div class="detalle-row">
                <span class="label">Estado:</span>
                <span class="value"><?php echo ($venta['anulada'] ?? 0) ? '❌ Anulada' : '✅ Activa'; ?></span>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>📦 Productos</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th style="text-align:center;">Cantidad</th>
                    <th style="text-align:right;">Precio Unit.</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($d = $detalle->fetch_assoc()): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($d['codigo']); ?></code></td>
                    <td><?php echo htmlspecialchars($d['descripcion']); ?></td>
                    <td style="text-align:center;"><?php echo $d['cantidad']; ?></td>
                    <td class="precio">$<?php echo number_format($d['precio_unitario'], 2); ?></td>
                    <td class="precio">$<?php echo number_format($d['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;font-weight:700;font-size:1.1rem;">TOTAL</td>
                    <td style="text-align:right;font-weight:700;font-size:1.1rem;color:var(--jb-success);">$<?php echo number_format($venta['total'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="display:flex;gap:8px;margin-top:16px;">
        <a href="ticket.php?id=<?php echo $id; ?>" class="btn-primary" target="_blank">🖨️ Imprimir ticket</a>
        <a href="comprobante.php?venta_id=<?php echo $id; ?>" class="btn-primary" style="background:linear-gradient(135deg,#001845,#023e8a);">🧾 Comprobante</a>
        <a href="ventas.php" class="btn-cancelar">← Volver al historial</a>
    </div>

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
