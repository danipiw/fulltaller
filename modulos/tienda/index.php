<?php
require_once __DIR__ . '/includes/conexion.php';

// Asegurar columnas en pos_productos
$cols = $conn->query("SHOW COLUMNS FROM pos_productos LIKE 'visible_en_tienda'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE pos_productos ADD COLUMN visible_en_tienda TINYINT DEFAULT 0");
    $conn->query("ALTER TABLE pos_productos ADD COLUMN tienda_descripcion TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE pos_productos ADD COLUMN tienda_imagen VARCHAR(255) DEFAULT NULL");
}

$config_s = [];
$r_s = $conn->query("SELECT clave, valor FROM configuracion");
if ($r_s) { while ($f_s = $r_s->fetch_assoc()) $config_s[$f_s['clave']] = $f_s['valor']; }
$taller_nombre = htmlspecialchars($config_s['taller_nombre'] ?? 'Taller');

$orden = null;
$error = '';
$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $conn->prepare("SELECT o.*, c.nombre AS cliente_nombre, c.dni, c.telefono FROM ordenes o INNER JOIN clientes c ON o.cliente_id = c.id WHERE o.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $orden = $stmt->get_result()->fetch_assoc();
    if (!$orden) $error = 'Token inválido o orden no encontrada.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_buscar = trim($_POST['id'] ?? '');
    $dni_buscar = trim($_POST['dni'] ?? '');
    if ($id_buscar && $dni_buscar) {
        $stmt = $conn->prepare("SELECT o.*, c.nombre AS cliente_nombre, c.dni, c.telefono FROM ordenes o INNER JOIN clientes c ON o.cliente_id = c.id WHERE o.id = ? AND c.dni = ?");
        $stmt->bind_param("is", $id_buscar, $dni_buscar);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        if (!$orden) $error = 'No encontramos una orden con ese N° y DNI.';
    } else { $error = 'Completá el N° de orden y el DNI.'; }
}

$productos = $conn->query("SELECT id, codigo, descripcion AS nombre, precio, tienda_descripcion, tienda_imagen FROM pos_productos WHERE visible_en_tienda = 1 AND activo = 1 ORDER BY codigo ASC");

if ($orden):
    $orden_id = $orden['id'];
    $historial = $conn->prepare("SELECT * FROM estados_log WHERE orden_id = ? ORDER BY fecha DESC");
    $historial->bind_param("i", $orden_id); $historial->execute();
    $historial_result = $historial->get_result();
    $fotos = $conn->prepare("SELECT filename FROM fotos WHERE orden_id = ?");
    $fotos->bind_param("i", $orden_id); $fotos->execute();
    $fotos_result = $fotos->get_result();
    $badge_color = match ($orden['estado']) {
        'INGRESADO' => 'bg-secondary', 'EN REVISION' => 'bg-info text-dark',
        'EN ESPERA' => 'bg-warning text-dark', 'APROBADO' => 'bg-primary',
        'PRESUPUESTO RECHAZADO' => 'bg-danger', 'REPARADO' => 'bg-success',
        'SIN REPARACION' => 'bg-dark', 'ENTREGADO' => 'bg-success',
        default => 'bg-secondary'
    };
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden #<?php echo $orden_id; ?> — <?php echo $taller_nombre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .header-taller { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 16px 0; text-align: center; }
        .header-taller h5 { margin: 0; font-weight: 700; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .card-title { font-weight: 700; font-size: 0.9rem; color: #1a1a2e; border-bottom: 1px solid #e9ecef; padding-bottom: 8px; margin-bottom: 12px; }
        .estado-badge { font-size: 0.85rem; padding: 6px 14px; border-radius: 20px; font-weight: 600; }
        .timeline { position: relative; padding-left: 28px; }
        .timeline::before { content: ''; position: absolute; left: 8px; top: 4px; bottom: 4px; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; padding-bottom: 16px; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-dot { position: absolute; left: -22px; top: 4px; width: 14px; height: 14px; border-radius: 50%; background: #1a1a2e; border: 2px solid white; }
        .timeline-item:last-child .timeline-dot { background: #198754; }
        .timeline-date { font-size: 0.75rem; color: #6c757d; }
        .timeline-text { font-size: 0.85rem; font-weight: 500; }
        .foto-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6; }
        .info-label { font-size: 0.78rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
        .info-value { font-size: 0.95rem; font-weight: 500; color: #1a1a2e; }
        .presupuesto-ok { color: #198754; font-weight: 700; font-size: 1rem; }
        .producto-card { border: 1px solid #e9ecef; border-radius: 12px; padding: 12px; transition: box-shadow .2s; }
        .producto-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .producto-img { width: 100%; height: 140px; object-fit: contain; border-radius: 8px; background: #f8f9fa; }
        .producto-nombre { font-weight: 600; font-size: 0.9rem; color: #1a1a2e; margin-top: 8px; }
        .producto-precio { font-weight: 700; font-size: 1.1rem; color: #198754; }
        .producto-desc { font-size: 0.8rem; color: #6c757d; }
        .badge-carrito { position: fixed; top: 16px; right: 16px; z-index: 999; background: #dc3545; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .footer-link { text-align: center; margin-top: 32px; padding: 16px; font-size: 0.8rem; color: #6c757d; }
        @media (max-width: 576px) { .header-taller { padding: 12px 0; } .header-taller h5 { font-size: 1rem; } }
    </style>
</head>
<body>
<div class="header-taller"><h5><i class="bi bi-tools me-2"></i><?php echo $taller_nombre; ?></h5></div>
<div class="container py-4" style="max-width: 820px;">
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0" style="font-weight:700;">Orden #<?php echo $orden_id; ?></h6>
            <span class="estado-badge <?php echo $badge_color; ?>"><?php echo htmlspecialchars($orden['estado']); ?></span>
        </div>
        <div class="text-end" style="font-size:0.8rem;color:#6c757d;">Ingreso: <?php echo date('d/m/Y H:i', strtotime($orden['fecha_ingreso'])); ?></div>
    </div>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-person me-1"></i>Cliente</div>
        <div class="row g-2">
            <div class="col-6"><div class="info-label">Nombre</div><div class="info-value"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></div></div>
            <div class="col-6"><div class="info-label">Teléfono</div><div class="info-value"><?php echo htmlspecialchars($orden['telefono']); ?></div></div>
        </div>
    </div>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-phone me-1"></i>Equipo</div>
        <div class="row g-2">
            <div class="col-6"><div class="info-label">Tipo</div><div class="info-value"><?php echo htmlspecialchars($orden['tipo']); ?></div></div>
            <div class="col-6"><div class="info-label">Marca</div><div class="info-value"><?php echo htmlspecialchars($orden['marca']); ?></div></div>
            <div class="col-6"><div class="info-label">Modelo</div><div class="info-value"><?php echo htmlspecialchars($orden['modelo']); ?></div></div>
            <?php if ($orden['imei']): ?><div class="col-6"><div class="info-label">IMEI</div><div class="info-value"><?php echo htmlspecialchars($orden['imei']); ?></div></div><?php endif; ?>
        </div>
    </div>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-wrench me-1"></i>Detalle del Trabajo</div>
        <div class="info-label mb-1">Falla reportada</div>
        <div class="info-value mb-3"><?php echo nl2br(htmlspecialchars($orden['falla'])); ?></div>
        <?php if (!empty($orden['observaciones'])): ?><div class="info-label mb-1">Observaciones</div><div class="info-value"><?php echo nl2br(htmlspecialchars($orden['observaciones'])); ?></div><?php endif; ?>
    </div>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-currency-dollar me-1"></i>Presupuesto</div>
        <div class="row g-2">
            <div class="col-6"><div class="info-label">Presupuesto</div><div class="presupuesto-ok"><?php echo $orden['presupuesto'] ? '$' . number_format($orden['presupuesto'], 2) : 'Pendiente'; ?></div></div>
            <div class="col-6"><div class="info-label">Seña</div><div class="info-value"><?php echo $orden['sena'] > 0 ? '$' . number_format($orden['sena'], 2) : '—'; ?></div></div>
        </div>
    </div>
    <?php if ($fotos_result->num_rows > 0): ?>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-images me-1"></i>Fotos</div>
        <div class="d-flex gap-2 flex-wrap">
            <?php while ($foto = $fotos_result->fetch_assoc()): ?>
            <a href="../ordenes/uploads/<?php echo htmlspecialchars($foto['filename']); ?>" target="_blank"><img src="../ordenes/uploads/<?php echo htmlspecialchars($foto['filename']); ?>" class="foto-thumb" alt="Foto"></a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($historial_result->num_rows > 0): ?>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-clock-history me-1"></i>Historial de movimientos</div>
        <div class="timeline">
            <?php while ($h = $historial_result->fetch_assoc()): ?>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-text"><?php echo htmlspecialchars($h['estado']); ?></div><div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($h['fecha'])); ?> — <?php echo htmlspecialchars($h['cambiado_por_usuario'] ?? $h['cambiado_por'] ?? ''); ?></div></div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($productos && $productos->num_rows > 0): ?>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-bag me-1"></i>Tienda — Accesorios y más</div>
        <p class="text-muted" style="font-size:0.85rem;">Aprovechá y llevate algún accesorio para tu equipo.</p>
        <div class="row g-3">
            <?php while ($p = $productos->fetch_assoc()): ?>
            <div class="col-6 col-md-4">
                <div class="producto-card">
                    <?php if ($p['tienda_imagen'] && file_exists("uploads/" . $p['tienda_imagen'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($p['tienda_imagen']); ?>" class="producto-img" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                    <?php else: ?>
                    <div class="producto-img d-flex align-items-center justify-content-center" style="background:#f8f9fa;"><i class="bi bi-box" style="font-size:2.5rem;color:#adb5bd;"></i></div>
                    <?php endif; ?>
                    <div class="producto-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <?php if ($p['tienda_descripcion']): ?><div class="producto-desc"><?php echo htmlspecialchars($p['tienda_descripcion']); ?></div><?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="producto-precio">$<?php echo number_format($p['precio'], 2); ?></span>
                        <button class="btn btn-sm btn-outline-primary add-to-cart" data-id="<?php echo $p['id']; ?>" data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>" data-precio="<?php echo $p['precio']; ?>"><i class="bi bi-cart-plus"></i></button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer-link"><i class="bi bi-shield-check me-1"></i><?php echo $taller_nombre; ?></div>
</div>

<div class="badge-carrito" id="cart-badge" onclick="openCart()" style="display:none;"><span id="cart-count">0</span></div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="cart-offcanvas" style="--bs-offcanvas-width:360px;">
    <div class="offcanvas-header">
        <h6 class="offcanvas-title"><i class="bi bi-cart me-1"></i>Carrito</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="cart-items"></div>
        <hr>
        <div class="d-flex justify-content-between"><strong>Total:</strong><strong id="cart-total">$0.00</strong></div>
        <button class="btn btn-primary w-100 mt-3" onclick="checkout()"><i class="bi bi-whatsapp me-1"></i>Consultar por WhatsApp</button>
        <p class="text-muted mt-2" style="font-size:0.75rem;">Por ahora enviamos tu consulta por WhatsApp. Pronto podrás pagar online.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const cart = JSON.parse(localStorage.getItem('tienda_cart') || '[]');
function saveCart() { localStorage.setItem('tienda_cart', JSON.stringify(cart)); updateCartUI(); }
function updateCartUI() {
    const count = cart.reduce((s, i) => s + i.cantidad, 0);
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    document.getElementById('cart-count').textContent = count;
    document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
    document.getElementById('cart-badge').style.display = count > 0 ? 'flex' : 'none';
    const container = document.getElementById('cart-items');
    if (cart.length === 0) { container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-cart" style="font-size:2rem;"></i><p class="mt-2">Carrito vacío</p></div>'; return; }
    container.innerHTML = cart.map((item, i) => `<div class="d-flex justify-content-between align-items-center border-bottom py-2"><div><div style="font-weight:600;font-size:0.9rem;">${item.nombre}</div><div style="font-size:0.8rem;color:#6c757d;">$${item.precio.toFixed(2)} x ${item.cantidad}</div></div><div class="d-flex align-items-center gap-2"><button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${i}, -1)">−</button><span style="font-weight:600;">${item.cantidad}</span><button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${i}, 1)">+</button><button class="btn btn-sm btn-outline-danger" onclick="removeItem(${i})"><i class="bi bi-trash"></i></button></div></div>`).join('');
}
function addToCart(id, nombre, precio) { const ex = cart.find(i => i.id == id); if (ex) ex.cantidad++; else cart.push({id, nombre, precio: parseFloat(precio), cantidad: 1}); saveCart(); openCart(); }
function updateQty(i, d) { cart[i].cantidad += d; if (cart[i].cantidad <= 0) cart.splice(i, 1); saveCart(); }
function removeItem(i) { cart.splice(i, 1); saveCart(); }
function openCart() { new bootstrap.Offcanvas(document.getElementById('cart-offcanvas')).show(); }
function checkout() {
    if (cart.length === 0) return;
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    let msg = 'Hola, quiero comprar:%0A';
    cart.forEach(i => msg += `• ${i.nombre} x${i.cantidad} = $${(i.precio * i.cantidad).toFixed(2)}%0A`);
    msg += `%0ATotal: $${total.toFixed(2)}%0A%0AOrden #<?php echo $orden_id; ?>`;
    const wa = prompt('WhatsApp del taller:', '<?php echo htmlspecialchars($config_s['taller_telefono'] ?? ''); ?>');
    if (wa) window.open(`https://wa.me/${wa.replace(/[^0-9]/g, '')}?text=${msg}`, '_blank');
}
document.querySelectorAll('.add-to-cart').forEach(b => b.addEventListener('click', function() { addToCart(this.dataset.id, this.dataset.nombre, this.dataset.precio); }));
updateCartUI();
</script>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento — <?php echo $taller_nombre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; }
        .buscar-card { background: white; border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px 32px; }
        .buscar-card h4 { font-weight: 700; color: #1a1a2e; text-align: center; margin-bottom: 4px; }
        .buscar-card .subtitle { text-align: center; color: #6c757d; font-size: 0.9rem; margin-bottom: 28px; }
        .header-taller { background: transparent; color: white; text-align: center; position: fixed; top: 0; left: 0; right: 0; z-index: 10; padding: 16px; }
        .header-taller h5 { margin: 0; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .btn-primary { background: linear-gradient(135deg, #0f3460, #1a1a2e); border: none; padding: 12px; font-weight: 600; border-radius: 12px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,52,96,0.3); }
        .footer-link { text-align: center; margin-top: 20px; font-size: 0.8rem; color: rgba(255,255,255,0.6); }
        @media (max-width: 480px) { .buscar-card { padding: 28px 20px; border-radius: 16px; } }
    </style>
</head>
<body>
<div class="header-taller"><h5><i class="bi bi-tools me-2"></i><?php echo $taller_nombre; ?></h5></div>
<div class="container" style="max-width:460px;padding-top:60px;">
    <div class="buscar-card">
        <h4>Consultá tu orden</h4>
        <p class="subtitle">Ingresá el número de orden y tu DNI para ver el estado.</p>
        <?php if ($error): ?><div class="alert alert-danger py-2" style="font-size:0.9rem;"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3"><label class="form-label" style="font-weight:600;font-size:0.85rem;">N° de Orden</label><input type="text" name="id" class="form-control form-control-lg" placeholder="Ej: 123" required></div>
            <div class="mb-3"><label class="form-label" style="font-weight:600;font-size:0.85rem;">DNI</label><input type="text" name="dni" class="form-control form-control-lg" placeholder="DNI del titular" required></div>
            <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-search me-1"></i>Consultar</button>
        </form>
    </div>
    <div class="footer-link"><i class="bi bi-shield-check me-1"></i><?php echo $taller_nombre; ?></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
