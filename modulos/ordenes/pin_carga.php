<?php
include 'includes/verificar_sesion.php';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pin de Carga - FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f4f8; }
        .nav-jb { background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%); padding: 0.2rem 1.5rem 0.2rem 0; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .search-card { background: white; border-radius: 12px; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 20px; }
        .result-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; }
        .precio-badge { font-size: 1.1rem; font-weight: 700; color: var(--jb-azul); }
        body.dark-mode { background: #0f1729 !important; }
        body.dark-mode .search-card { background: #1a2235 !important; }
        body.dark-mode .result-row { border-bottom-color: #2d3748; }
        body.dark-mode .precio-badge { color: #38bdf8; }
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

        @media (max-width: 767px) {
            .nav-center { position: absolute; left: 50%; transform: translateX(-50%); }
            .nav-left, .nav-right { min-width: 40px; }
            .nav-left .nav-btn { padding: 0.25rem 0.5rem !important; font-size: 0.7rem !important; gap: 3px !important; }
        }
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
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="repuestos.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.9);padding:0.4rem 0.9rem;border-radius:6px;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span class="nav-brand" style="color:white;font-weight:800;font-size:2rem;line-height:1;letter-spacing:-1px;font-family:'Neuropol X','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                <span style="position:relative;display:inline-block;">
                    <i class="bi bi-phone"></i>
                    <i class="bi bi-lightning-fill" style="position:absolute;font-size:0.55em;bottom:-2px;right:-4px;color:#ffd700;"></i>
                </span>
                Pin de carga
            </span>
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

<div class="container mt-3">
    <h1 style="color:var(--jb-navy);font-size:1.5rem;">
        <span style="position:relative;display:inline-block;">
            <i class="bi bi-phone"></i>
            <i class="bi bi-lightning-fill" style="position:absolute;font-size:0.55em;bottom:-2px;right:-4px;color:#ffd700;"></i>
        </span>
        Pin de carga
    </h1>

    <div class="search-card" id="listaPines">
        <?php
        $pines = $conn->query("SELECT id, tipo, precio FROM pin_carga ORDER BY id");
        while ($p = $pines->fetch_assoc()):
            $precio = $p['precio'] ? '$ ' . number_format((float)$p['precio'], 0, ',', '.') : 'Consultar';
        ?>
        <div class="result-row">
            <span><strong><?php echo htmlspecialchars($p['tipo']); ?></strong></span>
            <span class="precio-badge"><?php echo $precio; ?></span>
        </div>
        <?php endwhile; ?>
    </div>
</div>

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

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>