<?php
include 'includes/verificar_sesion.php';

$busqueda = '';
$filtro = 'nombre';

if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = $_GET['buscar'];
    $filtro = $_GET['filtro'] ?? 'nombre';
}

$sql = "SELECT * FROM clientes";
$where = [];
$params = [];

if (!empty($busqueda)) {
    $b = $conn->real_escape_string($busqueda);
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
$clientes = $conn->query($sql);

// Cliente a editar automaticamente desde detalle.php
$editar_cliente = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $eid = (int)$_GET['editar'];
    $editar_cliente = $conn->query("SELECT * FROM clientes WHERE id = $eid")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Clientes - FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f4f8; }
        .nav-jb {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            padding: 0.2rem 1.5rem 0.2rem 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,56,168,0.3);
        }
        .nav-jb .nav-content { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: space-between; }
        .nav-jb .nav-brand { color: white; font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px; display: inline-flex; align-items: center; gap: 10px; font-family: 'Neuropol X', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .nav-jb .nav-btn {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9); padding: 0.4rem 0.9rem; border-radius: 6px;
            font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
        }
        .nav-jb .nav-btn:hover { background: rgba(255,255,255,0.2); color: white; }
        .nav-jb .nav-btn.active { background: var(--jb-cyan); color: var(--jb-navy); font-weight: 600; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-left: 3px solid var(--jb-azul); }
        .card-header { background: white; border-bottom: 1px solid #e9ecef; color: var(--jb-azul-oscuro); font-weight: 600; }
        .card-header i { color: var(--jb-cyan); }
        .table { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-radius: 8px; overflow: hidden; margin-bottom: 0; }
        .table thead { background: linear-gradient(135deg, #001845, #023e8a) !important; }
        .table thead th { color: white !important; font-weight: 600; border: none !important; padding: 10px 12px; }
        .table tbody tr:nth-child(even) { background: #f8fafc; }
        .table tbody tr:hover { background: #dbeafe !important; }
        .table tbody td { padding: 10px 12px; vertical-align: middle; }
        .btn-jb-edit { background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro)); border: none; color: white; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; display:inline-flex;align-items:center;justify-content:center;width:28px;height:26px; }
        .btn-jb-edit:hover { color: white; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,119,182,0.3); }
        .btn-jb-delete { background: linear-gradient(135deg, #ef4444, #dc2626); border: none; color: white; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; display:inline-flex;align-items:center;justify-content:center;width:28px;height:26px; }
        .btn-jb-delete:hover { color: white; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(239,68,68,0.3); }
        .btn-jb-note { background: linear-gradient(135deg, #0ea5e9, #0284c7); border: none; color: white; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; display:inline-flex;align-items:center;justify-content:center;width:28px;height:26px; }
        .btn-jb-note:hover { color: white; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(14,165,233,0.3); }

        body.dark-mode { background: #0f1729 !important; color: #e2e8f0 !important; }
        body.dark-mode .card { background: #1a2235 !important; border-left-color: var(--jb-cyan) !important; color: #e2e8f0; }
        body.dark-mode .card-header { background: #1a2235 !important; border-bottom-color: #2d3748 !important; color: #e2e8f0; }
        body.dark-mode .table { background: #0f1729 !important; color: #e2e8f0 !important; }
        body.dark-mode .table thead { background: linear-gradient(135deg, #0d1b3e, #1a2744) !important; }
        body.dark-mode .table tbody tr { background: #1a2235 !important; }
        body.dark-mode .table tbody tr:nth-child(even) { background: #162032 !important; }
        body.dark-mode .table tbody tr:hover { background: #1e3a5f !important; }
        body.dark-mode .table tbody td { color: #e2e8f0 !important; }
        body.dark-mode .form-control { background: #1a2235 !important; border-color: #2d3748 !important; color: #e2e8f0 !important; }
        body.dark-mode .modal-content { background: #1a2235 !important; color: #e2e8f0; }
        body.dark-mode .modal-header { border-bottom-color: #2d3748 !important; }
        body.dark-mode .modal-footer { border-top-color: #2d3748 !important; }
        body.dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        body.dark-mode h1 { color: #e2e8f0 !important; }
        body.dark-mode .bi-chat-quote-fill { color: #38bdf8 !important; }
        body.dark-mode .bi-chat-quote { color: #64748b !important; }

        .btn-dark-mode-nav {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9); width: 42px; height: 42px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            transition: all 0.2s; font-size: 1.2rem; flex-shrink: 0;
        }
        .btn-dark-mode-nav:hover { background: rgba(255,255,255,0.2); color: white; transform: scale(1.05); }
        .btn-hamburger {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            width: 38px; height: 38px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .btn-hamburger:hover {
            background: rgba(255,255,255,0.2);
            color: white; transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }
        .rol-badge {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .sidebar-jb {
            background: linear-gradient(180deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 100%) !important;
            color: white;
            border-right: 2px solid var(--jb-cyan);
        }
        .sidebar-jb .offcanvas-header { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-jb .offcanvas-title { color: var(--jb-cyan); font-weight: 700; }
        .sidebar-jb .btn-close { filter: brightness(0) invert(1); }
        .sidebar-menu-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: rgba(255,255,255,0.85);
            text-decoration: none; border-radius: 8px;
            transition: all 0.2s; cursor: pointer;
            font-size: 0.95rem; border: none; background: none; width: 100%; text-align: left;
        }
        .sidebar-menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white; transform: translateX(4px);
        }
        .sidebar-menu-item i { font-size: 1.3rem; width: 24px; text-align: center; color: var(--jb-cyan); }
        .sidebar-menu-item .badge-sidebar {
            margin-left: auto; background: var(--jb-cyan);
            color: var(--jb-navy); font-size: 0.7rem;
            padding: 2px 8px; border-radius: 10px; font-weight: 700;
        }
        .sidebar-divider { border-color: rgba(255,255,255,0.1); margin: 8px 0; }
        body.dark-mode .sidebar-jb { background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important; }

        .nav-left, .nav-right { min-width: 40px; }
        .nav-right { justify-content: flex-end; }
        @media (max-width: 767px) {
            .nav-center { position: absolute; left: 50%; transform: translateX(-50%); }
        }
        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }
    </style>
</head>
<body>

<!-- Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start sidebar-jb" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">
            <?php if ($ES_ADMIN): ?>
            <i class="bi bi-shield-lock me-2"></i> Admin
            <?php elseif ($ES_TECNICO): ?>
            <i class="bi bi-tools me-2"></i> Técnico
            <?php else: ?>
            <i class="bi bi-headset me-2"></i> Recepción
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3 d-flex flex-column">
        <!-- Main menu items -->
        <div>
            <?php if ($ES_ADMIN): ?>
            <a href="?cambiar_vista=recepcion" class="sidebar-menu-item <?php echo $VISTA_ADMIN === 'recepcion' ? 'active' : ''; ?>">
                <i class="bi bi-headset"></i>
                <span>Vista Recepción</span>
                <?php if ($VISTA_ADMIN === 'recepcion'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
            </a>
            <a href="?cambiar_vista=tecnico" class="sidebar-menu-item <?php echo $VISTA_ADMIN === 'tecnico' ? 'active' : ''; ?>">
                <i class="bi bi-tools"></i>
                <span>Vista Técnico</span>
                <?php if ($VISTA_ADMIN === 'tecnico'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
            </a>
            <?php endif; ?>
            <!-- Home -->
            <a href="listado.php" class="sidebar-menu-item">
                <i class="bi bi-house-door"></i>
                <span>Home</span>
            </a>

            <hr class="sidebar-divider">

            <!-- Repuestos -->
            <a href="repuestos.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Repuestos</span>
            </a>

            <hr class="sidebar-divider">

            <!-- Clientes -->
            <a href="clientes.php" class="sidebar-menu-item">
                <i class="bi bi-people"></i>
                <span>Clientes</span>
            </a>

            <hr class="sidebar-divider">

            <!-- Movimientos -->
            <a href="movimientos.php" class="sidebar-menu-item">
                <i class="bi bi-activity"></i>
                <span>Movimientos</span>
            </a>
            <?php if ($ES_ADMIN): ?>
            <hr class="sidebar-divider">
            <a href="gestion_usuarios.php" class="sidebar-menu-item">
                <i class="bi bi-shield-lock"></i>
                <span>Gestión de Usuarios</span>
            </a>
            <?php endif; ?>
            <hr class="sidebar-divider">
            <a href="configuracion.php" class="sidebar-menu-item">
                <i class="bi bi-gear"></i>
                <span>Configuración</span>
            </a>
        </div>

        <!-- Spacer to push dark mode to bottom -->
        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <!-- Modo Nocturno -->
        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>

        <hr class="sidebar-divider">

        <!-- Cerrar Sesión -->
        <a href="../../logout.php" class="sidebar-menu-item" >
            <i class="bi bi-box-arrow-right" ></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<nav class="nav-jb" style="padding:0;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem; position: relative;">
        <!-- IZQUIERDA: hamburguesa + Volver -->
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="listado.php" class="nav-btn d-none d-md-inline-flex"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>

        <!-- CENTRO: logo + texto -->
        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
                <span style="display:inline-flex; align-items:center; gap:10px; color:white;">
                    <img src="logo.png" alt="FullTaller" class="nav-logo">
                    <span class="d-none d-sm-inline" style="font-size:0.95rem; font-weight:500;">Sistema de gestion de ordenes</span>
                </span>
        </div>

        <!-- DERECHA: rol + Salir -->
        <div class="nav-right" style="display: flex; align-items: center; gap: 2px;">
            <div class="rol-salir-wrapper" style="display: flex; flex-direction: column; align-items: stretch; gap: 2px;">
                <span class="rol-badge" style="padding:2px 10px; font-size:0.8rem; text-align:center; width:100%; display:block;">
                    <?php if ($ES_ADMIN): ?>
                    <i class="bi bi-shield-lock"></i>
                    <?php else: ?>
                    <i class="bi bi-<?php echo $ES_TECNICO ? 'tools' : 'headset'; ?>"></i>
                    <?php endif; ?>
                    <span style="font-weight:400;font-size:0.7rem;"><?php echo htmlspecialchars($NOMBRE_USUARIO); ?></span>
                </span>
                
            </div>
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

    <!-- Búsqueda -->
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

    <!-- Modal Comentario -->
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

    <!-- Tabla -->
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

<!-- Modal Cliente -->
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
    if (!confirm('¿Eliminar este cliente? Las órdenes asociadas no se eliminarán.')) return;
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

// Auto-abrir edicion si viene desde detalle.php
<?php if ($editar_cliente): ?>
editarCliente(
    <?php echo $editar_cliente['id']; ?>,
    '<?php echo htmlspecialchars(addslashes($editar_cliente['nombre'])); ?>',
    '<?php echo htmlspecialchars(addslashes($editar_cliente['dni'])); ?>',
    '<?php echo htmlspecialchars(addslashes($editar_cliente['telefono'])); ?>',
    '<?php echo htmlspecialchars(addslashes($editar_cliente['opinion'] ?? '')); ?>'
);
<?php endif; ?>


</script>
</body>
</html>