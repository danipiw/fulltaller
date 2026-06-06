<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/lib/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// ========== EXPORTAR A CSV ==========
if (isset($_GET['exportar'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=productos_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['codigo', 'descripcion', 'precio', 'stock']);

    $productos_exp = $db->query('SELECT codigo, descripcion, precio, stock FROM pos_productos WHERE activo=1 ORDER BY descripcion');
    while ($p = $productos_exp->fetch_assoc()) {
        fputcsv($output, [$p['codigo'], $p['descripcion'], $p['precio'], $p['stock']]);
    }
    fclose($output);
    $db->close();
    exit;
}

// ========== IMPORTAR ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar'])) {
    if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['tmp_name']) {
        $archivo = $_FILES['archivo_csv']['tmp_name'];
        $nombre = $_FILES['archivo_csv']['name'];
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        $importados = 0;
        $actualizados = 0;
        $errores = [];
        $linea = 0;

        if (in_array($extension, ['xlsx', 'xls'])) {
            if ($xlsx = SimpleXLSX::parse($archivo)) {
                $rows = $xlsx->rows();
                foreach ($rows as $index => $data) {
                    if ($index === 0) continue;
                    $linea = $index + 1;

                    if (count($data) < 4) continue;

                    $codigo = trim($data[0] ?? '');
                    $descripcion = trim($data[1] ?? '');
                    $precio = floatval(str_replace(',', '.', str_replace('$', '', $data[2] ?? '0')));
                    $stock = intval($data[3] ?? 0);

                    if (!$codigo || !$descripcion || $precio <= 0) {
                        $errores[] = "Línea $linea: datos inválidos (código: '$codigo')";
                        continue;
                    }

                    try {
                        $stmt_check = $db->prepare('SELECT id FROM pos_productos WHERE codigo = ?');
                        $stmt_check->bind_param('s', $codigo);
                        $stmt_check->execute();
                        $res = $stmt_check->get_result();
                        $existe = $res->fetch_assoc();
                        $stmt_check->close();

                        if ($existe) {
                            $stmt = $db->prepare('UPDATE pos_productos SET descripcion = ?, precio = ?, stock = ?, activo = 1 WHERE codigo = ?');
                            $stmt->bind_param('sdis', $descripcion, $precio, $stock, $codigo);
                            $stmt->execute();
                            $stmt->close();
                            $actualizados++;
                        } else {
                            $stmt = $db->prepare('INSERT INTO pos_productos (codigo, descripcion, precio, stock) VALUES (?, ?, ?, ?)');
                            $stmt->bind_param('ssdi', $codigo, $descripcion, $precio, $stock);
                            $stmt->execute();
                            $stmt->close();
                            $importados++;
                        }
                    } catch (Exception $e) {
                        $errores[] = "Línea $linea: " . $e->getMessage();
                    }
                }
            } else {
                $errores[] = 'Error al leer Excel: ' . SimpleXLSX::parseError();
            }
        } else {
            $file = fopen($archivo, 'r');

            $bom = fread($file, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($file);
            }

            fgetcsv($file);

            while (($data = fgetcsv($file)) !== false) {
                $linea++;
                if (count($data) < 4) continue;

                $codigo = trim($data[0]);
                $descripcion = trim($data[1]);
                $precio = floatval(str_replace(',', '.', str_replace('$', '', $data[2])));
                $stock = intval($data[3]);

                if (!$codigo || !$descripcion || $precio <= 0) {
                    $errores[] = "Línea $linea: datos inválidos (código: '$codigo')";
                    continue;
                }

                try {
                    $stmt_check = $db->prepare('SELECT id FROM pos_productos WHERE codigo = ?');
                    $stmt_check->bind_param('s', $codigo);
                    $stmt_check->execute();
                    $res = $stmt_check->get_result();
                    $existe = $res->fetch_assoc();
                    $stmt_check->close();

                    if ($existe) {
                        $stmt = $db->prepare('UPDATE pos_productos SET descripcion = ?, precio = ?, stock = ?, activo = 1 WHERE codigo = ?');
                        $stmt->bind_param('sdis', $descripcion, $precio, $stock, $codigo);
                        $stmt->execute();
                        $stmt->close();
                        $actualizados++;
                    } else {
                        $stmt = $db->prepare('INSERT INTO pos_productos (codigo, descripcion, precio, stock) VALUES (?, ?, ?, ?)');
                        $stmt->bind_param('ssdi', $codigo, $descripcion, $precio, $stock);
                        $stmt->execute();
                        $stmt->close();
                        $importados++;
                    }
                } catch (Exception $e) {
                    $errores[] = "Línea $linea: " . $e->getMessage();
                }
            }
            fclose($file);
        }

        $mensaje_import = "✅ $importados importados, $actualizados actualizados.";
        if ($errores) {
            $mensaje_import .= " ⚠️ " . count($errores) . " errores.";
            $_SESSION['import_errores'] = $errores;
        }
    }
}

// ========== CRUD NORMAL ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['importar'])) {
    if (isset($_POST['guardar'])) {
        $id = $_POST['id'] ?? '';
        $codigo = trim($_POST['codigo']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);
        
        if ($id) {
            $stmt = $db->prepare('UPDATE pos_productos SET codigo=?, descripcion=?, precio=?, stock=? WHERE id=?');
            $stmt->bind_param('ssdii', $codigo, $descripcion, $precio, $stock, $id);
        } else {
            $stmt = $db->prepare('INSERT INTO pos_productos (codigo, descripcion, precio, stock) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssdi', $codigo, $descripcion, $precio, $stock);
        }
        $stmt->execute();
        $stmt->close();
    }
    
    if (isset($_POST['eliminar']) && $_POST['eliminar'] !== '') {
        $stmt = $db->prepare('UPDATE pos_productos SET activo=0 WHERE id=?');
        $stmt->bind_param('i', $_POST['eliminar']);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: productos.php');
    exit;
}

// ========== BÚSQUEDA ==========
$busqueda = $_GET['buscar'] ?? '';
$where_busqueda = '';
if ($busqueda) {
    $busqueda_esc = $db->real_escape_string($busqueda);
    $where_busqueda = "AND (codigo LIKE '%$busqueda_esc%' OR descripcion LIKE '%$busqueda_esc%')";
}

$productos = $db->query("SELECT * FROM pos_productos WHERE activo=1 $where_busqueda ORDER BY descripcion");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - POS FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php require 'includes/sidebar.php'; ?>

<!-- ===== NAVBAR ===== -->
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

    <?php if (isset($mensaje_import)): ?>
    <div class="alert success"><?php echo $mensaje_import; ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['import_errores'])): ?>
    <div class="alert error">
        <strong>Errores de importación:</strong>
        <ul style="margin-top:10px; padding-left:20px;">
            <?php foreach (array_slice($_SESSION['import_errores'], 0, 10) as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
            <?php if (count($_SESSION['import_errores']) > 10): ?>
            <li>... y <?php echo count($_SESSION['import_errores']) - 10; ?> errores más</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php 
        unset($_SESSION['import_errores']);
    endif; 
    ?>

    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h1 style="margin:0;">📦 Productos</h1>
        <div style="display:flex;gap:6px;">
            <?php if (esAdminPOS()): ?>
            <button type="button" class="btn-guardar" onclick="abrirModalProducto()">➕ Nuevo</button>
            <button type="button" class="btn-primary" onclick="abrirModalImportar()">📥 Importar / 📤 Exportar</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel busqueda-panel">
        <form method="GET" class="busqueda-form">
            <div class="form-group" style="flex:1;margin-bottom:0;">
                <input type="text" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>" 
                       placeholder="🔍 Buscar por código o nombre..." autofocus autocomplete="off">
            </div>
            <button type="submit" class="btn-guardar">🔍 Buscar</button>
            <?php if ($busqueda): ?>
            <a href="productos.php" class="btn-cancelar">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <h2>Productos <?php echo $busqueda ? '(filtrado)' : ''; ?></h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $productos->fetch_assoc()): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($p['codigo']); ?></code></td>
                    <td><?php echo htmlspecialchars($p['descripcion']); ?></td>
                    <td class="precio">$<?php echo number_format($p['precio'], 2); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td class="actions">
                        <?php if (esAdminPOS()): ?>
                        <button type="button" class="btn-edit" 
                            onclick="editarProducto(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['codigo'])); ?>', '<?php echo htmlspecialchars(addslashes($p['descripcion'])); ?>', <?php echo $p['precio']; ?>, <?php echo $p['stock']; ?>)">✏️</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar?')">
                            <input type="hidden" name="eliminar" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn-delete">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PRODUCTO -->
<div id="modal-producto" class="modal-overlay" style="display:none;" onclick="if(event.target===this)cerrarModalProducto()">
    <div class="modal-content" style="max-width:500px;">
        <h2 id="modal-producto-title">➕ Nuevo Producto</h2>
        <form method="POST">
            <input type="hidden" name="id" id="form-producto-id" value="">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Código:</label>
                    <input type="text" name="codigo" id="form-producto-codigo" required placeholder="SKU-001">
                </div>
                <div class="form-group">
                    <label>Precio:</label>
                    <input type="number" step="0.01" name="precio" id="form-producto-precio" required placeholder="0.00">
                </div>
            </div>
            
            <div class="form-group">
                <label>Descripción:</label>
                <input type="text" name="descripcion" id="form-producto-descripcion" required placeholder="Nombre del producto">
            </div>
            
            <div class="form-group">
                <label>Stock:</label>
                <input type="number" name="stock" id="form-producto-stock" value="0" min="0">
            </div>
            
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" name="guardar" class="btn-guardar" style="flex:1;">💾 Guardar</button>
                <button type="button" class="btn-cancelar" onclick="cerrarModalProducto()" style="flex:1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORTAR / EXPORTAR -->
<div id="modal-importar" class="modal-overlay" style="display:none;" onclick="if(event.target===this)cerrarModalImportar()">
    <div class="modal-content" style="max-width:500px;">
        <h2>📥 Importar / 📤 Exportar</h2>
        
        <div class="ie-box" style="margin-bottom:12px;">
            <h3>📥 Importar desde Excel/CSV</h3>
            <p class="hint">Formatos: .xlsx, .xls, .csv — Columnas: código | descripción | precio | stock (con encabezado)</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="archivo_csv" accept=".csv,.xlsx,.xls" required style="margin-bottom:8px;">
                <button type="submit" name="importar" class="btn-guardar">📥 Importar</button>
            </form>
        </div>
        
        <div class="ie-box">
            <h3>📤 Exportar a CSV</h3>
            <p class="hint">Descarga todos los productos activos</p>
            <a href="?exportar=1" class="btn-guardar" style="display:inline-block;text-decoration:none;">📤 Exportar CSV</a>
        </div>
        
        <button type="button" class="btn-cerrar-modal" onclick="cerrarModalImportar()" style="margin-top:12px;">Cerrar</button>
    </div>
</div>

<script>
function abrirModalProducto() {
    document.getElementById('modal-producto-title').textContent = '➕ Nuevo Producto';
    document.getElementById('form-producto-id').value = '';
    document.getElementById('form-producto-codigo').value = '';
    document.getElementById('form-producto-descripcion').value = '';
    document.getElementById('form-producto-precio').value = '';
    document.getElementById('form-producto-stock').value = '0';
    document.getElementById('modal-producto').style.display = 'flex';
    setTimeout(() => document.getElementById('form-producto-codigo').focus(), 200);
}

function editarProducto(id, codigo, descripcion, precio, stock) {
    document.getElementById('modal-producto-title').textContent = '✏️ Editar Producto';
    document.getElementById('form-producto-id').value = id;
    document.getElementById('form-producto-codigo').value = codigo;
    document.getElementById('form-producto-descripcion').value = descripcion;
    document.getElementById('form-producto-precio').value = precio;
    document.getElementById('form-producto-stock').value = stock;
    document.getElementById('modal-producto').style.display = 'flex';
    setTimeout(() => document.getElementById('form-producto-codigo').focus(), 200);
}

function cerrarModalProducto() {
    document.getElementById('modal-producto').style.display = 'none';
}

function abrirModalImportar() {
    document.getElementById('modal-importar').style.display = 'flex';
}

function cerrarModalImportar() {
    document.getElementById('modal-importar').style.display = 'none';
}

<?php if (isset($_GET['edit'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    <?php
    $stmt = $db->prepare('SELECT * FROM pos_productos WHERE id = ?');
    $stmt->bind_param('i', $_GET['edit']);
    $stmt->execute();
    $res = $stmt->get_result();
    $editP = $res->fetch_assoc();
    $stmt->close();
    if ($editP):
    ?>
    editarProducto(<?php echo $editP['id']; ?>, '<?php echo htmlspecialchars(addslashes($editP['codigo'])); ?>', '<?php echo htmlspecialchars(addslashes($editP['descripcion'])); ?>', <?php echo $editP['precio']; ?>, <?php echo $editP['stock']; ?>);
    <?php endif; ?>
});
<?php endif; ?>
</script>

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
<?php $db->close(); ?>
