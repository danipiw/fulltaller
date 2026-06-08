<?php
include 'includes/verificar_sesion.php';

$es_admin = in_array($_SESSION['rol'], ['admin', 'full']);
if (!$es_admin) {
    header('Location: listado.php?error=sin_acceso');
    exit;
}

$mensaje = '';
$error = '';

$conn->query("CREATE TABLE IF NOT EXISTS tienda_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    activo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Guardar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = (float)($_POST['precio'] ?? 0);

        if (!$nombre || $precio <= 0) {
            $error = 'Completá nombre y precio.';
        } else {
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name']) {
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $upload_dir = __DIR__ . '/tienda_uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $filename)) {
                        $imagen = $filename;
                        // Borrar imagen anterior si existe
                        if ($id > 0) {
                            $r = $conn->query("SELECT imagen FROM tienda_productos WHERE id = $id");
                            if ($f = $r->fetch_assoc()) {
                                if ($f['imagen'] && file_exists($upload_dir . $f['imagen'])) unlink($upload_dir . $f['imagen']);
                            }
                        }
                    }
                }
            }

            if ($id > 0) {
                if ($imagen) {
                    $stmt = $conn->prepare("UPDATE tienda_productos SET nombre=?, descripcion=?, precio=?, imagen=? WHERE id=?");
                    $stmt->bind_param("ssdsi", $nombre, $descripcion, $precio, $imagen, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE tienda_productos SET nombre=?, descripcion=?, precio=? WHERE id=?");
                    $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $id);
                }
                $stmt->execute();
                $mensaje = 'Producto actualizado.';
            } else {
                $stmt = $conn->prepare("INSERT INTO tienda_productos (nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $imagen);
                $stmt->execute();
                $mensaje = 'Producto creado.';
            }
        }
    } elseif ($_POST['accion'] === 'eliminar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $r = $conn->query("SELECT imagen FROM tienda_productos WHERE id = $id");
        if ($f = $r->fetch_assoc()) {
            if ($f['imagen'] && file_exists(__DIR__ . '/tienda_uploads/' . $f['imagen'])) {
                unlink(__DIR__ . '/tienda_uploads/' . $f['imagen']);
            }
        }
        $conn->query("DELETE FROM tienda_pedido_items WHERE producto_id = $id");
        $conn->query("DELETE FROM tienda_productos WHERE id = $id");
        $mensaje = 'Producto eliminado.';
    } elseif ($_POST['accion'] === 'toggle' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE tienda_productos SET activo = NOT activo WHERE id = $id");
        $mensaje = 'Estado cambiado.';
    }
}

$productos = $conn->query("SELECT * FROM tienda_productos ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding-top: 70px; }
        .nav-top { background: #1a1a2e; color: white; padding: 12px 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; display: flex; align-items: center; gap: 12px; }
        .nav-top h5 { margin: 0; font-weight: 700; flex: 1; }
        .nav-top a { color: rgba(255,255,255,0.8); text-decoration: none; }
        .nav-top a:hover { color: white; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .table th { font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="nav-top">
    <a href="listado.php"><i class="bi bi-arrow-left"></i></a>
    <h5><i class="bi bi-shop me-2"></i>Administrar Tienda</h5>
    <button class="btn btn-sm btn-outline-light" onclick="abrirModal()"><i class="bi bi-plus-lg"></i></button>
</div>
<div class="container-fluid py-3">
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="bi bi-shop me-2"></i>Administrar Tienda</h4>
            <button class="btn btn-primary" onclick="abrirModal()"><i class="bi bi-plus-lg"></i> Nuevo producto</button>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-success py-2"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">Imagen</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th style="width:100px;">Activo</th>
                                <th style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($productos && $productos->num_rows > 0): ?>
                                <?php while ($p = $productos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($p['imagen'] && file_exists("tienda_uploads/" . $p['imagen'])): ?>
                                        <img src="tienda_uploads/<?php echo htmlspecialchars($p['imagen']); ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                        <?php else: ?>
                                        <div style="width:50px;height:50px;background:#f0f2f5;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-box text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                                        <?php if ($p['descripcion']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($p['descripcion']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($p['precio'], 2); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                            <button class="btn btn-sm <?php echo $p['activo'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $p['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="abrirModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES); ?>', <?php echo $p['precio']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar producto?')">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay productos. Creá el primero.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="seguimiento.php" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> Ver página pública
            </a>
            <a href="listado.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" id="prod-id" value="0">
                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="prod-nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" id="prod-desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Precio <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="precio" id="prod-precio" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Imagen</label>
                    <input type="file" name="imagen" class="form-control" accept="image/png,image/jpeg,image/webp">
                    <small class="text-muted">Dejá vacío para mantener la imagen actual.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
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
