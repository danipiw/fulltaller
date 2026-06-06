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
$terminos = $config['legal_terminos'] ?? '';
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
        .comprobante-preview {
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
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .comp-cliente h5 {
            font-size: 0.9rem;
            color: #001845;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .comp-cliente-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
            font-size: 0.85rem;
        }
        .comp-cliente-grid .label {
            color: #64748b;
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
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
        }
        .comp-tabla tfoot td {
            padding: 8px 12px;
            font-weight: 700;
            font-size: 1rem;
        }
        .comp-total {
            text-align: right;
            font-size: 1.3rem;
            font-weight: 700;
            color: #10b981;
            border-top: 2px solid #001845;
            padding-top: 8px;
        }
        .comp-terminos {
            margin-top: 15px;
            padding: 10px 14px;
            background: #f8faff;
            border-radius: 6px;
            font-size: 0.75rem;
            color: #64748b;
            white-space: pre-line;
            border: 1px solid #e2e8f0;
        }
        .comp-footer {
            text-align: center;
            margin-top: 15px;
            color: #94a3b8;
            font-size: 0.75rem;
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

        body.dark-mode .comprobante-preview {
            background: #1a2235;
            color: #e2e8f0;
        }
        body.dark-mode .comp-header { border-bottom-color: #2d3748; }
        body.dark-mode .comp-header .comp-info h2 { color: #e2e8f0; }
        body.dark-mode .comp-header .comp-info p { color: #94a3b8; }
        body.dark-mode .comp-cliente { background: #0f1729; border-color: #2d3748; }
        body.dark-mode .comp-cliente h5 { color: #e2e8f0; }
        body.dark-mode .comp-cliente-grid .label { color: #94a3b8; }
        body.dark-mode .comp-tabla thead { background: #0d1b3e; }
        body.dark-mode .comp-tabla tbody td { border-bottom-color: #2d3748; color: #cbd5e1; }
        body.dark-mode .comp-terminos { background: #0f1729; border-color: #2d3748; color: #94a3b8; }
        body.dark-mode .item-row td input { background: #0f1729; color: #e2e8f0; border-color: #2d3748; }

        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .comprobante-preview { box-shadow: none; padding: 20px; max-width: 100%; }
            .comp-tabla thead { background: #001845 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .comp-header { border-bottom-color: #001845 !important; }
            .comp-cliente { background: #f8faff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .comp-terminos { background: #f8faff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .comp-total { border-top-color: #001845 !important; }
            .item-row td input { border: none; padding: 2px; }
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

<div class="pos-wrapper no-print">
    <div class="page-header">
        <h1>🧾 Comprobante</h1>
        <p class="text-muted" style="font-size:0.9rem;">Completá los datos del cliente y los artículos para generar un comprobante imprimible.</p>
    </div>

    <div class="comprobante-preview" id="comprobantePreview">
        <div class="comp-header">
            <div>
                <?php if ($tiene_logo): ?>
                <img src="logo_comprobante.png?v=<?php echo filemtime(__DIR__ . '/logo_comprobante.png'); ?>" class="comp-logo" alt="Logo">
                <?php endif; ?>
            </div>
            <div class="comp-info">
                <h2><?php echo htmlspecialchars($taller_nombre); ?></h2>
                <?php if ($taller_direccion): ?>
                <p>📍 <?php echo htmlspecialchars($taller_direccion); ?></p>
                <?php endif; ?>
                <?php if ($taller_telefono): ?>
                <p>📞 <?php echo htmlspecialchars($taller_telefono); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="comp-cliente">
            <h5>👤 Datos del Cliente</h5>
            <div id="clienteDisplay" style="font-size:0.85rem;color:#475569;">
                <em>Completá el formulario abajo para ver los datos aquí.</em>
            </div>
        </div>

        <table class="comp-tabla" id="compTabla">
            <thead>
                <tr>
                    <th style="width:45%;">Descripción</th>
                    <th style="width:12%;text-align:center;">Cant.</th>
                    <th style="width:18%;text-align:right;">Precio Unit.</th>
                    <th style="width:18%;text-align:right;">Subtotal</th>
                    <th style="width:7%;"></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;font-weight:700;">TOTAL</td>
                    <td id="compTotal" style="text-align:right;font-weight:700;color:#10b981;font-size:1.1rem;">$0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($terminos): ?>
        <div class="comp-terminos">
            <strong>📋 Términos:</strong><br>
            <?php echo nl2br(htmlspecialchars($terminos)); ?>
        </div>
        <?php endif; ?>

        <div class="comp-footer">
            Comprobante generado el <?php echo date('d/m/Y H:i'); ?> por <?php echo htmlspecialchars($_SESSION['nombre']); ?>
        </div>
    </div>

    <div class="no-print" style="max-width:800px;margin:20px auto 0;">
        <div class="panel">
            <h2>✏️ Datos del Cliente</h2>
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre y Apellido</label>
                    <input type="text" id="cliente_nombre" class="form-control" placeholder="Juan Pérez" oninput="actualizarCliente()">
                </div>
                <div class="form-group">
                    <label>DNI / CUIT</label>
                    <input type="text" id="cliente_dni" class="form-control" placeholder="20.123.456-7" oninput="actualizarCliente()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" id="cliente_telefono" class="form-control" placeholder="+54 9 11 1234-5678" oninput="actualizarCliente()">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" id="cliente_direccion" class="form-control" placeholder="Calle 1234" oninput="actualizarCliente()">
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>📦 Artículos</h2>
            <button type="button" class="btn-guardar" onclick="agregarItem()" style="margin-bottom:12px;">➕ Agregar artículo</button>
            <table class="comp-tabla">
                <thead>
                    <tr>
                        <th style="width:45%;">Descripción</th>
                        <th style="width:12%;text-align:center;">Cant.</th>
                        <th style="width:18%;text-align:right;">Precio Unit.</th>
                        <th style="width:18%;text-align:right;">Subtotal</th>
                        <th style="width:7%;"></th>
                    </tr>
                </thead>
                <tbody id="formItemsBody">
                </tbody>
            </table>
        </div>

        <div style="display:flex;gap:10px;margin-bottom:40px;">
            <button type="button" class="btn-primary" onclick="window.print()" style="flex:1;padding:14px;font-size:1.1rem;">🖨️ Imprimir Comprobante</button>
            <button type="button" class="btn-cancelar" onclick="limpiarFormulario()">🗑️ Limpiar</button>
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
        html += '<div class="comp-cliente-grid">';
        if (nombre) html += '<div><span class="label">Nombre:</span> ' + escapeHtml(nombre) + '</div>';
        if (dni) html += '<div><span class="label">DNI/CUIT:</span> ' + escapeHtml(dni) + '</div>';
        if (telefono) html += '<div><span class="label">Teléfono:</span> ' + escapeHtml(telefono) + '</div>';
        if (direccion) html += '<div><span class="label">Dirección:</span> ' + escapeHtml(direccion) + '</div>';
        html += '</div>';
    } else {
        html = '<em style="color:#94a3b8;">Completá el formulario abajo para ver los datos aquí.</em>';
    }
    document.getElementById('clienteDisplay').innerHTML = html;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function agregarItem(desc, cant, precio) {
    const idx = itemCount++;
    const tbody = document.getElementById('formItemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.id = 'item-row-' + idx;
    tr.innerHTML = `
        <td><input type="text" class="item-desc form-control" placeholder="Descripción" value="${escapeHtml(desc || '')}" oninput="actualizarItem(${idx})"></td>
        <td><input type="number" class="item-cant form-control" value="${cant || 1}" min="1" oninput="actualizarItem(${idx})" style="text-align:center;"></td>
        <td><input type="number" class="item-precio form-control" value="${precio || 0}" step="0.01" min="0" oninput="actualizarItem(${idx})" style="text-align:right;"></td>
        <td><input type="text" class="item-subtotal form-control" value="$0.00" readonly style="text-align:right;font-weight:600;color:#10b981;background:#f1f5f9;"></td>
        <td><button type="button" class="btn-remove-item" onclick="eliminarItem(${idx})">×</button></td>
    `;
    tbody.appendChild(tr);

    // Also add to preview
    const previewBody = document.getElementById('itemsBody');
    const previewTr = document.createElement('tr');
    previewTr.id = 'preview-item-' + idx;
    previewTr.innerHTML = `
        <td class="preview-desc">-</td>
        <td class="preview-cant" style="text-align:center;">-</td>
        <td class="preview-precio" style="text-align:right;">-</td>
        <td class="preview-subtotal" style="text-align:right;">-</td>
        <td></td>
    `;
    previewBody.appendChild(previewTr);

    if (desc) actualizarItem(idx);
    actualizarTotal();
}

function actualizarItem(idx) {
    const desc = document.querySelector('#item-row-' + idx + ' .item-desc').value;
    const cant = parseFloat(document.querySelector('#item-row-' + idx + ' .item-cant').value) || 0;
    const precio = parseFloat(document.querySelector('#item-row-' + idx + ' .item-precio').value) || 0;
    const subtotal = cant * precio;

    document.querySelector('#item-row-' + idx + ' .item-subtotal').value = '$' + subtotal.toFixed(2);

    document.querySelector('#preview-item-' + idx + ' .preview-desc').textContent = desc || '-';
    document.querySelector('#preview-item-' + idx + ' .preview-cant').textContent = cant || '-';
    document.querySelector('#preview-item-' + idx + ' .preview-precio').textContent = precio ? '$' + precio.toFixed(2) : '-';
    document.querySelector('#preview-item-' + idx + ' .preview-subtotal').textContent = subtotal ? '$' + subtotal.toFixed(2) : '-';

    actualizarTotal();
}

function eliminarItem(idx) {
    const row = document.getElementById('item-row-' + idx);
    if (row) row.remove();
    const preview = document.getElementById('preview-item-' + idx);
    if (preview) preview.remove();
    actualizarTotal();
}

function actualizarTotal() {
    let total = 0;
    document.querySelectorAll('#formItemsBody .item-row').forEach(row => {
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
    document.getElementById('formItemsBody').innerHTML = '';
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
