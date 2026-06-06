<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();

$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_cajero = $_GET['cajero'] ?? '';
$filtro_metodo = $_GET['metodo'] ?? '';
$filtro_anuladas = $_GET['anuladas'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filtro_fecha) {
    $where[] = "DATE(v.created_at) = ?";
    $params[] = $filtro_fecha;
    $types .= 's';
}
if ($filtro_cajero) {
    $where[] = "v.usuario_id = ?";
    $params[] = $filtro_cajero;
    $types .= 'i';
}
if ($filtro_metodo) {
    $where[] = "v.metodo_pago = ?";
    $params[] = $filtro_metodo;
    $types .= 's';
}
if ($filtro_anuladas === 'si') {
    $where[] = "v.anulada = 1";
} elseif ($filtro_anuladas === 'no') {
    $where[] = "v.anulada = 0";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

function bindParams($stmt, $types, $params) {
    if (empty($params)) return;
    $refs = [$types];
    foreach ($params as $k => $v) {
        $refs[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$total_sql = "SELECT SUM(v.total) as total FROM pos_ventas v $where_sql";
$stmt_total = $db->prepare($total_sql);
bindParams($stmt_total, $types, $params);
$stmt_total->execute();
$total_result = $stmt_total->get_result()->fetch_assoc();
$total_ventas = $total_result['total'] ?? 0;
$stmt_total->close();

$totales_metodo = ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0];
if (!$filtro_metodo) {
    $metodo_sql = "SELECT v.metodo_pago, SUM(v.total) as total FROM pos_ventas v $where_sql GROUP BY v.metodo_pago";
    $stmt_metodo = $db->prepare($metodo_sql);
    bindParams($stmt_metodo, $types, $params);
    $stmt_metodo->execute();
    $metodo_res = $stmt_metodo->get_result();
    while ($m = $metodo_res->fetch_assoc()) {
        $totales_metodo[$m['metodo_pago']] = $m['total'];
    }
    $stmt_metodo->close();
}

$sql = "SELECT v.*, u.nombre as cajero, COUNT(vd.id) as num_items 
        FROM pos_ventas v 
        LEFT JOIN pos_venta_detalle vd ON v.id = vd.venta_id 
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        $where_sql
        GROUP BY v.id 
        ORDER BY v.created_at DESC";

$stmt = $db->prepare($sql);
bindParams($stmt, $types, $params);
$stmt->execute();
$ventas = $stmt->get_result();

$cajeros = $db->query("SELECT id, nombre FROM usuarios WHERE activo=1 ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Ventas - POS FullTaller</title>
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
    <div class="page-header">
        <h1>📊 Registro de Ventas</h1>
    </div>

    <div class="panel filtros-panel">
        <h2>🔍 Filtros</h2>
        <form method="GET" class="filtros-form">
            <div class="form-group">
                <label>Fecha:</label>
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
            </div>
            <div class="form-group">
                <label>Cajero:</label>
                <select name="cajero">
                    <option value="">Todos</option>
                    <?php while ($c = $cajeros->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $filtro_cajero == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nombre']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Método de Pago:</label>
                <select name="metodo">
                    <option value="">Todos</option>
                    <option value="efectivo" <?php echo $filtro_metodo === 'efectivo' ? 'selected' : ''; ?>>💵 Efectivo</option>
                    <option value="tarjeta" <?php echo $filtro_metodo === 'tarjeta' ? 'selected' : ''; ?>>💳 Tarjeta</option>
                    <option value="transferencia" <?php echo $filtro_metodo === 'transferencia' ? 'selected' : ''; ?>>🏦 Transferencia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estado:</label>
                <select name="anuladas">
                    <option value="">Todas</option>
                    <option value="no" <?php echo $filtro_anuladas === 'no' ? 'selected' : ''; ?>>Activas</option>
                    <option value="si" <?php echo $filtro_anuladas === 'si' ? 'selected' : ''; ?>>Anuladas</option>
                </select>
            </div>
            <button type="submit" class="btn-guardar">🔍 Filtrar</button>
            <a href="ventas.php" class="btn-cancelar">Limpiar</a>
        </form>

        <div class="resumen-filtro">
            <strong>Total filtrado: <span class="precio-grande">$<?php echo number_format($total_ventas, 2); ?></span></strong>
            <?php if (!$filtro_metodo && $total_ventas > 0): ?>
            <div style="display:flex;gap:16px;margin-top:8px;flex-wrap:wrap;">
                <span>💵 Efectivo: <strong style="color:var(--jb-success);">$<?php echo number_format($totales_metodo['efectivo'], 2); ?></strong></span>
                <span>💳 Tarjeta: <strong style="color:var(--jb-azul);">$<?php echo number_format($totales_metodo['tarjeta'], 2); ?></strong></span>
                <span>🏦 Transferencia: <strong style="color:var(--jb-azul);">$<?php echo number_format($totales_metodo['transferencia'], 2); ?></strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <h2>Lista de Ventas</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th># Venta</th>
                    <th>Fecha</th>
                    <th>Cajero</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Método de Pago</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($v = $ventas->fetch_assoc()): 
                    $anulada = (int)($v['anulada'] ?? 0);
                ?>
                <tr onclick="window.location='detalle_venta.php?id=<?php echo $v['id']; ?>'" style="cursor:pointer;<?php echo $anulada ? 'text-decoration:line-through;opacity:0.6;' : ''; ?>">
                    <td><a href="detalle_venta.php?id=<?php echo $v['id']; ?>" style="color:var(--jb-azul);font-weight:700;text-decoration:none;">#<?php echo $v['id']; ?></a></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($v['cajero']); ?></td>
                    <td><?php echo $v['num_items']; ?></td>
                    <td class="precio">$<?php echo number_format($v['total'], 2); ?></td>
                    <td>
                        <?php 
                        $iconos = ['efectivo' => '💵', 'tarjeta' => '💳', 'transferencia' => '🏦'];
                        if ($anulada) {
                            echo '❌ Anulada';
                        } else {
                            echo ($iconos[$v['metodo_pago']] ?? '💵') . ' ' . ucfirst($v['metodo_pago']);
                        }
                        ?>
                    </td>
                    <td class="text-end">
                        <div style="display:flex;gap:4px;justify-content:flex-end;">
                        <?php if (!$anulada): ?>
                        <a href="ticket.php?id=<?php echo $v['id']; ?>" class="btn-primary btn-sm" style="text-decoration:none;font-size:0.75rem;padding:4px 8px;" onclick="event.stopPropagation();" target="_blank">🖨️ Ticket</a>
                        <a href="comprobante.php?venta_id=<?php echo $v['id']; ?>" class="btn-edit btn-sm" style="text-decoration:none;font-size:0.75rem;padding:4px 8px;" onclick="event.stopPropagation();">🧾 Comprobante</a>
                        <button type="button" class="btn-delete btn-sm" style="padding:4px 8px;font-size:0.75rem;" onclick="event.stopPropagation();anularVenta(<?php echo $v['id']; ?>)">❌ Anular</button>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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

function anularVenta(id) {
    if (!confirm('¿Anular esta venta? Se restaurará el stock de los productos.')) return;
    fetch('api.php?action=anular&id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Venta anulada y stock restaurado.');
                location.reload();
            } else {
                alert('❌ ' + (data.message || 'Error al anular'));
            }
        })
        .catch(() => alert('❌ Error de conexión'));
}
</script>
</body>
</html>
<?php 
$stmt->close();
$db->close(); 
?>
