<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
if (!$PUEDE_EDITAR_TIENDA) { header('Location: catalogo.php'); exit; }

$mensaje = '';
$error = '';

// Asegurar columnas en pos_productos
$cols = $conn->query("SHOW COLUMNS FROM pos_productos LIKE 'visible_en_tienda'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE pos_productos ADD COLUMN visible_en_tienda TINYINT DEFAULT 0");
    $conn->query("ALTER TABLE pos_productos ADD COLUMN tienda_descripcion TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE pos_productos ADD COLUMN tienda_imagen VARCHAR(255) DEFAULT NULL");
}

// Crear tabla tienda_fotos para multiples imagenes
$conn->query("CREATE TABLE IF NOT EXISTS tienda_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB");

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { $error = 'ID inválido.'; }
    elseif ($_POST['accion'] === 'toggle') {
        $conn->query("UPDATE pos_productos SET visible_en_tienda = NOT visible_en_tienda WHERE id = $id");
        $mensaje = 'Visibilidad cambiada.';
    } elseif ($_POST['accion'] === 'guardar') {
        $descripcion = trim($_POST['tienda_descripcion'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { $error = 'El nombre no puede estar vacío.'; }
        else {
            $imagen = null;
            if (isset($_FILES['tienda_imagen']) && $_FILES['tienda_imagen']['tmp_name']) {
                $ext = strtolower(pathinfo($_FILES['tienda_imagen']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['tienda_imagen']['tmp_name'], $upload_dir . $filename)) {
                        $imagen = $filename;
                        $r2 = $conn->query("SELECT tienda_imagen FROM pos_productos WHERE id = $id");
                        if ($f2 = $r2->fetch_assoc()) { if ($f2['tienda_imagen'] && file_exists($upload_dir . $f2['tienda_imagen'])) unlink($upload_dir . $f2['tienda_imagen']); }
                    }
                }
            }
            if ($imagen) {
                $stmt = $conn->prepare("UPDATE pos_productos SET descripcion=?, tienda_descripcion=?, tienda_imagen=? WHERE id=?");
                $stmt->bind_param("sssi", $nombre, $descripcion, $imagen, $id);
            } else {
                $stmt = $conn->prepare("UPDATE pos_productos SET descripcion=?, tienda_descripcion=? WHERE id=?");
                $stmt->bind_param("ssi", $nombre, $descripcion, $id);
            }
            $stmt->execute();
            $mensaje = 'Producto actualizado.';
        }
    } elseif ($_POST['accion'] === 'quitar_imagen') {
        $r = $conn->query("SELECT tienda_imagen FROM pos_productos WHERE id = $id");
        if ($f = $r->fetch_assoc()) { if ($f['tienda_imagen'] && file_exists($upload_dir . $f['tienda_imagen'])) unlink($upload_dir . $f['tienda_imagen']); }
        $conn->query("UPDATE pos_productos SET tienda_imagen = NULL WHERE id = $id");
        $mensaje = 'Imagen principal eliminada.';
    } elseif ($_POST['accion'] === 'agregar_foto') {
        if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['tmp_name']) {
            $ext = strtolower(pathinfo($_FILES['nueva_foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = 'foto_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $upload_dir . $filename)) {
                    $max_ord = $conn->query("SELECT COALESCE(MAX(orden),0)+1 AS nxt FROM tienda_fotos WHERE producto_id = $id");
                    $ord = $max_ord->fetch_assoc()['nxt'];
                    $stmt_foto = $conn->prepare("INSERT INTO tienda_fotos (producto_id, filename, orden) VALUES (?,?,?)");
                    $stmt_foto->bind_param("iss", $id, $filename, $ord);
                    $stmt_foto->execute();
                    $mensaje = 'Foto agregada.';
                }
            }
        }
    } elseif ($_POST['accion'] === 'eliminar_foto') {
        $fid = (int)($_POST['foto_id'] ?? 0);
        $rf = $conn->query("SELECT filename FROM tienda_fotos WHERE id = $fid AND producto_id = $id");
        if ($ff = $rf->fetch_assoc()) { if ($ff['filename'] && file_exists($upload_dir . $ff['filename'])) unlink($upload_dir . $ff['filename']); }
        $conn->query("DELETE FROM tienda_fotos WHERE id = $fid");
        $mensaje = 'Foto eliminada.';
    }
}

$productos = $conn->query("SELECT id, codigo, descripcion AS nombre, precio, costo, activo, visible_en_tienda, tienda_descripcion, tienda_imagen FROM pos_productos ORDER BY visible_en_tienda DESC, codigo ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda — Administrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding-top: 70px; }
        .nav-top { background: linear-gradient(135deg, var(--jb-navy), var(--jb-azul-oscuro), var(--jb-azul)); color: white; padding: 12px 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .nav-top h5 { margin: 0; font-weight: 700; flex: 1; font-size: 1rem; }
        .nav-top a { color: rgba(255,255,255,0.8); text-decoration: none; }
        .nav-top a:hover { color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .card-title { font-weight: 700; font-size: 0.9rem; color: var(--jb-navy); border-bottom: 1px solid #e9ecef; padding-bottom: 8px; margin-bottom: 12px; }
        .table th { font-weight: 600; font-size: 0.85rem; border-top: none; }
        .prod-img { width: 48px; height: 48px; object-fit: contain; border-radius: 8px; background: #f8f9fa; border: 1px solid #e9ecef; }
        .tienda-on { border-left: 4px solid #198754; }
        .tienda-off { border-left: 4px solid #dee2e6; opacity: 0.7; }
        .toggle-btn { min-width: 72px; }
        form.d-inline { display: inline; }
    </style>
</head>
<body>
<div class="nav-top">
    <a href="../ordenes/listado.php"><i class="bi bi-arrow-left"></i></a>
    <h5><i class="bi bi-shop me-2"></i>Tienda — Productos</h5>
    <a href="index.php" class="btn btn-sm btn-outline-light" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
    <a href="catalogo.php" class="btn btn-sm btn-outline-light" target="_blank" title="Ver catálogo público"><i class="bi bi-eye"></i></a>
</div>
<div class="container-fluid py-3">
    <?php if ($mensaje): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="card p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:54px;"></th><th>Código</th><th>Nombre</th><th>Precio</th>
                        <?php if ($ES_ADMIN): ?><th>Costo</th><?php endif; ?>
                        <th>Descripción tienda</th><th style="width:90px;">Visible</th><th style="width:80px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($productos && $productos->num_rows > 0): while ($p = $productos->fetch_assoc()):
                        $fotos_extra = $conn->query("SELECT id, filename FROM tienda_fotos WHERE producto_id = " . $p['id'] . " ORDER BY orden ASC");
                        $total_fotos = ($p['tienda_imagen'] ? 1 : 0) + $fotos_extra->num_rows;
                    ?>
                    <tr class="<?php echo $p['visible_en_tienda'] ? 'tienda-on' : 'tienda-off'; ?>">
                        <td>
                            <?php if ($p['tienda_imagen'] && file_exists("uploads/" . $p['tienda_imagen'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($p['tienda_imagen']); ?>" class="prod-img" alt="">
                            <?php else: ?>
                            <div class="prod-img d-flex align-items-center justify-content-center"><i class="bi bi-box text-muted"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($p['codigo']); ?></code></td>
                        <td><strong><?php echo htmlspecialchars($p['nombre']); ?> <?php if ($total_fotos > 1): ?><span class="badge bg-secondary" style="font-size:0.65rem;"><?php echo $total_fotos; ?> fotos</span><?php endif; ?></strong></td>
                        <td>$<?php echo number_format($p['precio'], 2); ?></td>
                        <?php if ($ES_ADMIN): ?>
                        <td><small class="text-muted">$<?php echo number_format($p['costo'] ?? 0, 2); ?></small></td>
                        <?php endif; ?>
                        <td><small class="text-muted"><?php echo htmlspecialchars(mb_substr($p['tienda_descripcion'] ?? '', 0, 60)); if (mb_strlen($p['tienda_descripcion'] ?? '') > 60) echo '...'; ?></small></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button class="btn btn-sm toggle-btn <?php echo $p['visible_en_tienda'] ? 'btn-success' : 'btn-secondary'; ?>">
                                    <?php echo $p['visible_en_tienda'] ? 'Sí' : 'No'; ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="abrirModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['tienda_descripcion'] ?? '', ENT_QUOTES); ?>', <?php echo $p['precio']; ?>, '<?php echo htmlspecialchars($p['codigo'], ENT_QUOTES); ?>', <?php echo $p['costo'] ?? 0; ?>, '<?php echo htmlspecialchars($p['tienda_imagen'] ?? '', ENT_QUOTES); ?>')"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="<?php echo $ES_ADMIN ? 8 : 7; ?>" class="text-center py-4 text-muted">No hay productos cargados en POS.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-end mt-2">
        <small class="text-muted">Los productos se administran desde <a href="../pos/productos.php">POS → Productos</a>. Acá solo definís visibilidad, descripción y fotos para la tienda.</small>
    </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar producto para tienda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" id="prod-id" value="0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2"><label class="form-label small">Código</label><input type="text" id="prod-codigo" class="form-control" disabled></div>
                        <div class="mb-2"><label class="form-label small">Nombre *</label><input type="text" name="nombre" id="prod-nombre" class="form-control" required></div>
                        <div class="mb-2"><label class="form-label small">Precio venta</label><input type="text" id="prod-precio" class="form-control" disabled></div>
                        <?php if ($ES_ADMIN): ?>
                        <div class="mb-2"><label class="form-label small">Costo</label><input type="text" id="prod-costo" class="form-control" disabled></div>
                        <?php endif; ?>
                        <div class="mb-2"><label class="form-label small">Descripción para la tienda</label><textarea name="tienda_descripcion" id="prod-desc" class="form-control" rows="3" placeholder="Opcional: descripción que verá el cliente"></textarea></div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label small">Foto principal</label>
                            <input type="file" name="tienda_imagen" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">Dejá vacío para mantener la actual.</small>
                            <div id="imagen-actual" class="mt-1" style="display:none;">
                                <img src="" id="img-preview" style="max-width:100%;max-height:120px;border-radius:6px;border:1px solid #dee2e6;">
                                <button type="submit" formaction="" name="accion" value="quitar_imagen" class="btn btn-sm btn-outline-danger mt-1" onclick="return confirm('¿Quitar imagen principal?')"><i class="bi bi-trash"></i> Quitar</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Fotos adicionales</label>
                            <div id="fotos-extra-container"></div>
                            <hr class="my-2">
                            <label class="form-label small">Agregar foto</label>
                            <input type="file" name="nueva_foto" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                            <button type="submit" class="btn btn-sm btn-outline-success mt-1" onclick="this.form.accion.value='agregar_foto'"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let fotosExtra = [];

function abrirModal(id, nombre, desc, precio, codigo, costo, imagen) {
    document.getElementById('prod-id').value = id || 0;
    document.getElementById('prod-codigo').value = codigo || '';
    document.getElementById('prod-nombre').value = nombre || '';
    document.getElementById('prod-precio').value = precio ? '$' + parseFloat(precio).toFixed(2) : '';
    <?php if ($ES_ADMIN): ?>document.getElementById('prod-costo').value = costo ? '$' + parseFloat(costo).toFixed(2) : '';<?php endif; ?>
    document.getElementById('prod-desc').value = desc || '';
    document.getElementById('imagen-actual').style.display = 'none';
    if (imagen) {
        document.getElementById('img-preview').src = 'uploads/' + imagen;
        document.getElementById('imagen-actual').style.display = 'block';
    }
    // Cargar fotos extra vía AJAX
    fotosExtra = [];
    fetch('ajax_fotos.php?id=' + id).then(r => r.json()).then(data => {
        fotosExtra = data.fotos || [];
        renderFotosExtra(id);
    }).catch(() => fotosExtra = []);
    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

function renderFotosExtra(prodId) {
    const container = document.getElementById('fotos-extra-container');
    if (fotosExtra.length === 0) {
        container.innerHTML = '<small class="text-muted">Sin fotos adicionales.</small>';
        return;
    }
    container.innerHTML = '<div class="d-flex flex-wrap gap-2">' + fotosExtra.map(f =>
        '<div style="position:relative;width:80px;height:80px;">' +
        '<img src="uploads/' + encodeURIComponent(f.filename) + '" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">' +
        '<form method="POST" style="position:absolute;top:-6px;right:-6px;">' +
        '<input type="hidden" name="accion" value="eliminar_foto">' +
        '<input type="hidden" name="id" value="' + prodId + '">' +
        '<input type="hidden" name="foto_id" value="' + f.id + '">' +
        '<button class="btn btn-sm btn-danger" style="padding:0 4px;font-size:0.6rem;border-radius:50%;line-height:1;" onclick="return confirm(\'¿Eliminar esta foto?\')">&times;</button>' +
        '</form></div>'
    ).join('') + '</div>';
}
</script>
</body>
</html>
