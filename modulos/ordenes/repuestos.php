<?php
include 'includes/verificar_sesion.php';

$mensaje = '';
$tipo_msj = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['csv_file']['tmp_name'];
    $nombre = $_FILES['csv_file']['name'];
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $insertados = 0;
    $stmt = $conn->prepare("INSERT INTO repuestos (tipo, marca, modelo, precio) VALUES (?, ?, ?, ?)");

    $conn->query("DELETE FROM repuestos");
    $skipFirst = true;

    if ($ext === 'xlsx' || $ext === 'xls') {
        require_once __DIR__ . '/lib/SimpleXLSX.php';
        if ($xlsx = Shuchkin\SimpleXLSX::parse($tmp)) {
            foreach ($xlsx->rows() as $line) {
                if ($skipFirst) { $skipFirst = false; continue; }
                if (count($line) < 4) continue;
                $t = trim((string)($line[0] ?? ''));
                $m = trim((string)($line[1] ?? ''));
                $mo = trim((string)($line[2] ?? ''));
                $p = trim((string)($line[3] ?? ''));
                if (!$t && !$m && !$mo && !$p) continue;
                $stmt->bind_param('ssss', $t, $m, $mo, $p);
                $stmt->execute();
                $insertados++;
            }
            $mensaje = "Se importaron $insertados repuestos correctamente.";
            $tipo_msj = 'success';
        } else {
            $mensaje = 'Error al leer el archivo Excel: ' . Shuchkin\SimpleXLSX::parseError();
            $tipo_msj = 'danger';
        }
    } else {
        $handle = fopen($tmp, 'r');
        if ($handle) {
            while (($line = fgetcsv($handle, 0, ',')) !== false || ($line = fgetcsv($handle, 0, ';')) !== false) {
                if ($skipFirst) { $skipFirst = false; continue; }
                if (count($line) < 4) continue;
                if (count($line) > 4) {
                    rewind($handle);
                    $skipFirst = true;
                    fgetcsv($handle, 0, ',');
                    while (($line2 = fgetcsv($handle, 0, ';')) !== false) {
                        if ($skipFirst) { $skipFirst = false; continue; }
                        if (count($line2) < 4) continue;
                        $t = trim($line2[0]); $m = trim($line2[1]); $mo = trim($line2[2]); $p = trim($line2[3]);
                        if (!$t && !$m && !$mo && !$p) continue;
                        $stmt->bind_param('ssss', $t, $m, $mo, $p);
                        $stmt->execute();
                        $insertados++;
                    }
                    break;
                }
                $t = trim($line[0]); $m = trim($line[1]); $mo = trim($line[2]); $p = trim($line[3]);
                if (!$t && !$m && !$mo && !$p) continue;
                $stmt->bind_param('ssss', $t, $m, $mo, $p);
                $stmt->execute();
                $insertados++;
            }
            fclose($handle);
            $mensaje = "Se importaron $insertados repuestos correctamente.";
            $tipo_msj = 'success';
        } else {
            $mensaje = 'Error al abrir el archivo.';
            $tipo_msj = 'danger';
        }
    }
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Repuestos - FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        html, body { height: 100%; overflow: hidden; }
        body { background: #f0f4f8; display: flex; flex-direction: column; }
        .nav-jb { flex-shrink: 0; background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%); padding: 0.2rem 1.5rem 0.2rem 0; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .search-card { background: white; border-radius: 12px; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 20px; }
        .result-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; transition: all 0.15s; }
        .result-row:hover { background: #f0f9ff; }
        .precio-badge { font-size: 1.1rem; font-weight: 700; color: var(--jb-azul); }
        body.dark-mode { background: #0f1729 !important; }
        body.dark-mode .search-card { background: #1a2235 !important; }
        body.dark-mode .result-row { border-bottom-color: #2d3748; }
        body.dark-mode .result-row:hover { background: #162032; }
        body.dark-mode .precio-badge { color: #38bdf8; }
        .form-control, .form-select { font-size: 0.95rem; }
        .sidebar-jb { background: linear-gradient(180deg, #001845 0%, #023e8a 100%) !important; color: white; }
        .sidebar-jb .offcanvas-title { color: var(--jb-cyan); font-weight: 700; }
        .sidebar-jb .btn-close { filter: brightness(0) invert(1); }
        .sidebar-menu-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            color: rgba(255,255,255,0.85); text-decoration: none; border-radius: 8px;
            transition: all 0.2s; cursor: pointer; font-size: 0.95rem;
            border: none; background: none; width: 100%; text-align: left;
        }
        .sidebar-menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(4px); }
        .sidebar-menu-item i { font-size: 1.3rem; width: 24px; text-align: center; color: var(--jb-cyan); }
        .sidebar-divider { border-color: rgba(255,255,255,0.1); margin: 8px 0; }
        body.dark-mode .sidebar-jb { background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important; }
        .btn-hamburger {
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
            color: white; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 1.5rem;
            display: flex; align-items: center; cursor: pointer;
        }
        .btn-hamburger:hover { background: rgba(255,255,255,0.25); }
        .nav-jb .nav-btn {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .nav-jb .nav-btn:hover { background-color: rgba(255,255,255,0.2); color: white; }
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
        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }
        @media (max-width: 767px) {
            .nav-center { position: absolute; left: 50%; transform: translateX(-50%); }
            .nav-left, .nav-right { min-width: 40px; }
            .nav-left .nav-btn { padding: 0.25rem 0.5rem !important; font-size: 0.7rem !important; gap: 3px !important; }
        }
        .main-content { flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; padding:0.75rem 1rem; }
        .resultados-wrap { height: 100%; overflow-y: auto; }
        .table-repuestos { font-size: 0.9rem; }
        .table-repuestos th { background: var(--jb-navy); color: white; position: sticky; top: 0; }
        body.dark-mode .table-repuestos th { background: #0a1628; }
        body.dark-mode .table-repuestos td { background: #1a2235; color: #e2e8f0; }
        body.dark-mode .main-content h1, body.dark-mode .main-content .form-label { color: #e2e8f0 !important; }
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
            <a href="listado.php" class="sidebar-menu-item">
                <i class="bi bi-house-door"></i>
                <span>Home</span>
            </a>

            <hr class="sidebar-divider">

            <a href="repuestos.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Repuestos</span>
            </a>

            <hr class="sidebar-divider">

            <a href="clientes.php" class="sidebar-menu-item">
                <i class="bi bi-people"></i>
                <span>Clientes</span>
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

        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>

        <hr class="sidebar-divider">

        <a href="../../logout.php" class="sidebar-menu-item" >
            <i class="bi bi-box-arrow-right" ></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>
<nav class="nav-jb" style="padding:0;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem; position: relative;">
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="listado.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.9);padding:0.4rem 0.9rem;border-radius:6px;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span class="nav-brand" style="color:white;font-weight:800;font-size:2rem;line-height:1;letter-spacing:-1px;font-family:'Neuropol X','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;"><i class="bi bi-gear-wide-connected"></i> Repuestos</span>
        </div>
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

<div class="main-content">

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_msj; ?> alert-dismissible fade show" style="margin-bottom:0.5rem;"><?php echo $mensaje; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2" style="margin-bottom:0.5rem;">
        <h1 style="color:var(--jb-navy);font-size:1.5rem;margin:0;"><i class="bi bi-box-seam"></i> Repuestos</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importarModal"><i class="bi bi-upload"></i> Importar</button>
    </div>

    <div class="search-card" style="flex-shrink:0; margin-bottom:0.5rem;">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.85rem;">Tipo</label>
                <select class="form-select" id="filtroTipo" onchange="cargarMarcas(); buscar();">
                    <option value="">Todos los tipos</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.85rem;">Marca</label>
                <select class="form-select" id="filtroMarca" onchange="cargarModelos(); buscar();">
                    <option value="">Todas las marcas</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600;font-size:0.85rem;">Modelo</label>
                <input type="text" class="form-control" id="filtroModelo" placeholder="Buscar modelo..." oninput="buscar()">
            </div>
        </div>
    </div>

    <div id="resultados" class="search-card" style="flex:1;min-height:0;overflow:hidden;">
        <p style="color:#94a3b8;text-align:center;margin:0;">Seleccione filtros para buscar repuestos.</p>
    </div>

</div>

<!-- Modal Importar CSV -->
<div class="modal fade" id="importarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Importar Repuestos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:0.9rem;">El archivo (CSV o Excel) debe tener 4 columnas: <strong>tipo, marca, modelo, precio</strong>. Sin encabezados.</p>
                    <input type="file" name="csv_file" accept=".csv,.xlsx,.xls" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
if (localStorage.getItem('jb_dark_mode') === '1') {
    document.body.classList.add('dark-mode');
}
updateDarkModeIcon();

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

function cargarTipos() {
    fetch('buscar_repuestos.php?action=tipos')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('filtroTipo');
            data.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                sel.appendChild(opt);
            });
        });
}

function cargarMarcas() {
    const tipo = document.getElementById('filtroTipo').value;
    const sel = document.getElementById('filtroMarca');
    sel.innerHTML = '<option value="">Todas las marcas</option>';
    sel.disabled = false;
    fetch('buscar_repuestos.php?action=marcas&tipo=' + encodeURIComponent(tipo))
        .then(r => r.json())
        .then(data => {
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                sel.appendChild(opt);
            });
        });
}

function cargarModelos() {
    const sel = document.getElementById('filtroModelo');
    sel.value = '';
    sel.disabled = false;
}

function buscar() {
    const tipo = document.getElementById('filtroTipo').value;
    const marca = document.getElementById('filtroMarca').value;
    const modelo = document.getElementById('filtroModelo').value;

    const params = new URLSearchParams({ action: 'buscar' });
    if (tipo) params.set('tipo', tipo);
    if (marca) params.set('marca', marca);
    if (modelo) params.set('modelo', modelo);

    fetch('buscar_repuestos.php?' + params.toString())
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('resultados');
            if (data.length === 0) {
                div.innerHTML = '<p style="color:#94a3b8;text-align:center;margin:0;">Sin resultados.</p>';
                return;
            }
            let html = '<div class="resultados-wrap"><table class="table table-repuestos table-hover mb-0">';
            html += '<thead><tr><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Precio</th></tr></thead><tbody>';
            data.forEach(r => {
                const p = r.precio ? '$ ' + Number(r.precio).toLocaleString('es-AR') : '';
                html += '<tr><td>' + esc(r.tipo) + '</td><td>' + esc(r.marca) + '</td><td>' + esc(r.modelo) + '</td><td class="precio-badge">' + p + '</td></tr>';
            });
            html += '</tbody></table></div>';
            div.innerHTML = html;
        });
}

function esc(s) { return s ? s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : ''; }

cargarTipos();
</script>
</body>
</html>
