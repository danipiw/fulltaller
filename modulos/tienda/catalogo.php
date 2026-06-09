<?php
require_once __DIR__ . '/includes/conexion.php';

$cols = $conn->query("SHOW COLUMNS FROM pos_productos LIKE 'visible_en_tienda'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE pos_productos ADD COLUMN visible_en_tienda TINYINT DEFAULT 0");
    $conn->query("ALTER TABLE pos_productos ADD COLUMN tienda_descripcion TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE pos_productos ADD COLUMN tienda_imagen VARCHAR(255) DEFAULT NULL");
}
$conn->query("CREATE TABLE IF NOT EXISTS tienda_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB");

$tiene_activo = $conn->query("SHOW COLUMNS FROM pos_productos LIKE 'activo'")->num_rows > 0;

$config_s = [];
$r_s = $conn->query("SELECT clave, valor FROM configuracion");
if ($r_s) { while ($f_s = $r_s->fetch_assoc()) $config_s[$f_s['clave']] = $f_s['valor']; }
$taller_nombre = htmlspecialchars($config_s['taller_nombre'] ?? 'Taller');
$taller_telefono = htmlspecialchars($config_s['taller_telefono'] ?? '');

$activo_filter = $tiene_activo ? 'AND p.activo = 1' : '';
$productos = $conn->query("SELECT p.id, p.codigo, p.descripcion AS nombre, p.precio, p.costo, p.tienda_descripcion, p.tienda_imagen,
    (SELECT COUNT(*) FROM tienda_fotos WHERE producto_id = p.id) AS fotos_extra
    FROM pos_productos p WHERE p.visible_en_tienda = 1 $activo_filter ORDER BY p.codigo ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $taller_nombre; ?> — Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .nav-jb { background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%); padding: 0.3rem 1.5rem; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .nav-jb .nav-content { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: space-between; }
        .nav-jb .nav-brand { color: white; font-weight: 700; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .nav-jb .nav-brand img { height: 36px; width: auto; }
        .nav-jb .nav-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.9); padding: 0.4rem 0.9rem; border-radius: 6px; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .nav-jb .nav-btn:hover { background: rgba(255,255,255,0.2); color: white; }
        .cart-link { position: relative; cursor: pointer; }
        .cart-count { position: absolute; top: -6px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.65rem; border: 2px solid var(--jb-navy); }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .card-title { font-weight: 700; font-size: 0.9rem; color: var(--jb-navy); border-bottom: 1px solid #e9ecef; padding-bottom: 8px; margin-bottom: 12px; }
        .producto-card { border: 1px solid #e9ecef; border-radius: 12px; padding: 12px; transition: box-shadow .2s; cursor: pointer; }
        .producto-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .producto-img { width: 100%; height: 180px; object-fit: contain; border-radius: 8px; background: #f8f9fa; }
        .producto-nombre { font-weight: 600; font-size: 0.9rem; color: var(--jb-navy); margin-top: 8px; }
        .producto-precio { font-weight: 700; font-size: 1.1rem; color: #198754; }
        .producto-desc { font-size: 0.8rem; color: #6c757d; }
        .foto-thumb { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; border: 2px solid #dee2e6; cursor: pointer; transition: opacity .15s; }
        .foto-thumb:hover { opacity: 0.8; }
        @media (max-width: 576px) { .nav-jb { padding: 0.3rem 0.75rem; } .nav-jb .nav-brand { font-size: 0.95rem; } .nav-jb .nav-brand img { height: 30px; } }
    </style>
</head>
<body>

<nav class="nav-jb">
    <div class="nav-content">
        <a href="catalogo.php" class="nav-brand">
            <img src="../ordenes/logo.png" alt="" onerror="this.style.display='none'">
            <?php echo $taller_nombre; ?>
        </a>
        <div style="display:flex;align-items:center;gap:6px;">
            <span class="nav-btn cart-link" onclick="openCart()">
                <i class="bi bi-cart3"></i> <span id="cart-count-nav">0</span>
            </span>
        </div>
    </div>
</nav>

<div class="container-fluid py-3 px-3 px-md-4">

    <?php if ($productos && $productos->num_rows > 0): ?>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-bag me-1"></i>Tienda</div>
        <p class="text-muted" style="font-size:0.85rem;">Agregá productos a tu lista y consultanos por WhatsApp.</p>
        <div class="row g-3">
            <?php while ($p = $productos->fetch_assoc()):
                $fotos_extra = $conn->query("SELECT filename FROM tienda_fotos WHERE producto_id = " . $p['id'] . " ORDER BY orden ASC LIMIT 1");
                $foto_extra = $fotos_extra->fetch_assoc();
                $galeria = [];
                if ($p['tienda_imagen'] && file_exists("uploads/" . $p['tienda_imagen'])) $galeria[] = 'uploads/' . $p['tienda_imagen'];
                $r_fotos_all = $conn->query("SELECT filename FROM tienda_fotos WHERE producto_id = " . $p['id'] . " ORDER BY orden ASC");
                while ($fa = $r_fotos_all->fetch_assoc()) { if (!in_array('uploads/' . $fa['filename'], $galeria) && file_exists("uploads/" . $fa['filename'])) $galeria[] = 'uploads/' . $fa['filename']; }
                $img_src = count($galeria) > 0 ? $galeria[0] : '';
            ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="producto-card" onclick="abrirGaleria(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>', <?php echo $p['precio']; ?>)">
                    <?php if ($img_src): ?>
                    <img src="<?php echo $img_src; ?>" class="producto-img" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                    <?php else: ?>
                    <div class="producto-img d-flex align-items-center justify-content-center" style="background:#f8f9fa;"><i class="bi bi-box" style="font-size:2.5rem;color:#adb5bd;"></i></div>
                    <?php endif; ?>
                    <div class="producto-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <?php if ($p['tienda_descripcion']): ?><div class="producto-desc"><?php echo htmlspecialchars(mb_substr($p['tienda_descripcion'], 0, 80)); if (mb_strlen($p['tienda_descripcion']) > 80) echo '...'; ?></div><?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="producto-precio">$<?php echo number_format($p['precio'], 2); ?></span>
                        <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); addToCart(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>', <?php echo $p['precio']; ?>)"><i class="bi bi-cart-plus"></i></button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card p-3 text-center py-5 text-muted">
        <i class="bi bi-box-seam" style="font-size:3rem;"></i>
        <p class="mt-3 mb-0">Próximamente productos disponibles</p>
    </div>
    <?php endif; ?>

    <div class="text-center mt-4 mb-2" style="font-size:0.8rem;color:#6c757d;">
        <i class="bi bi-shield-check me-1"></i><?php echo $taller_nombre; ?>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="cart-offcanvas" style="--bs-offcanvas-width:380px;">
    <div class="offcanvas-header">
        <h6 class="offcanvas-title"><i class="bi bi-cart3 me-1"></i>Carrito</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="cart-items"></div>
        <hr>
        <div class="d-flex justify-content-between"><strong>Total:</strong><strong id="cart-total">$0.00</strong></div>
        <button class="btn btn-primary w-100 mt-3" onclick="checkout()"><i class="bi bi-whatsapp me-1"></i>Consultar por WhatsApp</button>
        <p class="text-muted mt-2" style="font-size:0.75rem;">Por ahora enviamos tu consulta por WhatsApp.</p>
    </div>
</div>

<div class="modal fade" id="modalGaleria" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGaleriaNombre"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="galeria-imagen-principal">
                    <img id="galeria-img" src="" style="max-width:100%;max-height:400px;object-fit:contain;border-radius:8px;">
                </div>
                <div id="galeria-thumbs" class="d-flex justify-content-center gap-2 mt-3 flex-wrap"></div>
                <div id="galeria-descripcion" class="mt-2 text-muted" style="font-size:0.9rem;"></div>
                <div class="mt-2">
                    <strong id="galeria-precio" class="text-success fs-5"></strong>
                </div>
                <button class="btn btn-primary mt-2" id="galeria-add-cart"><i class="bi bi-cart-plus"></i> Agregar al carrito</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const cart = JSON.parse(localStorage.getItem('tienda_cart') || '[]');
function saveCart() { localStorage.setItem('tienda_cart', JSON.stringify(cart)); updateCartUI(); }
function updateCartUI() {
    const count = cart.reduce((s, i) => s + i.cantidad, 0);
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    document.getElementById('cart-count-nav').textContent = count;
    document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
    const container = document.getElementById('cart-items');
    if (cart.length === 0) { container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-cart3" style="font-size:2rem;"></i><p class="mt-2">Carrito vacío</p></div>'; return; }
    container.innerHTML = cart.map((item, i) => `<div class="d-flex justify-content-between align-items-center border-bottom py-2"><div><div style="font-weight:600;font-size:0.9rem;">${item.nombre}</div><div style="font-size:0.8rem;color:#6c757d;">$${item.precio.toFixed(2)} x ${item.cantidad}</div></div><div class="d-flex align-items-center gap-2"><button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${i}, -1)">−</button><span style="font-weight:600;">${item.cantidad}</span><button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${i}, 1)">+</button><button class="btn btn-sm btn-outline-danger" onclick="removeItem(${i})"><i class="bi bi-trash"></i></button></div></div>`).join('');
}
function addToCart(id, nombre, precio) { const ex = cart.find(i => i.id == id); if (ex) ex.cantidad++; else cart.push({id, nombre, precio: parseFloat(precio), cantidad: 1}); saveCart(); }
function updateQty(i, d) { cart[i].cantidad += d; if (cart[i].cantidad <= 0) cart.splice(i, 1); saveCart(); }
function removeItem(i) { cart.splice(i, 1); saveCart(); }
function openCart() { new bootstrap.Offcanvas(document.getElementById('cart-offcanvas')).show(); }
function checkout() {
    if (cart.length === 0) return;
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    let msg = 'Hola, quiero comprar:%0A';
    cart.forEach(i => msg += `• ${i.nombre} x${i.cantidad} = $${(i.precio * i.cantidad).toFixed(2)}%0A`);
    msg += `%0ATotal: $${total.toFixed(2)}`;
    const wa = prompt('WhatsApp del taller:', '<?php echo htmlspecialchars($taller_telefono, ENT_QUOTES); ?>');
    if (wa) window.open('https://wa.me/' + wa.replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(msg), '_blank');
}

let galeriaImgs = [];
function abrirGaleria(id, nombre, precio) {
    document.getElementById('modalGaleriaNombre').textContent = nombre;
    document.getElementById('galeria-precio').textContent = '$' + parseFloat(precio).toFixed(2);
    document.getElementById('galeria-add-cart').onclick = function() { addToCart(id, nombre, precio); bootstrap.Modal.getInstance(document.getElementById('modalGaleria')).hide(); };
    galeriaImgs = [];
    document.getElementById('galeria-descripcion').textContent = '';
    fetch('ajax_fotos.php?id=' + id).then(r => r.json()).then(data => {
        if (data.imagen_principal) galeriaImgs.push('uploads/' + data.imagen_principal);
        (data.fotos || []).forEach(f => { if (f.filename) galeriaImgs.push('uploads/' + f.filename); });
        document.getElementById('galeria-descripcion').textContent = data.descripcion || '';
        renderGaleria();
    }).catch(() => renderGaleria());
    document.getElementById('galeria-thumbs').innerHTML = '<div class="text-muted py-3"><i class="bi bi-arrow-repeat"></i> Cargando...</div>';
    new bootstrap.Modal(document.getElementById('modalGaleria')).show();
}
function renderGaleria() {
    const thumbs = document.getElementById('galeria-thumbs');
    if (galeriaImgs.length === 0) { thumbs.innerHTML = '<div class="text-muted py-2">Sin imágenes.</div>'; document.getElementById('galeria-img').src = ''; return; }
    thumbs.innerHTML = galeriaImgs.map((src, i) => `<img src="${src}" class="foto-thumb" onclick="mostrarImagen(${i})" style="${i === 0 ? 'border-color:var(--jb-azul);' : ''}">`).join('');
    mostrarImagen(0);
}
function mostrarImagen(idx) {
    if (idx >= 0 && idx < galeriaImgs.length) {
        document.getElementById('galeria-img').src = galeriaImgs[idx];
        document.querySelectorAll('#galeria-thumbs img').forEach((img, i) => img.style.borderColor = i === idx ? 'var(--jb-azul)' : '#dee2e6');
    }
}
updateCartUI();
</script>
</body>
</html>
