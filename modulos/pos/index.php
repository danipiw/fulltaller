<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db_caja = getDB();
$user_id_caja = (int)$_SESSION['usuario_id'];

$check_user = $db_caja->query("SELECT id FROM usuarios WHERE id = $user_id_caja AND activo = 1");
if (!$check_user || $check_user->num_rows === 0) {
    $db_caja->close(); session_destroy(); header('Location: login.php'); exit;
}

$hoy_caja = date('Y-m-d');

// Verificar caja abierta
$caja_res = $db_caja->query("SELECT * FROM pos_caja WHERE usuario_id = $user_id_caja AND fecha_apertura = '$hoy_caja' AND estado = 'abierta'");
$caja_activa = $caja_res ? $caja_res->fetch_assoc() : null;

if (!$caja_activa) {
    header('Location: inicio_caja.php');
    exit;
}

$caja_id = (int)$caja_activa['id'];
$monto_inicial = (float)$caja_activa['monto_inicial'];

// Calcular saldo esperado
$r_ventas = $db_caja->query("SELECT COALESCE(SUM(total), 0) as total FROM pos_ventas WHERE usuario_id = $user_id_caja AND DATE(created_at) = '$hoy_caja' AND metodo_pago = 'efectivo' AND anulada = 0");
$total_ventas_caja = (float)$r_ventas->fetch_assoc()['total'];

$r_ing = $db_caja->query("SELECT COALESCE(SUM(monto), 0) as total FROM pos_caja_movimientos WHERE caja_id = $caja_id AND tipo = 'ingreso'");
$total_ingresos_caja = (float)$r_ing->fetch_assoc()['total'];

$r_egr = $db_caja->query("SELECT COALESCE(SUM(monto), 0) as total FROM pos_caja_movimientos WHERE caja_id = $caja_id AND tipo = 'egreso'");
$total_egresos_caja = (float)$r_egr->fetch_assoc()['total'];

$saldo_caja = $monto_inicial + $total_ventas_caja + $total_ingresos_caja - $total_egresos_caja;
$db_caja->close();

// Asegurar que exista el producto COMUN
$db_temp = getDB();
$db_temp->query("INSERT IGNORE INTO pos_productos (codigo, descripcion, precio, stock) VALUES ('COMUN', 'Producto Común', 0, 999999)");
$r_comun = $db_temp->query("SELECT id FROM pos_productos WHERE codigo = 'COMUN'");
$comun_id = $r_comun ? (int)$r_comun->fetch_assoc()['id'] : 0;
$db_temp->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cobrar'])) {
    $db = getDB();
    $items = json_decode($_POST['items_json'], true);
    $total = floatval($_POST['total']);
    $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
    $usuario_id = $_SESSION['usuario_id'];

    $stmt = $db->prepare('INSERT INTO pos_ventas (usuario_id, total, items, metodo_pago) VALUES (?, ?, ?, ?)');
    $num_items = count($items);
    $stmt->bind_param('idis', $usuario_id, $total, $num_items, $metodo_pago);
    $stmt->execute();
    $venta_id = $db->insert_id;
    $stmt->close();

    $stmt = $db->prepare('INSERT INTO pos_venta_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($items as $item) {
        $desc = $item['descripcion_custom'] ?? null;
        $stmt->bind_param('iisidd', $venta_id, $item['id'], $desc, $item['cantidad'], $item['precio'], $item['subtotal']);
        $stmt->execute();
        if ($item['id'] != $comun_id) {
            $db->query("UPDATE pos_productos SET stock = stock - {$item['cantidad']} WHERE id = {$item['id']}");
        }
    }
    $stmt->close();
    $db->close();

    $venta_exitosa = true;
    $venta_id_success = $venta_id;

    if (isset($_POST['imprimir']) && $_POST['imprimir'] === '1') {
        header("Location: ticket.php?id=$venta_id");
        exit;
    }


}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Punto de Venta</title>
    <link rel="manifest" href="manifest.php">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="../ordenes/icon.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script>if('serviceWorker'in navigator){navigator.serviceWorker.register('../sw.js').catch(function(){})}</script>
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
                <span class="nav-brand-text" style="color:white;font-size:0.95rem;font-weight:500;">Punto de venta</span>
            </a>
        </div>
        <div class="nav-center d-flex" style="align-items:center;">
            <a href="index.php" class="nav-btn active">🛒 Vender</a>
            <span class="nav-sep">|</span>
            <button type="button" class="nav-btn" onclick="abrirModalMovimiento('ingreso')">📥 Ingreso</button>
            <span class="nav-sep">|</span>
            <button type="button" class="nav-btn" onclick="abrirModalMovimiento('egreso')">📤 Egreso</button>
            <span class="nav-sep">|</span>
            <a href="corte_caja.php" class="nav-btn">🔒 Corte</a>
        </div>
        <div class="nav-right">
            <span class="caja-monto-nav" style="margin-right:8px;">💰 $<?php echo number_format($saldo_caja, 2); ?></span>
            <span class="rol-badge">
                <?php echo esAdminPOS() ? '👑' : '👤'; ?>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
        </div>
    </div>
</nav>

<div class="pos-wrapper pos-full">

    <?php if (isset($venta_exitosa) && (!isset($_POST['imprimir']) || $_POST['imprimir'] === '0')): ?>
    <div class="alert success" style="margin-bottom:12px;">✅ Venta #<?php echo $venta_id_success; ?> registrada por <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const waData = {
            items: <?php echo json_encode($items); ?>,
            total: <?php echo $total; ?>,
            metodo: '<?php echo $metodo_pago; ?>',
            ventaId: <?php echo $venta_id_success; ?>,
            cajero: '<?php echo htmlspecialchars($_SESSION['nombre']); ?>'
        };
        document.getElementById('wa-items').value = JSON.stringify(waData);
        document.getElementById('modal-whatsapp').style.display = 'flex';
        setTimeout(() => document.getElementById('wa-telefono').focus(), 300);
    });
    function cerrarWaModal() { document.getElementById('modal-whatsapp').style.display = 'none'; }
    function enviarWhatsApp() {
        const telefono = document.getElementById('wa-telefono').value.trim();
        if (!telefono) { alert('Ingresá el número de teléfono'); return; }
        const d = JSON.parse(document.getElementById('wa-items').value);
        let maxName = 0, texto = '🧾 *FullTaller POS*\n';
        texto += 'Venta #' + d.ventaId + ' - ' + new Date().toLocaleDateString('es-AR') + '\n';
        texto += 'Cajero: ' + d.cajero + '\n\n';
        d.items.forEach(i => { if (i.descripcion.length > maxName) maxName = i.descripcion.length; });
        if (maxName < 8) maxName = 8;
        const sep = '─'.repeat(maxName + 24) + '\n';
        texto += 'Producto' + ' '.repeat(Math.max(0, maxName - 7)) + '  Cant   Importe\n' + sep;
        d.items.forEach(i => {
            texto += i.descripcion + ' '.repeat(Math.max(0, maxName - i.descripcion.length + 2));
            texto += String(i.cantidad).padStart(5) + '  $' + i.subtotal.toFixed(2).padStart(7) + '\n';
        });
        texto += sep + 'TOTAL' + ' '.repeat(Math.max(0, maxName - 4)) + '       $' + d.total.toFixed(2).padStart(7) + '\n';
        const iconos = { efectivo: '💵', tarjeta: '💳', transferencia: '🏦' };
        texto += '\n' + (iconos[d.metodo] || '💵') + ' ' + d.metodo.charAt(0).toUpperCase() + d.metodo.slice(1) + '\n\n¡Gracias por su compra!';
        window.open('whatsapp://send?phone=' + telefono + '&text=' + encodeURIComponent(texto), '_blank');
    }
    </script>
    <?php endif; ?>

    <!-- Scan input: solo código -->
    <div class="scan-section">
        <div class="scan-simple">
            <label for="codigo">🔍 Código de producto</label>
            <div class="scan-input-group">
                <input type="text" id="codigo" placeholder="Escanear o escribir código y Enter..." autofocus autocomplete="off">
                <button type="button" class="btn-scan" onclick="escanearCodigo()">➤</button>
                <button type="button" class="btn-scan btn-comun" onclick="abrirModalProductoComun()" title="Producto común" style="margin-left:6px;">📋</button>
            </div>
            <div id="mensaje-toast" class="scan-toast" style="display:none;"></div>
        </div>
    </div>

    <!-- Cart -->
    <div class="panel cart-panel">
        <div class="cart-header-row">
            <h2 style="margin:0;">🛍️ Carrito</h2>
            <div>
                <span id="cart-count" class="cart-count">0</span>
            </div>
        </div>

        <div class="carrito-header">
            <span>Producto</span>
            <span style="text-align:center;">Cant</span>
            <span style="text-align:right;">Precio</span>
            <span style="text-align:right;">Subtotal</span>
            <span></span>
        </div>
        <div id="carrito-items" class="carrito-items">
            <p class="empty-cart">No hay productos en el carrito</p>
        </div>

        <div class="totales">
            <div class="total-line">
                <span>Items:</span>
                <span id="total-items">0</span>
            </div>
            <div class="total-line grand-total">
                <span>TOTAL</span>
                <span id="carrito-total">$0.00</span>
            </div>
        </div>

        <div class="cart-buttons">
            <button type="button" class="btn-cobrar" id="btn-cobrar" disabled onclick="abrirModalCobro()">
                💰 Cobrar
            </button>
            <button type="button" class="btn-limpiar" onclick="limpiarCarrito()">
                🗑️ Limpiar
            </button>
        </div>
    </div>
</div>

<!-- MODAL COBRO -->
<div id="modal-cobro" class="modal-overlay" style="display:none;" onclick="if(event.target===this)cerrarModalCobro()">
    <div class="modal-content">
        <h2>💰 Cobrar</h2>
        <div class="vuelto-total" id="modal-total">$0.00</div>

        <div class="form-group">
            <label>Método de pago</label>
            <select id="modal-metodo-pago" class="pago-select">
                <option value="efectivo">💵 Efectivo</option>
                <option value="tarjeta">💳 Tarjeta</option>
                <option value="transferencia">🏦 Transferencia</option>
            </select>
        </div>

        <div class="form-group" id="vuelto-section">
            <label>Monto recibido</label>
            <div class="vuelto-input-group">
                <span class="moneda">$</span>
                <input type="number" id="modal-recibido" step="0.01" min="0" placeholder="0.00" oninput="calcularVuelto()">
            </div>
            <div id="modal-vuelto" style="display:none;margin-top:8px;"></div>
        </div>

        <form method="POST" id="form-cobro" style="margin:0;">
            <input type="hidden" name="items_json" id="form-items-json">
            <input type="hidden" name="total" id="form-total">
            <input type="hidden" name="metodo_pago" id="form-metodo-pago">
            <input type="hidden" name="cobrar" value="1">
            <div class="modal-actions">
                <button type="submit" name="imprimir" value="1" class="btn-full btn-imprimir" onclick="return prepararCobro()">
                    🖨️ Imprimir ticket
                </button>
                <button type="submit" name="imprimir" value="0" class="btn-full btn-registrar" onclick="return prepararCobro()">
                    ✅ Registrar venta
                </button>
            </div>
        </form>

        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalCobro()">Cancelar</button>
    </div>
</div>

<script>
let carrito = [];
let productoActual = null;
let comunId = <?php echo $comun_id; ?>;

async function escanearCodigo() {
    const codigo = document.getElementById('codigo').value.trim();
    if (!codigo) return;
    try {
        const r = await fetch(`api.php?action=buscar&codigo=${encodeURIComponent(codigo)}`);
        const data = await r.json();
        if (data.success) {
            productoActual = data.producto;
            agregarAlCarrito();
        } else {
            mostrarToast('❌ ' + (data.message || 'Producto no encontrado'), 'error');
        }
    } catch (e) {
        mostrarToast('❌ Error de conexión', 'error');
    }
}

function mostrarToast(msg, tipo) {
    const t = document.getElementById('mensaje-toast');
    t.textContent = msg;
    t.className = 'scan-toast ' + (tipo === 'error' ? 'toast-error' : 'toast-success');
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 1500);
}

function agregarAlCarrito() {
    if (!productoActual) return;
    const idx = carrito.findIndex(item => item.id === productoActual.id);
    if (idx >= 0) {
        carrito[idx].cantidad += 1;
        carrito[idx].subtotal = carrito[idx].cantidad * carrito[idx].precio;
    } else {
        carrito.push({
            id: productoActual.id,
            codigo: productoActual.codigo,
            descripcion: productoActual.descripcion,
            precio: parseFloat(productoActual.precio),
            cantidad: 1,
            subtotal: parseFloat(productoActual.precio)
        });
    }
    actualizarCarrito();
    mostrarToast('✅ ' + productoActual.descripcion + ' agregado', 'success');
    // Aviso de stock bajo
    const nuevoStock = productoActual.stock - (carrito.find(i => i.id === productoActual.id)?.cantidad || 1);
    if (productoActual.id !== comunId && nuevoStock >= 0 && nuevoStock < 3) {
        const t = document.getElementById('mensaje-toast');
        t.textContent = '⚠️ Stock bajo: ' + productoActual.descripcion + ' (' + nuevoStock + ' restantes)';
        t.className = 'scan-toast toast-error';
        t.style.display = 'block';
        setTimeout(() => t.style.display = 'none', 3000);
    }
    document.getElementById('codigo').value = '';
    productoActual = null;
    document.getElementById('codigo').focus();
}

function actualizarCarrito() {
    const container = document.getElementById('carrito-items');
    const btn = document.getElementById('btn-cobrar');
    if (carrito.length === 0) {
        container.innerHTML = '<p class="empty-cart">No hay productos en el carrito</p>';
        btn.disabled = true;
        document.getElementById('cart-count').textContent = '0';
        document.getElementById('total-items').textContent = '0';
        document.getElementById('carrito-total').textContent = '$0.00';
        return;
    }
    let html = '', total = 0, items = 0;
    carrito.forEach((item, i) => {
        total += item.subtotal;
        items += item.cantidad;
        html += `<div class="carrito-item">
            <span class="desc">${item.descripcion}</span>
            <span class="cant">${item.cantidad}</span>
            <span class="precio">$${item.precio.toFixed(2)}</span>
            <span class="subtotal">$${item.subtotal.toFixed(2)}</span>
            <button type="button" class="btn-eliminar-item" onclick="eliminarItem(${i})">✕</button>
        </div>`;
    });
    container.innerHTML = html;
    document.getElementById('cart-count').textContent = items;
    document.getElementById('total-items').textContent = items;
    document.getElementById('carrito-total').textContent = '$' + total.toFixed(2);
    btn.disabled = false;
}

function eliminarItem(i) { carrito.splice(i, 1); actualizarCarrito(); }

function limpiarCarrito() {
    if (carrito.length === 0) return;
    if (confirm('¿Limpiar el carrito?')) { carrito = []; actualizarCarrito(); }
}

function abrirModalCobro() {
    if (carrito.length === 0) return;
    const total = carrito.reduce((s, i) => s + i.subtotal, 0);
    document.getElementById('modal-total').textContent = '$' + total.toFixed(2);
    document.getElementById('modal-recibido').value = '';
    document.getElementById('modal-vuelto').style.display = 'none';
    document.getElementById('modal-metodo-pago').value = 'efectivo';
    document.getElementById('vuelto-section').style.display = 'block';
    document.getElementById('modal-cobro').style.display = 'flex';
    setTimeout(() => document.getElementById('modal-recibido').focus(), 300);
}

function cerrarModalCobro() {
    document.getElementById('modal-cobro').style.display = 'none';
    document.getElementById('codigo').focus();
}

function calcularVuelto() {
    const total = carrito.reduce((s, i) => s + i.subtotal, 0);
    const recibido = parseFloat(document.getElementById('modal-recibido').value) || 0;
    const vuelto = recibido - total;
    const div = document.getElementById('modal-vuelto');
    if (recibido <= 0) { div.style.display = 'none'; return; }
    div.style.display = 'block';
    let cls = 'vuelto-resultado ', txt = '';
    if (vuelto < 0) { cls += 'negativo'; txt = '❌ Falta: $' + Math.abs(vuelto).toFixed(2); }
    else if (vuelto === 0) { cls += 'cero'; txt = '✅ Pago exacto'; }
    else { cls += 'positivo'; txt = '🔄 Vuelto: $' + vuelto.toFixed(2); }
    div.className = cls;
    div.textContent = txt;
}

function prepararCobro() {
    if (carrito.length === 0) return false;
    const metodo = document.getElementById('modal-metodo-pago').value;
    if (metodo === 'efectivo') {
        const total = carrito.reduce((s, i) => s + i.subtotal, 0);
        const recibido = parseFloat(document.getElementById('modal-recibido').value) || 0;
        if (recibido < total) { alert('El monto recibido es menor al total.'); return false; }
    }
    const total = carrito.reduce((s, i) => s + i.subtotal, 0);
    document.getElementById('form-items-json').value = JSON.stringify(carrito);
    document.getElementById('form-total').value = total;
    document.getElementById('form-metodo-pago').value = metodo;
    return true;
}

document.getElementById('codigo').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); escanearCodigo(); }
});

document.getElementById('modal-metodo-pago').addEventListener('change', function() {
    document.getElementById('vuelto-section').style.display = this.value === 'efectivo' ? 'block' : 'none';
});

// ===== CAJA: Ingreso / Egreso =====
let movimientoTipo = '';

function abrirModalMovimiento(tipo) {
    movimientoTipo = tipo;
    document.getElementById('movimiento-titulo').textContent = tipo === 'ingreso' ? '📥 Ingreso a Caja' : '📤 Egreso de Caja';
    document.getElementById('movimiento-label').textContent = tipo === 'ingreso' ? 'Monto a ingresar' : 'Monto a retirar';
    document.getElementById('movimiento-concepto').value = '';
    document.getElementById('movimiento-monto').value = '';
    document.getElementById('movimiento-error').style.display = 'none';
    document.getElementById('modal-movimiento').style.display = 'flex';
    setTimeout(() => document.getElementById('movimiento-concepto').focus(), 200);
}

function cerrarModalMovimiento() {
    document.getElementById('modal-movimiento').style.display = 'none';
}

async function guardarMovimiento() {
    const concepto = document.getElementById('movimiento-concepto').value.trim();
    const monto = parseFloat(document.getElementById('movimiento-monto').value) || 0;
    const errDiv = document.getElementById('movimiento-error');

    if (!concepto) {
        errDiv.textContent = 'El concepto es obligatorio.';
        errDiv.style.display = 'block';
        return;
    }
    if (monto <= 0) {
        errDiv.textContent = 'El monto debe ser mayor a 0.';
        errDiv.style.display = 'block';
        return;
    }

    const formData = new FormData();
    formData.append('tipo', movimientoTipo);
    formData.append('concepto', concepto);
    formData.append('monto', monto);

    try {
        const r = await fetch('api_caja.php?action=movimiento', {
            method: 'POST',
            body: formData
        });
        const data = await r.json();
        if (data.success) {
            cerrarModalMovimiento();
            location.reload();
        } else {
            errDiv.textContent = data.message;
            errDiv.style.display = 'block';
        }
    } catch (e) {
        errDiv.textContent = 'Error de conexión.';
        errDiv.style.display = 'block';
    }
}

// ===== PRODUCTO COMÚN =====
function abrirModalProductoComun() {
    document.getElementById('comun-error').style.display = 'none';
    document.getElementById('comun-descripcion').value = '';
    document.getElementById('comun-precio').value = '';
    document.getElementById('modal-comun').style.display = 'flex';
    setTimeout(() => document.getElementById('comun-descripcion').focus(), 200);
}

function cerrarModalProductoComun() {
    document.getElementById('modal-comun').style.display = 'none';
    document.getElementById('codigo').focus();
}

function agregarProductoComun() {
    const desc = document.getElementById('comun-descripcion').value.trim();
    const precio = parseFloat(document.getElementById('comun-precio').value) || 0;
    const errDiv = document.getElementById('comun-error');

    if (!desc) {
        errDiv.textContent = 'La descripción es obligatoria.';
        errDiv.style.display = 'block';
        return;
    }
    if (precio <= 0) {
        errDiv.textContent = 'El precio debe ser mayor a 0.';
        errDiv.style.display = 'block';
        return;
    }

    carrito.push({
        id: comunId,
        codigo: 'COMUN',
        descripcion: desc,
        descripcion_custom: desc,
        precio: precio,
        cantidad: 1,
        subtotal: precio
    });
    actualizarCarrito();
    mostrarToast('✅ ' + desc + ' agregado', 'success');
    cerrarModalProductoComun();
}
</script>

<!-- MODAL PRODUCTO COMÚN -->
<div id="modal-comun" class="modal-overlay" style="display:none;" onclick="if(event.target===this)cerrarModalProductoComun()">
    <div class="modal-content" style="max-width:400px;">
        <h2>📋 Producto Común</h2>
        <div id="comun-error" class="alert error" style="display:none;margin-bottom:12px;"></div>
        <div class="form-group">
            <label>Descripción</label>
            <input type="text" id="comun-descripcion" placeholder="Ej: Servicio de instalación..." style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:1rem;">
        </div>
        <div class="form-group">
            <label>Precio</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:1.3rem;font-weight:700;">$</span>
                <input type="number" id="comun-precio" step="0.01" min="0" placeholder="0.00" style="flex:1;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:1.2rem;">
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="button" class="btn-guardar" style="flex:1;" onclick="agregarProductoComun()">✅ Agregar</button>
            <button type="button" class="btn-cancelar" style="flex:1;" onclick="cerrarModalProductoComun()">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL INGRESO/EGRESO -->
<div id="modal-movimiento" class="modal-overlay" style="display:none;" onclick="if(event.target===this)cerrarModalMovimiento()">
    <div class="modal-content" style="max-width:400px;">
        <h2 id="movimiento-titulo">📥 Ingreso a Caja</h2>
        <div id="movimiento-error" class="alert error" style="display:none;margin-bottom:12px;"></div>
        <div class="form-group">
            <label>Concepto</label>
            <input type="text" id="movimiento-concepto" placeholder="Ej: Pago de servicio, compra de insumos..." style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:1rem;">
        </div>
        <div class="form-group">
            <label id="movimiento-label">Monto</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:1.3rem;font-weight:700;">$</span>
                <input type="number" id="movimiento-monto" step="0.01" min="0" placeholder="0.00" style="flex:1;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:1.2rem;">
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="button" class="btn-guardar" style="flex:1;" onclick="guardarMovimiento()">✅ Registrar</button>
            <button type="button" class="btn-cancelar" style="flex:1;" onclick="cerrarModalMovimiento()">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL WHATSAPP -->
<div id="modal-whatsapp" class="modal-overlay" style="display:none;" onclick="if(event.target===this)cerrarWaModal()">
    <div class="modal-content" style="max-width:400px;">
        <h2>📱 Enviar comprobante</h2>
        <input type="hidden" id="wa-items">
        <div class="form-group">
            <label>📞 Número de teléfono (con código de país)</label>
            <input type="tel" id="wa-telefono" placeholder="5491112345678" style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;">
            <button type="button" class="btn-guardar" style="flex:1;" onclick="enviarWhatsApp()">📤 Enviar</button>
            <button type="button" class="btn-cancelar" style="flex:1;" onclick="cerrarWaModal()">Cerrar</button>
        </div>
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
<?php require 'includes/api_token_script.php'; ?>
</body>
</html>
