<?php
include 'includes/verificar_sesion.php';

$limite = isset($_GET['limite']) ? min((int)$_GET['limite'], 200) : 100;

$stmt = $conn->prepare("
    SELECT el.id, el.orden_id, el.estado, el.cambiado_por, el.cambiado_por_usuario, el.fecha,
           o.marca, o.modelo
    FROM estados_log el
    LEFT JOIN ordenes o ON el.orden_id = o.id
    ORDER BY el.fecha DESC
    LIMIT ?
");
$stmt->bind_param("i", $limite);
$stmt->execute();
$result = $stmt->get_result();
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Movimientos - FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f4f8; font-size: 0.9rem; }
        .nav-jb { background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%); padding: 0.2rem 1.5rem 0.2rem 0; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .sidebar-jb { background: linear-gradient(180deg, #001845 0%, #023e8a 100%) !important; color: white; }
        .sidebar-jb .offcanvas-title { color: var(--jb-cyan); font-weight: 700; }
        .sidebar-jb .btn-close { filter: brightness(0) invert(1); }
        .sidebar-menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: rgba(255,255,255,0.85); text-decoration: none; border-radius: 8px; transition: all 0.2s; cursor: pointer; font-size: 0.95rem; border: none; background: none; width: 100%; text-align: left; }
        .sidebar-menu-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(4px); }
        .sidebar-menu-item i { font-size: 1.3rem; width: 24px; text-align: center; color: var(--jb-cyan); }
        .sidebar-divider { border-color: rgba(255,255,255,0.1); margin: 8px 0; }
        body.dark-mode .sidebar-jb { background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important; }
        .btn-hamburger { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: white; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 1.5rem; display: flex; align-items: center; cursor: pointer; }
        .btn-hamburger:hover { background: rgba(255,255,255,0.25); }
        .mov-card { background: white; border-radius: 8px; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 10px 14px; margin-bottom: 6px; }
        .mov-fecha { color: #94a3b8; font-size: 0.78rem; }
        .mov-orden { font-weight: 700; color: var(--jb-navy); }
        .mov-equipo { color: #64748b; font-size: 0.82rem; }
        body.dark-mode { background: #0f1729 !important; }
        body.dark-mode .mov-card { background: #1a2235; border-left-color: #38bdf8; }
        body.dark-mode .mov-fecha { color: #64748b; }
        body.dark-mode .mov-orden { color: #e2e8f0; }
        body.dark-mode .mov-equipo { color: #94a3b8; }
        .badge-rol { font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; }
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
            <a href="listado.php" class="sidebar-menu-item"><i class="bi bi-house-door"></i><span>Home</span></a>
            <hr class="sidebar-divider">
            <a href="repuestos.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Repuestos</span>
            </a>
            <hr class="sidebar-divider">
            <a href="clientes.php" class="sidebar-menu-item"><i class="bi bi-people"></i><span>Clientes</span></a>
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
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i><span id="sidebarTextDark">Modo Nocturno</span>
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
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú"><i class="bi bi-list"></i></button>
            <a href="listado.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.9);padding:0.4rem 0.9rem;border-radius:6px;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
        <div style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span class="nav-brand" style="color:white;font-weight:800;font-size:2rem;line-height:1;letter-spacing:-1px;font-family:'Neuropol X','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;"><i class="bi bi-activity"></i> Movimientos</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; margin-right:-0.25rem;">
            <div style="display: flex; flex-direction: column; align-items: stretch; gap: 2px; min-width:70px;">
                <span class="rol-badge" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:white;font-size:0.7rem;text-align:center;width:100%;display:block;padding:2px 10px;border-radius:20px;">
                    <?php if ($ES_ADMIN): ?>
                    <i class="bi bi-shield-lock" style="font-size:0.7rem;"></i>
                    <?php else: ?>
                    <i class="bi bi-<?php echo $ES_TECNICO ? 'tools' : 'headset'; ?>" style="font-size:0.7rem;"></i>
                    <?php endif; ?>
                    <span style="font-weight:400;font-size:0.7rem;"><?php echo htmlspecialchars($NOMBRE_USUARIO); ?></span>
                </span>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid mt-2" style="padding: 0 0.25rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <h1 style="color:var(--jb-navy);font-size:1.3rem;margin:0;"><i class="bi bi-activity"></i> Últimos Movimientos</h1>
        <div>
            <select class="form-select form-select-sm" style="width:auto;display:inline-block;" onchange="window.location.href='movimientos.php?limite='+this.value">
                <option value="50" <?php if($limite==50) echo 'selected'; ?>>50</option>
                <option value="100" <?php if($limite==100) echo 'selected'; ?>>100</option>
                <option value="200" <?php if($limite==200) echo 'selected'; ?>>200</option>
            </select>
        </div>
    </div>

    <?php while ($row = $result->fetch_assoc()):
        $badge = match($row['estado']) {
            'INGRESADO' => 'bg-secondary',
            'EN REVISION' => 'bg-info',
            'EN ESPERA' => 'bg-warning text-dark',
            'APROBADO' => 'bg-success',
            'PRESUPUESTO RECHAZADO' => 'bg-danger',
            'REPARADO' => 'bg-success',
            'SIN REPARACION' => 'bg-dark',
            'ENTREGADO' => 'bg-primary',
            'CHEQUEO FINAL' => 'bg-info',
            default => 'bg-light text-dark'
        };
        $rol_class = $row['cambiado_por'] == 'tecnico' ? 'bg-info text-dark' : 'bg-primary';
        $usuario_mostrar = !empty($row['cambiado_por_usuario']) ? htmlspecialchars($row['cambiado_por_usuario']) : ($row['cambiado_por'] == 'tecnico' ? 'Técnico' : 'Recepción');
    ?>
    <a href="detalle.php?id=<?php echo $row['orden_id']; ?>" style="text-decoration:none;color:inherit;">
    <div class="mov-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            <div>
                <span class="mov-orden">#<?php echo $row['orden_id']; ?></span>
                <span class="mov-equipo"> <?php echo htmlspecialchars($row['marca'] . ' ' . $row['modelo']); ?></span>
            </div>
            <div>
                <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['estado']); ?></span>
                <span class="badge <?php echo $rol_class; ?> badge-rol"><i class="bi bi-<?php echo $row['cambiado_por'] == 'tecnico' ? 'tools' : 'headset'; ?>"></i> <?php echo $usuario_mostrar; ?></span>
            </div>
        </div>
        <div class="mov-fecha"><i class="bi bi-clock"></i> <?php echo date('d/m/Y H:i:s', strtotime($row['fecha'])); ?></div>
    </div>
    </a>
    <?php endwhile; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
if (localStorage.getItem('jb_dark_mode') === '1') { document.body.classList.add('dark-mode'); }
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
</body>
</html>