<?php
require_once __DIR__ . '/includes/verificar_sesion.php';
if (!$ES_ADMIN) { header('Location: ../ordenes/listado.php?error=sin_acceso'); exit; }

$mensaje = '';
$error = '';

$conn->query("CREATE TABLE IF NOT EXISTS tienda_productos (
    id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(255) NOT NULL,
    descripcion TEXT, precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL, activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Cargar config
$config_s = [];
$r = $conn->query("SELECT clave, valor FROM configuracion");
if ($r) { while ($f = $r->fetch_assoc()) $config_s[$f['clave']] = $f['valor']; }

// Guardar config
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'config') {
    $seguimiento = ($_POST['seguimiento_activo'] ?? '0') === '1' ? '1' : '0';
    $tienda = ($_POST['tienda_activa'] ?? '0') === '1' ? '1' : '0';
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('seguimiento_activo', '$seguimiento') ON DUPLICATE KEY UPDATE valor = '$seguimiento'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('tienda_activa', '$tienda') ON DUPLICATE KEY UPDATE valor = '$tienda'");
    $config_s['seguimiento_activo'] = $seguimiento;
    $config_s['tienda_activa'] = $tienda;
    $mensaje = 'Configuración guardada.';
}

// Guardar/editar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    if (!$nombre || $precio <= 0) { $error = 'Completá nombre y precio.'; }
    else {
        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name']) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $filename)) {
                    $imagen = $filename;
                    if ($id > 0) {
                        $r2 = $conn->query("SELECT imagen FROM tienda_productos WHERE id = $id");
                        if ($f2 = $r2->fetch_assoc()) { if ($f2['imagen'] && file_exists($upload_dir . $f2['imagen'])) unlink($upload_dir . $f2['imagen']); }
                    }
                }
            }
        }
        if ($id > 0) {
            if ($imagen) { $stmt = $conn->prepare("UPDATE tienda_productos SET nombre=?, descripcion=?, precio=?, imagen=? WHERE id=?"); $stmt->bind_param("ssdsi", $nombre, $descripcion, $precio, $imagen, $id); }
            else { $stmt = $conn->prepare("UPDATE tienda_productos SET nombre=?, descripcion=?, precio=? WHERE id=?"); $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $id); }
            $stmt->execute(); $mensaje = 'Producto actualizado.';
        } else {
            $stmt = $conn->prepare("INSERT INTO tienda_productos (nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $imagen); $stmt->execute(); $mensaje = 'Producto creado.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $id = (int)$_POST['id']; $r = $conn->query("SELECT imagen FROM tienda_productos WHERE id = $id");
    if ($f = $r->fetch_assoc()) { if ($f['imagen'] && file_exists(__DIR__ . '/uploads/' . $f['imagen'])) unlink(__DIR__ . '/uploads/' . $f['imagen']); }
    $conn->query("DELETE FROM tienda_productos WHERE id = $id"); $mensaje = 'Producto eliminado.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'toggle') {
    $id = (int)$_POST['id']; $conn->query("UPDATE tienda_productos SET activo = NOT activo WHERE id = $id"); $mensaje = 'Estado cambiado.';
}

$productos = $conn->query("SELECT * FROM tienda_productos ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda — Administrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding-top: 70px; }
        .nav-top { background: #1a1a2e; color: white; padding: 12px 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; display: flex; align-items: center; gap: 12px; }
        .nav-top h5 { margin: 0; font-weight: 700; flex: 1; }
        .nav-top a { color: rgba(255,255,255,0.8); text-decoration: none; }
        .nav-top a:hover { color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .card-title { font-weight: 700; font-size: 0.9rem; color: #1a1a2e; border-bottom: 1px solid #e9ecef; padding-bottom: 8px; margin-bottom: 12px; }
        .table th { font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="nav-top">
    <a href="../ordenes/listado.php"><i class="bi bi-arrow-left"></i></a>
    <h5><i class="bi bi-shop me-2"></i>Tienda</h5>
</div>
<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3">
                <div class="card-title"><i class="bi bi-gear me-1"></i>Configuración</div>
                <?php if ($mensaje): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="accion" value="config">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="seguimiento_activo" value="1" id="chk-seg" <?php echo ($config_s['seguimiento_activo'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="chk-seg">Seguimiento de órdenes</label>
                        <small class="d-block text-muted">Permite a los clientes consultar el estado de su orden.</small>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="tienda_activa" value="1" id="chk-tienda" <?php echo ($config_s['tienda_activa'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="chk-tienda">Tienda de productos</label>
                        <small class="d-block text-muted">Muestra productos en la página de seguimiento.</small>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Guardar</button>
                </form>
            </div>
            <a href="index.php" class="btn btn-outline-secondary w-100" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver página pública</a>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0" style="font-weight:700;">Productos</h6>
                        <button class="btn btn-primary btn-sm" onclick="abrirModal()"><i class="bi bi-plus-lg"></i> Nuevo</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th style="width:50px;">Img</th><th>Nombre</th><th>Precio</th><th style="width:90px;">Activo</th><th style="width:100px;">Acciones</th></tr></thead>
                            <tbody>
                                <?php if ($productos && $productos->num_rows > 0): while ($p = $productos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php if ($p['imagen'] && file_exists("uploads/" . $p['imagen'])): ?><img src="uploads/<?php echo htmlspecialchars($p['imagen']); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;"><?php else: ?><div style="width:40px;height:40px;background:#f0f2f5;border-radius:6px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-box text-muted"></i></div><?php endif; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong><?php if ($p['descripcion']): ?><br><small class="text-muted"><?php echo htmlspecialchars($p['descripcion']); ?></small><?php endif; ?></td>
                                    <td>$<?php echo number_format($p['precio'], 2); ?></td>
                                    <td><form method="POST" style="display:inline;"><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?php echo $p['id']; ?>"><button class="btn btn-sm <?php echo $p['activo'] ? 'btn-success' : 'btn-secondary'; ?>"><?php echo $p['activo'] ? 'Activo' : 'Inactivo'; ?></button></form></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="abrirModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES); ?>', <?php echo $p['precio']; ?>)"><i class="bi bi-pencil"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar producto?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?php echo $p['id']; ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No hay productos aún.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalTitle">Nuevo producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" id="prod-id" value="0">
                <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="nombre" id="prod-nombre" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Descripción</label><textarea name="descripcion" id="prod-desc" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Precio *</label><input type="number" step="0.01" name="precio" id="prod-precio" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Imagen</label><input type="file" name="imagen" class="form-control" accept="image/png,image/jpeg,image/webp"><small class="text-muted">Dejá vacío para mantener la actual.</small></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModal(id, nombre, desc, precio) {
    document.getElementById('modalTitle').textContent = id ? 'Editar producto' : 'Nuevo producto';
    document.getElementById('prod-id').value = id || 0;
    document.getElementById('prod-nombre').value = nombre || '';
    document.getElementById('prod-desc').value = desc || '';
    document.getElementById('prod-precio').value = precio || '';
    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}
</script>
</body>
</html>
