<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();

$config = [];
$r = $db->query("SELECT clave, valor FROM configuracion");
if ($r) {
    while ($f = $r->fetch_assoc()) {
        $config[$f['clave']] = $f['valor'];
    }
}

$prefill_products = [];
$venta_id = intval($_GET['venta_id'] ?? 0);
if ($venta_id) {
    $stmt = $db->prepare("SELECT vd.*, p.codigo, COALESCE(vd.descripcion, p.descripcion) as descripcion FROM pos_venta_detalle vd JOIN pos_productos p ON vd.producto_id = p.id WHERE vd.venta_id = ?");
    $stmt->bind_param('i', $venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($item = $res->fetch_assoc()) {
        $prefill_products[] = $item;
    }
    $stmt->close();
}

$db->close();

$taller_nombre = $config['taller_nombre'] ?? 'Mi Taller';
$taller_direccion = $config['taller_direccion'] ?? '';
$taller_telefono = $config['taller_telefono'] ?? '';
$tiene_logo = !empty($config['logo_comprobante']) && file_exists(__DIR__ . '/logo_comprobante.png');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante - POS FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .comprobante-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 0 auto;
        }
        .comp-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 2px solid #001845;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .comp-header .comp-logo {
            max-height: 80px;
            max-width: 120px;
            border-radius: 4px;
        }
        .comp-header .comp-info {
            flex: 1;
        }
        .comp-header .comp-info h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #001845;
            font-weight: 700;
        }
        .comp-header .comp-info p {
            margin: 2px 0;
            color: #475569;
            font-size: 0.85rem;
        }
        .comp-cliente {
            background: #f8faff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .comp-cliente h5 {
            font-size: 0.9rem;
            color: #001845;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .comp-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .comp-tabla thead {
            background: #001845;
            color: white;
        }
        .comp-tabla thead th {
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .comp-tabla tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .comp-tabla tfoot td {
            padding: 10px 12px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .comp-total {
            text-align: right;
            color: #10b981;
            font-size: 1.3rem;
        }
        .comp-footer {
            text-align: center;
            margin-top: 15px;
            color: #94a3b8;
            font-size: 0.7rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .item-row td input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .item-row td input.item-desc { min-width: 180px; }
        .item-row td input.item-cant,
        .item-row td input.item-precio,
        .item-row td input.item-subtotal { text-align: right; width: 90px; }
        .btn-remove-item {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 2px 6px;
        }
        .cliente-display { display: none; }
        .cliente-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 20px;
            font-size: 0.85rem;
        }
        .cliente-grid .cli-label { color: #64748b; font-weight: 600; }

        body.dark-mode .comprobante-content { background: #1a2235; color: #e2e8f0; }
        body.dark-mode .comp-header { border-bottom-color: #2d3748; }
        body.dark-mode .comp-header .comp-info h2 { color: #e2e8f0; }
        body.dark-mode .comp-header .comp-info p { color: #94a3b8; }
        body.dark-mode .comp-cliente { background: #0f1729; border-color: #2d3748; }
        body.dark-mode .comp-cliente h5 { color: #e2e8f0; }
        body.dark-mode .cliente-grid .cli-label { color: #94a3b8; }
        body.dark-mode .comp-tabla thead { background: #0d1b3e; }
        body.dark-mode .comp-tabla tbody td { border-bottom-color: #2d3748; color: #cbd5e1; }
        body.dark-mode .item-row td input { background: #0f1729; color: #e2e8f0; border-color: #2d3748; }
        body.dark-mode .comp-footer { border-top-color: #2d3748; color: #64748b; }

        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .cliente-display { display: block !important; }
            .cliente-form { display: none !important; }
            .comprobante-content { box-shadow: none; padding: 20px; max-width: 100%; }
            .comp-tabla thead { background: #001845 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .comp-header { border-bottom-color: #001845 !important; }
            .comp-cliente { background: #f8faff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .comp-total { color: #10b981 !important; }
            .item-row td { border-bottom: 1px solid #ddd !important; }
            .item-row td input {
                border: none;
                padding: 2px;
                background: transparent;
                color: #1e293b;
                font-size: 0.85rem;
            }
            .item-row td input.item-subtotal { color: #10b981; font-weight: 600; }
            .btn-remove-item { display: none; }
        }
    </style>
</head>
<body>

<?php require 'includes/sidebar.php'; ?>

<nav class="nav-jb no-print">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 0 0 0.25rem;">
        <div class="nav-left" style="display:flex;align-items:center;gap:10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <img src="logo.png?v=<?php echo filemtime('logo.png'); ?>" alt="FullTaller" class="nav-logo" onerror="this.style.display='none'">
                <span style="color:white;font-size:0.95rem;font-weight:500;">Comprobante</span>
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
            <a href="logout.php" class="nav-btn">➤ Salir</a>
        </div>
    </div>
</nav>

<div class="pos-wrapper">
    <div class="page-header no-print">
        <h1>🧾 Comprobante</h1>
        <p class="text-muted" style="font-size:0.9rem;">Completá los datos del cliente y los artículos, luego imprimí en A4.</p>
    </div>

    <div class="comprobante-content">
        <div class="comp-header">
            <div>
                <?php if ($tiene_logo): ?>
                <img src="logo_comprobante.png?v=<?php echo filemtime(__DIR__ . '/logo_comprobante.png'); ?>" class="comp-logo" alt="Logo">
                <?php endif; ?>
            </div>
            <div class="comp-info">
                <h2 id="printTallerNombre"><?php echo htmlspecialchars($taller_nombre); ?></h2>
                <?php if ($taller_direccion): ?>
                <p id="printTallerDir">📍 <?php echo htmlspecialchars($taller_direccion); ?></p>
                <?php endif; ?>
                <?php if ($taller_telefono): ?>
                <p id="printTallerTel">📞 <?php echo htmlspecialchars($taller_telefono); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="comp-cliente">
            <h5>👤 Datos del Cliente</h5>
            <div class="cliente-form no-print">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre y Apellido</label>
                        <input type="text" id="cliente_nombre" class="form-control form-control-sm" placeholder="Juan Pérez" oninput="actualizarCliente()">
                    </div>
                    <div class="form-group">
                        <label>DNI / CUIT</label>
                        <input type="text" id="cliente_dni" class="form-control form-control-sm" placeholder="20.123.456-7" oninput="actualizarCliente()">
                    </div>
                </div>
                <div class="form-row" style="margin-top:8px;">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" id="cliente_telefono" class="form-control form-control-sm" placeholder="+54 9 11 1234-5678" oninput="actualizarCliente()">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" id="cliente_direccion" class="form-control form-control-sm" placeholder="Calle 1234" oninput="actualizarCliente()">
                    </div>
                </div>
            </div>
            <div class="cliente-display" id="clienteDisplay"></div>
        </div>

        <table class="comp-tabla">
            <thead>
                <tr>
                    <th style="width:45%;">Descripción</th>
                    <th style="width:12%;text-align:center;">Cant.</th>
                    <th style="width:18%;text-align:right;">Precio Unit.</th>
                    <th style="width:18%;text-align:right;">Subtotal</th>
                    <th class="no-print" style="width:7%;"></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;font-weight:700;">TOTAL</td>
                    <td id="compTotal" class="comp-total">$0.00</td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
        </table>

        <div class="no-print" style="margin-bottom:15px;">
            <button type="button" class="btn-guardar btn-sm" onclick="agregarItem()">➕ Agregar artículo</button>
        </div>

        <div class="no-print" style="display:flex;gap:10px;">
            <button type="button" class="btn-primary" onclick="window.print()" style="flex:1;padding:14px;font-size:1.1rem;">🖨️ Imprimir Comprobante</button>
            <button type="button" class="btn-cancelar" onclick="limpiarFormulario()">🗑️ Limpiar</button>
        </div>

        <div class="comp-footer">
            Comprobante generado el <?php echo date('d/m/Y H:i'); ?> por <?php echo htmlspecialchars($_SESSION['nombre']); ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let itemCount = 0;

function actualizarCliente() {
    const nombre = document.getElementById('cliente_nombre').value.trim();
    const dni = document.getElementById('cliente_dni').value.trim();
    const telefono = document.getElementById('cliente_telefono').value.trim();
    const direccion = document.getElementById('cliente_direccion').value.trim();

    let html = '';
    if (nombre || dni || telefono || direccion) {
        html += '<div class="cliente-grid">';
        if (nombre) html += '<div><span class="cli-label">Nombre:</span> ' + escHtml(nombre) + '</div>';
        if (dni) html += '<div><span class="cli-label">DNI/CUIT:</span> ' + escHtml(dni) + '</div>';
        if (telefono) html += '<div><span class="cli-label">Teléfono:</span> ' + escHtml(telefono) + '</div>';
        if (direccion) html += '<div><span class="cli-label">Dirección:</span> ' + escHtml(direccion) + '</div>';
        html += '</div>';
    } else {
        html = '<em style="color:#94a3b8;">—</em>';
    }
    document.getElementById('clienteDisplay').innerHTML = html;
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function agregarItem(desc, cant, precio) {
    const idx = itemCount++;
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.id = 'item-row-' + idx;
    tr.innerHTML = `
        <td><input type="text" class="item-desc form-control form-control-sm" placeholder="Descripción" value="${escHtml(desc || '')}" oninput="actualizarItem(${idx})"></td>
        <td><input type="number" class="item-cant form-control form-control-sm" value="${cant || 1}" min="1" oninput="actualizarItem(${idx})" style="text-align:center;"></td>
        <td><input type="number" class="item-precio form-control form-control-sm" value="${precio || 0}" step="0.01" min="0" oninput="actualizarItem(${idx})" style="text-align:right;"></td>
        <td><input type="text" class="item-subtotal form-control form-control-sm" value="$0.00" readonly style="text-align:right;font-weight:600;color:#10b981;background:#f1f5f9;"></td>
        <td class="no-print"><button type="button" class="btn-remove-item" onclick="eliminarItem(${idx})">×</button></td>
    `;
    tbody.appendChild(tr);
    if (desc || cant) actualizarItem(idx);
    actualizarTotal();
}

function actualizarItem(idx) {
    const desc = document.querySelector('#item-row-' + idx + ' .item-desc').value;
    const cant = parseFloat(document.querySelector('#item-row-' + idx + ' .item-cant').value) || 0;
    const precio = parseFloat(document.querySelector('#item-row-' + idx + ' .item-precio').value) || 0;
    const subtotal = cant * precio;
    document.querySelector('#item-row-' + idx + ' .item-subtotal').value = '$' + subtotal.toFixed(2);
    actualizarTotal();
}

function eliminarItem(idx) {
    const row = document.getElementById('item-row-' + idx);
    if (row) row.remove();
    actualizarTotal();
}

function actualizarTotal() {
    let total = 0;
    document.querySelectorAll('#itemsBody .item-row').forEach(row => {
        const cant = parseFloat(row.querySelector('.item-cant').value) || 0;
        const precio = parseFloat(row.querySelector('.item-precio').value) || 0;
        total += cant * precio;
    });
    document.getElementById('compTotal').textContent = '$' + total.toFixed(2);
}

function limpiarFormulario() {
    document.getElementById('cliente_nombre').value = '';
    document.getElementById('cliente_dni').value = '';
    document.getElementById('cliente_telefono').value = '';
    document.getElementById('cliente_direccion').value = '';
    document.getElementById('itemsBody').innerHTML = '';
    itemCount = 0;
    actualizarCliente();
    actualizarTotal();
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('jb_dark_mode', isDark ? '1' : '0');
    updateDarkModeIcon();
}
function updateDarkModeIcon() {
    const si = document.getElementById('sidebarIconDark');
    const st = document.getElementById('sidebarTextDark');
    if (document.body.classList.contains('dark-mode')) {
        if (si) si.className = 'bi bi-sun-fill';
        if (st) st.textContent = 'Modo Claro';
    } else {
        if (si) si.className = 'bi bi-moon-stars-fill';
        if (st) st.textContent = 'Modo Nocturno';
    }
}
if (localStorage.getItem('jb_dark_mode') === '1') document.body.classList.add('dark-mode');
updateDarkModeIcon();

<?php if (!empty($prefill_products)): ?>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($prefill_products as $p): ?>
    agregarItem(
        '<?php echo htmlspecialchars(addslashes($p['descripcion'])); ?>',
        <?php echo (int)$p['cantidad']; ?>,
        <?php echo (float)$p['precio_unitario']; ?>
    );
    <?php endforeach; ?>
});
<?php endif; ?>
</script>
</body>
</html>
