<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();

$busqueda = '';
$filtro = 'nombre';

if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = $_GET['buscar'];
    $filtro = $_GET['filtro'] ?? 'nombre';
}

$sql = "SELECT * FROM clientes";
$where = [];

if (!empty($busqueda)) {
    $b = $db->real_escape_string($busqueda);
    if ($filtro == 'nombre') {
        $where[] = "nombre LIKE '%$b%'";
    } elseif ($filtro == 'dni') {
        $where[] = "dni LIKE '%$b%'";
    } elseif ($filtro == 'telefono') {
        $where[] = "telefono LIKE '%$b%'";
    }
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY nombre ASC";
$clientes = $db->query($sql);
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Clientes - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body.dark-mode .bi-chat-quote-fill { color: #38bdf8 !important; }
        body.dark-mode .bi-chat-quote { color: #64748b !important; }
    </style>
</head>
<body>

<?php require 'includes/sidebar.php'; ?>

<nav class="nav-jb" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 0 0 0.25rem;position:relative;">
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
            <span class="rol-badge">
                <?php echo esAdminPOS() ? '👑' : '👤'; ?>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
        </div>
    </div>
</nav>

<div class="container-fluid px-1 mt-2 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 style="color: var(--jb-navy);"><i class="bi bi-people"></i> Clientes</h1>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limpiarModalCliente()">
            <i class="bi bi-plus-lg"></i> Nuevo Cliente
        </button>
    </div>

    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="filtro" class="form-select form-select-sm">
                    <option value="nombre" <?php echo $filtro=='nombre'?'selected':''; ?>>Nombre</option>
                    <option value="dni" <?php echo $filtro=='dni'?'selected':''; ?>>DNI</option>
                    <option value="telefono" <?php echo $filtro=='telefono'?'selected':''; ?>>Teléfono</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" name="buscar" class="form-control form-control-sm" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar cliente...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark btn-sm w-100"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </div>
    </form>

    <div class="modal fade" id="modalComentario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-chat-quote-fill text-info"></i> Nota interna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="comentarioClienteNombre" class="fw-bold mb-2"></p>
                    <p id="comentarioClienteTexto" class="mb-0" style="background:#f8fafc;padding:12px;border-radius:8px;border-left:3px solid var(--jb-cyan);"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0" style="overflow-x:auto;">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>DNI</th>
                        <th>Teléfono</th>
                        <th style="width:100px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes->num_rows == 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No se encontraron clientes</td></tr>
                    <?php endif; ?>
                    <?php while ($c = $clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($c['dni']); ?></td>
                        <td><?php echo htmlspecialchars($c['telefono']); ?></td>
                        <td style="text-align:center;white-space:nowrap;">
                            <?php if (!empty($c['opinion'])): ?>
                                <button class="btn-jb-note" onclick="verComentario('<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>', '<?php echo htmlspecialchars(addslashes($c['opinion'])); ?>')" title="Ver nota interna">
                                    <i class="bi bi-chat-quote"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-jb-note" onclick="verComentario('<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>', 'Sin nota interna')" title="Sin nota interna" style="opacity:0.4;">
                                    <i class="bi bi-chat-quote"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn-jb-edit" onclick="editarCliente(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>', '<?php echo htmlspecialchars(addslashes($c['dni'])); ?>', '<?php echo htmlspecialchars(addslashes($c['telefono'])); ?>', '<?php echo htmlspecialchars(addslashes($c['opinion'] ?? '')); ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-jb-delete" onclick="eliminarCliente(<?php echo $c['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCliente">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalClienteTitle">Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="cliente_id">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="cliente_nombre" class="form-control" required oninput="this.value=this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI</label>
                        <input type="text" name="dni" id="cliente_dni" class="form-control" required oninput="this.value=this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="cliente_telefono" class="form-control" required oninput="this.value=this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-chat-quote"></i> Nota interna <span class="small-text text-muted">(Solo visible para el taller)</span></label>
                        <textarea name="opinion" id="cliente_opinion" class="form-control" rows="2" placeholder="Nota interna..." style="font-size:0.85rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function limpiarModalCliente() {
    document.getElementById('modalClienteTitle').textContent = 'Nuevo Cliente';
    document.getElementById('cliente_id').value = '';
    document.getElementById('formCliente').reset();
}

function editarCliente(id, nombre, dni, telefono, opinion) {
    document.getElementById('modalClienteTitle').textContent = 'Editar Cliente';
    document.getElementById('cliente_id').value = id;
    document.getElementById('cliente_nombre').value = nombre;
    document.getElementById('cliente_dni').value = dni;
    document.getElementById('cliente_telefono').value = telefono;
    document.getElementById('cliente_opinion').value = opinion || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCliente')).show();
}

document.getElementById('formCliente').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('cliente_id').value;
    const url = id ? 'actualizar_cliente.php' : 'nuevo_cliente.php';
    const formData = new FormData(this);

    fetch(url, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'clientes.php';
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar'));
        }
    })
    .catch(err => { alert('Error de conexión'); console.error(err); });
});

function eliminarCliente(id) {
    if (!confirm('¿Eliminar este cliente?')) return;
    fetch('eliminar_cliente.php', {
        method: 'POST',
        body: new URLSearchParams({ id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('Error: ' + (data.error || 'No se pudo eliminar'));
    })
    .catch(err => { alert('Error de conexión'); });
}

function verComentario(nombre, opinion) {
    document.getElementById('comentarioClienteNombre').textContent = '👤 ' + nombre;
    document.getElementById('comentarioClienteTexto').textContent = opinion;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalComentario')).show();
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
if (localStorage.getItem('jb_dark_mode') === '1') { document.body.classList.add('dark-mode'); }
updateDarkModeIcon();
</script>
</body>
</html>
