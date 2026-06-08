<?php
include 'includes/verificar_sesion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT 
        ordenes.*,
        clientes.nombre AS cliente_nombre,
        clientes.dni,
        clientes.telefono
    FROM ordenes
    INNER JOIN clientes ON ordenes.cliente_id = clientes.id
    WHERE ordenes.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$orden = $resultado->fetch_assoc();

if (!$orden) {
    die("Orden no encontrada");
}

$protocol_d = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$tracking_url_d = $protocol_d . '://' . $_SERVER['HTTP_HOST'] . '/seguimiento.php?token=' . ($orden['token'] ?? '');
$cfg_result_w = $conn->query("SELECT clave, valor FROM configuracion WHERE clave IN ('taller_nombre')");
$taller_nombre_w = 'FullTaller';
if ($cfg_result_w) {
    while ($cfg_row_w = $cfg_result_w->fetch_assoc()) {
        if ($cfg_row_w['clave'] === 'taller_nombre') $taller_nombre_w = $cfg_row_w['valor'];
    }
}
$whatsapp_msg = "Hola nos comunicamos de {$taller_nombre_w} por la orden N° {$orden['id']}, ";

// Obtener notas de la orden
$notas = [];
$stmt_notas = $conn->prepare("SELECT * FROM notas WHERE orden_id = ? ORDER BY fecha ASC");
$stmt_notas->bind_param("i", $id);
$stmt_notas->execute();
$result_notas = $stmt_notas->get_result();
while ($n = $result_notas->fetch_assoc()) {
    $notas[] = $n;
}

// Determinar label del autor actual
$label_autor = $ES_RECEPCION ? 'Recepción' : 'Técnico';
$icono_autor = $ES_RECEPCION ? 'headset' : 'tools';

// Obtener historial de estados
$estados_log = [];
$conn->query("CREATE TABLE IF NOT EXISTS estados_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    estado VARCHAR(50) NOT NULL,
    cambiado_por VARCHAR(20) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (orden_id)
)");
$stmt_log = $conn->prepare("SELECT * FROM estados_log WHERE orden_id = ? ORDER BY fecha ASC");
$stmt_log->bind_param("i", $id);
$stmt_log->execute();
$result_log = $stmt_log->get_result();
while ($log = $result_log->fetch_assoc()) {
    $estados_log[] = $log;
}

// Fetch all chequeo final records for this order
$chequeos = [];
$stmt_chk = $conn->prepare("SELECT * FROM chequeo_final WHERE orden_id = ? ORDER BY fecha DESC");
$stmt_chk->bind_param("i", $id);
$stmt_chk->execute();
$chequeos = $stmt_chk->get_result()->fetch_all(MYSQLI_ASSOC);
$items_chk = ['imagen'=>'Imagen','touch'=>'Touch','brillo'=>'Brillo','receiver'=>'Receiver','camaras'=>'Cámaras','microfono'=>'Micrófono','altavoz'=>'Altavoz','sensor'=>'Sensor','wifi'=>'WiFi','botones'=>'Botones','pegado'=>'Pegado','carga'=>'Carga'];

// Fetch fotos for this order
$fotos = [];
$stmt_fotos = $conn->prepare("SELECT * FROM fotos WHERE orden_id = ? ORDER BY created_at ASC");
$stmt_fotos->bind_param("i", $id);
$stmt_fotos->execute();
$result_fotos = $stmt_fotos->get_result();
while ($f = $result_fotos->fetch_assoc()) {
    $fotos[] = $f;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detalle Orden #<?php echo htmlspecialchars($orden['id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Colores FullTaller - basados en el logo */
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
        }

        html, body {
            height: 100vh;
        }
        @media (min-width: 768px) {
            html, body { overflow: hidden; }
            .fila3-wrap { flex-wrap: nowrap !important; }
        }

        body {
            background-color: #f0f4f8;
        }

        .page-content {
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            padding-bottom: 6px;
        }
        .page-content::-webkit-scrollbar { width: 6px; }
        .page-content::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .page-content::-webkit-scrollbar-thumb { background: var(--jb-azul); border-radius: 3px; }

        /* Navbar con botones */
        .nav-jb {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            padding: 0.2rem 1.5rem 0.2rem 0;
            margin-bottom: 0;
            box-shadow: 0 2px 10px rgba(0,56,168,0.3);
        }
        .nav-jb .nav-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .nav-jb .nav-brand {
            color: white;
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Neuropol X', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .nav-jb .nav-brand i {
            font-size: 1.6rem;
            color: var(--jb-cyan);
        }
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
        .nav-jb .nav-btn:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }
        .nav-jb .nav-btn.active {
            background-color: var(--jb-cyan);
            color: var(--jb-navy);
            font-weight: 600;
            border-color: var(--jb-cyan);
        }
        .nav-jb .nav-sep {
            color: rgba(255,255,255,0.3);
            margin: 0 0.25rem;
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

        /* Cards con borde azul sutil */
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-left: 3px solid var(--jb-azul);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e9ecef;
            color: var(--jb-azul-oscuro);
            font-weight: 600;
            padding: 0.3rem 0.7rem;
            font-size: 0.85rem;
        }
        .card-header i {
            color: var(--jb-cyan);
        }

        /* Badges */
        .badge-express {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: white;
        }
        .badge-normal {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            color: white;
        }

        .sena-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 8px;
        }
        .sena-monto {
            font-size: 1.1rem;
            font-weight: bold;
            color: #155724;
        }
        .observaciones-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 8px;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }
        .card-body p {
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }
        .card-body {
            padding: 0.4rem 0.7rem;
        }
        .info-section {
            height: 100%;
        }

        /* Botones de imprimir */
        .btn-jb-primary {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none;
            color: white;
        }
        .btn-jb-primary:hover {
            background: linear-gradient(135deg, var(--jb-azul-oscuro), var(--jb-navy));
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,119,182,0.3);
        }
        .btn-jb-dark {
            background: linear-gradient(135deg, #495057, #212529);
            border: none;
            color: white;
        }
        .btn-jb-dark:hover {
            background: linear-gradient(135deg, #212529, #000);
            color: white;
        }

        /* ===== CHAT / NOTAS ===== */
        .chat-box {
            flex: 1;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: #f8fafc;
            margin-bottom: 8px;
            min-height: 60px;
        }
        .chat-box::-webkit-scrollbar {
            width: 6px;
        }
        .chat-box::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 3px;
        }
        .chat-box::-webkit-scrollbar-thumb {
            background: var(--jb-azul);
            border-radius: 3px;
        }
        .nota {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            max-width: 85%;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .nota-recepcion {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
            margin-right: auto;
            color: #1e3a5f;
        }
        .nota-tecnico {
            background-color: #dcfce7;
            border: 1px solid #86efac;
            margin-left: auto;
            text-align: right;
            color: #14532d;
        }
        .nota-header {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .nota-recepcion .nota-header {
            color: #1d4ed8;
        }
        .nota-tecnico .nota-header {
            color: #15803d;
            justify-content: flex-end;
        }
        .nota-fecha {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-left: auto;
        }
        .nota-tecnico .nota-fecha {
            margin-left: 0;
            margin-right: auto;
        }
        .nota-texto {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .chat-input-area {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .chat-input {
            flex: 1;
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
            font-size: 0.9rem;
            resize: none;
            min-height: 60px;
        }
        .btn-enviar-nota {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            height: fit-content;
        }
        .btn-enviar-nota:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,119,182,0.3);
        }
        .btn-enviar-nota:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .sin-notas {
            text-align: center;
            color: #94a3b8;
            padding: 20px;
            font-style: italic;
        }

        /* Hamburguesa + Sidebar */
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
        /* Fin Hamburguesa + Sidebar */

        /* ===== MODO NOCTURNO ===== */
        body.dark-mode {
            background-color: #0f1729 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .card {
            background-color: #1a2235 !important;
            border-left-color: var(--jb-cyan) !important;
            color: #e2e8f0;
        }
        body.dark-mode .card-header {
            background-color: #1a2235 !important;
            border-bottom-color: #2d3748 !important;
            color: #e2e8f0;
        }
        body.dark-mode .observaciones-box {
            background-color: #0c2d4a !important;
            border-color: #1e4d7a !important;
            color: #7dd3fc !important;
        }
        body.dark-mode h1 {
            color: #e2e8f0 !important;
        }
        body.dark-mode .nav-jb .nav-brand {
            color: white !important;
        }
        body.dark-mode .nav-jb .nav-btn {
            color: rgba(255,255,255,0.9) !important;
        }
        body.dark-mode .nav-jb .nav-btn.active {
            color: var(--jb-navy) !important;
        }
        body.dark-mode .nav-jb .nav-sep {
            color: rgba(255,255,255,0.3) !important;
        }
        body.dark-mode .btn-jb-primary {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro)) !important;
        }
        body.dark-mode .btn-jb-dark {
            background: linear-gradient(135deg, #4b5563, #1f2937) !important;
        }
        body.dark-mode .nota-recepcion {
            background-color: #1e3a5f !important;
            border-color: #2d5a8a !important;
            color: #93c5fd !important;
        }
        body.dark-mode .nota-recepcion .nota-header {
            color: #60a5fa !important;
        }
        body.dark-mode .nota-tecnico {
            background-color: #1a4a2e !important;
            border-color: #2d6a4f !important;
            color: #86efac !important;
        }
        body.dark-mode .nota-tecnico .nota-header {
            color: #4ade80 !important;
        }
        body.dark-mode .chat-box {
            background-color: #0f1729 !important;
            border-color: #2d3748 !important;
        }
        body.dark-mode .chat-input {
            background-color: #1a2235 !important;
            border-color: #2d3748 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .sin-notas {
            color: #64748b;
        }
        body.dark-mode .sena-box {
            background-color: #0c2d1a !important;
            border-color: #1a5a30 !important;
            color: #86efac !important;
        }
        body.dark-mode .sena-monto { color: #4ade80 !important; }
        body.dark-mode h6 { color: #7dd3fc !important; }
        body.dark-mode .card-body p.text-muted { color: #94a3b8 !important; }
        body.dark-mode .foto-item { border-color: #2d3748 !important; }
        body.dark-mode #formChequeo > div {
            background: #1a2235 !important;
            border-color: #2d3748 !important;
        }
        body.dark-mode #formChequeo > div > span {
            color: #94a3b8 !important;
        }
        #formChequeo label {
            background: white;
            border: 1px solid #d1d5db;
        }
        body.dark-mode #formChequeo label {
            background: #1e293b;
            border-color: #475569;
        }

        .nav-left, .nav-right { min-width: 40px; }
        .nav-right { justify-content: flex-end; }
        @media (max-width: 767px) {
            .nav-center { position: absolute; left: 50%; transform: translateX(-50%); }
            .nav-left, .nav-right { min-width: 40px; }
            .nav-left .nav-btn { padding: 0.25rem 0.5rem !important; font-size: 0.7rem !important; gap: 3px !important; }
        }
        .nav-jb { padding: 0; }
        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }
    </style>
</head>
<body>

<!-- Navbar -->
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

<div class="page-content px-1">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center" style="padding:4px 0;">
        <h1 style="color:var(--jb-navy); font-size:1.1rem; margin:0;">Orden #<span id="ordenIdDisplay"><?php echo htmlspecialchars($orden['id']); ?></span></h1>
        <div>
            <?php if ($orden['express'] == 1): ?>
                <span class="badge badge-express p-1" style="font-size:0.7rem;"><i class="bi bi-lightning-charge-fill"></i> EXPRESS 24HS</span>
            <?php else: ?>
                <span class="badge badge-normal p-1" style="font-size:0.7rem;"><i class="bi bi-clock"></i> Standard</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILA 1: Cliente + Equipo + Estado -->
    <div class="row g-2 mb-2">
        <div class="col-md-4">
            <div class="card info-section">
                <div class="card-header py-1" style="font-size:0.9rem;"><i class="bi bi-person"></i> Cliente</div>
                <div class="card-body py-1">
                    <p class="mb-1" style="font-size:0.9rem;"><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['cliente_nombre']); ?> <a href="whatsapp://send?phone=54<?php echo $orden['telefono']; ?>&text=<?php echo urlencode($whatsapp_msg); ?>" title="WhatsApp" style="text-decoration:none;"><i class="bi bi-whatsapp" style="color:#25D366;font-size:1.1rem;"></i></a><?php if (!empty($orden['token'])): ?> <a href="whatsapp://send?phone=54<?php echo $orden['telefono']; ?>&text=<?php echo urlencode("Seguí el estado de tu orden #{$orden['id']} acá: {$tracking_url_d}"); ?>" title="Enviar seguimiento" style="text-decoration:none;"><i class="bi bi-link-45deg" style="color:#1a1a2e;font-size:1.1rem;"></i></a><?php endif; ?></p>
                    <p class="mb-1" style="font-size:0.9rem;"><strong>DNI:</strong> <?php echo htmlspecialchars($orden['dni']); ?></p>
                    <p class="mb-0" style="font-size:0.9rem;"><strong>Teléfono:</strong> <?php echo htmlspecialchars($orden['telefono']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card info-section">
                <div class="card-header py-1" style="font-size:0.9rem;"><i class="bi bi-phone"></i> Equipo</div>
                <div class="card-body py-1">
                    <p class="mb-1" style="font-size:0.9rem;"><strong>IMEI:</strong> <?php echo htmlspecialchars($orden['imei']); ?></p>
                    <p class="mb-1" style="font-size:0.9rem;"><strong>Tipo:</strong> <?php echo htmlspecialchars($orden['tipo']); ?></p>
                    <p class="mb-1" style="font-size:0.9rem;"><strong>Marca:</strong> <?php echo htmlspecialchars($orden['marca']); ?></p>
                    <p class="mb-0" style="font-size:0.9rem;"><strong>Modelo:</strong> <?php echo htmlspecialchars($orden['modelo']); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card info-section">
                <div class="card-header py-1" style="font-size:0.9rem;"><i class="bi bi-info-circle"></i> Estado</div>
                <div class="card-body py-1">
                    <p class="mb-1" style="font-size:0.9rem;"><strong>Estado:</strong> 
                        <span class="badge bg-<?php 
                            echo match($orden['estado']) {
                                'INGRESADO' => 'secondary',
                                'EN REVISION' => 'info',
                                'EN ESPERA' => 'warning',
                                'APROBADO' => 'success',
                                'PRESUPUESTO RECHAZADO' => 'danger',
                                'REPARADO' => 'success',
                                'SIN REPARACION' => 'dark',
                                'ENTREGADO' => 'primary',
                                default => 'light'
                            };
                        ?>">
                            <?php echo htmlspecialchars($orden['estado']); ?>
                        </span>
                    </p>
                    <p class="mb-0" style="font-size:0.9rem;"><strong>Ingreso:</strong> <?php echo date('d/m/Y H:i', strtotime($orden['fecha_ingreso'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- FILA 2: Falla+Obs + Presupuesto -->
    <div class="row g-2 mb-2">
        <div class="col-md-8">
            <div class="card info-section" style="height:100%;">
                <div class="card-header py-1" style="font-size:0.9rem;"><i class="bi bi-exclamation-triangle"></i> Falla y Observaciones</div>
                <div class="card-body py-1">
                    <p class="mb-1" style="font-size:0.9rem;"><strong>Falla:</strong> <?php echo nl2br(htmlspecialchars($orden['falla'])); ?></p>
                    <?php if (!empty($orden['observaciones'])): ?>
                        <p class="mb-1" style="font-size:0.9rem;"><strong>Observaciones:</strong></p>
                        <div class="observaciones-box" style="font-size:0.85rem; padding:4px 8px;"><?php echo nl2br(htmlspecialchars($orden['observaciones'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card info-section" style="height:100%;">
                <div class="card-header py-1" style="font-size:0.9rem;"><i class="bi bi-cash"></i> Presupuesto</div>
                <div class="card-body py-1">
                    <p class="mb-1" style="font-size:0.9rem;">
                        <strong>Presupuesto:</strong>
                        <span id="valPresupuesto">$<?php echo number_format($orden['presupuesto'], 2); ?></span>
                        <a href="#" onclick="event.preventDefault();editarCampo('presupuesto',<?php echo $orden['presupuesto']; ?>)" style="font-size:0.75rem;color:var(--jb-cyan);text-decoration:none;margin-left:4px;"><i class="bi bi-pencil"></i></a>
                    </p>
                    <?php if ($ES_ADMIN): ?>
                    <p class="mb-1" style="font-size:0.9rem;">
                        <strong>Costo:</strong>
                        <span id="valCosto">$<?php echo number_format($orden['costo'] ?? 0, 2); ?></span>
                        <a href="#" onclick="event.preventDefault();editarCampo('costo',<?php echo $orden['costo'] ?? 0; ?>)" style="font-size:0.75rem;color:var(--jb-cyan);text-decoration:none;margin-left:4px;"><i class="bi bi-pencil"></i></a>
                    </p>
                    <?php endif; ?>
                    <div id="senaDisplay">
                        <?php if ($orden['sena'] > 0): ?>
                            <div class="sena-box" style="padding:4px 8px; font-size:0.85rem;">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>Seña:</strong> <span id="valSena">$<?php echo number_format($orden['sena'], 2); ?></span>
                                <a href="#" onclick="event.preventDefault();editarCampo('sena',<?php echo $orden['sena']; ?>)" style="font-size:0.75rem;color:var(--jb-cyan);text-decoration:none;margin-left:4px;"><i class="bi bi-pencil"></i></a>
                                <br><small>Saldo: $<?php echo number_format($orden['presupuesto'] - $orden['sena'], 2); ?></small>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0" style="font-size:0.85rem;">
                                <i class="bi bi-x-circle"></i> Sin seña
                                <a href="#" onclick="event.preventDefault();editarCampo('sena',0)" style="font-size:0.75rem;color:var(--jb-cyan);text-decoration:none;margin-left:4px;"><i class="bi bi-pencil"></i></a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILA 3: Chat + Chequeo + Historial -->
    <div class="row g-2 fila3-wrap" style="flex:1; min-height:0;">
        <div class="col-md-6 d-flex flex-column" style="gap:4px; min-height:0;">
            <h6 style="font-size:0.9rem; color:var(--jb-azul-oscuro); margin:0;"><i class="bi bi-gear"></i> Opciones</h6>
            <div style="display:flex;gap:4px;">
                <a href="imprimir_cliente.php?id=<?php echo $orden['id']; ?>" class="btn btn-jb-primary" style="flex:1;padding:0.25rem;font-size:0.7rem;text-align:center;" target="_blank" title="Imprimir Cliente">
                    <i class="bi bi-printer"></i>
                </a>
                <a href="imprimir_taller.php?id=<?php echo $orden['id']; ?>" class="btn btn-jb-dark" style="flex:1;padding:0.25rem;font-size:0.7rem;text-align:center;" target="_blank" title="Imprimir Taller">
                    <i class="bi bi-tools"></i>
                </a>
                <a href="clientes.php?editar=<?php echo $orden['cliente_id']; ?>" class="btn btn-outline-primary" style="flex:1;padding:0.25rem;font-size:0.7rem;text-align:center;" title="Ficha del Cliente">
                    <i class="bi bi-person"></i>
                </a>
                <?php if (count($fotos) < 3): ?>
                <label class="btn" style="flex:1;padding:0.25rem;font-size:0.7rem;text-align:center;background:var(--jb-cyan);color:white;border:none;border-radius:6px;cursor:pointer;" title="Agregar Foto">
                    <i class="bi bi-camera"></i>
                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="subirFoto(<?php echo $id; ?>, this)">
                </label>
                <?php endif; ?>
            </div>
            <div id="fotosContainer" style="display:flex;gap:4px;flex-wrap:wrap;<?php echo empty($fotos) ? 'display:none;' : ''?>">
                <?php foreach ($fotos as $f): ?>
                <div class="foto-item" data-id="<?php echo $f['id']; ?>" style="position:relative;width:56px;height:56px;border-radius:6px;overflow:hidden;border:1px solid #e2e8f0;flex-shrink:0;">
                    <img src="uploads/<?php echo htmlspecialchars($f['filename']); ?>" style="width:100%;height:100%;object-fit:cover;display:block;" onclick="window.open(this.src,'_blank')">
                    <button type="button" onclick="eliminarFoto(<?php echo $f['id']; ?>)" style="position:absolute;top:1px;right:1px;background:rgba(0,0,0,0.6);color:white;border:none;border-radius:50%;width:16px;height:16px;font-size:0.5rem;line-height:16px;text-align:center;padding:0;cursor:pointer;">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card" style="flex:1; display:flex; flex-direction:column; min-height:0;">
                <div class="card-header py-1" style="font-size:0.9rem;">
                    <i class="bi bi-chat-dots"></i> Chat Interno
                    <span class="badge bg-secondary ms-2" style="font-size:0.75rem;">
                        <i class="bi bi-<?php echo $icono_autor; ?>"></i> <?php echo $label_autor; ?>
                    </span>
                </div>
                <div class="card-body py-1 d-flex flex-column" style="flex:1; min-height:0;">
                    <div class="chat-box" id="chatBox" style="flex:1; overflow-y:auto; border:1px solid #dee2e6; border-radius:6px; padding:6px; background:#f8fafc; margin-bottom:4px;">
                        <?php if (empty($notas)): ?>
                            <div class="sin-notas" id="sinNotas">
                                <i class="bi bi-chat-square-text" style="font-size:1.5rem; display:block; margin-bottom:2px;"></i>
                                No hay mensajes aún. Comenzá la conversación...
                            </div>
                        <?php else: ?>
<?php foreach ($notas as $n):
    $es_tecnico = ($n['autor'] == 'tecnico');
    $clase = $es_tecnico ? 'nota-tecnico' : 'nota-recepcion';
    $icono_nota = $es_tecnico ? 'tools' : 'headset';
    $label_nota = !empty($n['autor_nombre']) ? htmlspecialchars($n['autor_nombre']) : ($es_tecnico ? 'Técnico' : 'Recepción');
    $fecha_formateada = date('d/m/Y H:i', strtotime($n['fecha']));
?>
                                <div class="nota <?php echo $clase; ?>">
                                    <div class="nota-header">
                                        <i class="bi bi-<?php echo $icono_nota; ?>"></i> <?php echo $label_nota; ?>
                                        <span class="nota-fecha"><?php echo $fecha_formateada; ?></span>
                                    </div>
                                    <div class="nota-texto"><?php echo nl2br(htmlspecialchars($n['mensaje'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="chat-input-area" style="margin-top:0;">
                        <textarea id="mensajeNota" class="chat-input" placeholder="Escribí un mensaje como <?php echo $label_autor; ?>..." style="font-size:0.85rem;padding:4px 8px;"></textarea>
                        <button type="button" class="btn-enviar-nota" onclick="enviarNota(<?php echo $id; ?>)" style="font-size:0.85rem;padding:4px 12px;"><i class="bi bi-send"></i> Enviar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex flex-column" style="min-height:0;height:100%;overflow:hidden;">
            <div class="card" style="flex:1; display:flex; flex-direction:column;min-height:0;">
                <div class="card-body d-flex flex-column" style="gap:4px;padding:6px; flex:1; min-height:0;">
                    <!-- FORMULARIO CHEQUEO -->
                    <h6 style="font-size:0.75rem; color:var(--jb-azul-oscuro); margin:0 0 2px 0;"><i class="bi bi-clipboard-check"></i> Chequeo Final</h6>
                    <form id="formChequeo" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:2px;">
                        <input type="hidden" name="orden_id" value="<?php echo $id; ?>">
                        <?php foreach ($items_chk as $k => $v): ?>
                        <div style="display:flex;align-items:center;gap:2px;padding:2px 3px;border-radius:3px;background:#f8fafc;border:1px solid #e2e8f0;">
                            <span style="font-size:0.65rem;font-weight:600;min-width:40px;color:#1e293b;"><?php echo $v; ?></span>
                            <label onclick="seleccionarChequeo(this,'si')" style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:1px 5px;border-radius:2px;font-size:0.6rem;transition:all 0.15s;min-width:24px;">
                                <input type="radio" name="<?php echo $k; ?>" value="1" required style="display:none;"> Sí
                            </label>
                            <label onclick="seleccionarChequeo(this,'no')" style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:1px 5px;border-radius:2px;font-size:0.6rem;transition:all 0.15s;min-width:24px;">
                                <input type="radio" name="<?php echo $k; ?>" value="0" required style="display:none;"> No
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <div style="grid-column:1/-1;margin-top:2px;">
                            <button type="button" class="btn btn-sm" style="background:var(--jb-cyan);color:white;border:none;border-radius:3px;padding:0.2rem 0.6rem;font-size:0.7rem;width:100%;" onclick="guardarChequeo()"><i class="bi bi-save"></i> Guardar Chequeo</button>
                        </div>
                    </form>

                    <hr style="margin:2px 0;">
                    <h6 style="font-size:0.9rem; color:var(--jb-azul-oscuro); margin:0;"><i class="bi bi-clock-history"></i> Historial de Movimientos</h6>
                    <div style="font-size:0.65rem; overflow-y:auto; flex:1; min-height:0;">
                        <?php if (empty($estados_log)): ?>
                            <span class="text-muted">Sin registros</span>
                        <?php else:
                            $chk_idx = 0;
                            $chk_reversed = array_reverse($chequeos);
                        ?>
                            <?php foreach ($estados_log as $log): 
                                $icono_rol = match($log['cambiado_por']) { 'admin' => 'shield-lock', 'tecnico' => 'tools', default => 'headset' };
                                $nombre = !empty($log['cambiado_por_usuario']) ? htmlspecialchars($log['cambiado_por_usuario']) : '';
                            ?>
                                <div style="display:flex; align-items:flex-start; gap:4px; padding:3px 0; border-bottom:1px solid #eee; flex-wrap:wrap;">
                                    <span class="badge bg-<?php 
                                        echo match($log['estado']) {
                                            'INGRESADO' => 'secondary',
                                            'EN REVISION' => 'info',
                                            'EN ESPERA' => 'warning',
                                            'APROBADO' => 'success',
                                            'PRESUPUESTO RECHAZADO' => 'danger',
                                            'REPARADO' => 'success',
                                            'SIN REPARACION' => 'dark',
                                            'ENTREGADO' => 'primary',
                                            'CHEQUEO FINAL' => 'info',
                                            'PRESUPUESTO' => 'primary',
                                            'SEÑA' => 'success',
                                            default => 'light'
                                        };
                                    ?>" style="font-size:0.55rem; padding:1px 4px; flex-shrink:0;"><?php echo $log['estado']; ?></span>
                                    <?php if ($log['estado'] === 'CHEQUEO FINAL' && isset($chk_reversed[$chk_idx])): 
                                        $chk = $chk_reversed[$chk_idx];
                                        $fallados = [];
                                        foreach ($items_chk as $k => $v) {
                                            if (!$chk[$k]) $fallados[] = $v;
                                        }
                                        $chk_idx++;
                                    ?>
                                        <span style="color:#64748b;font-size:0.6rem;"><i class="bi bi-<?php echo $icono_rol; ?>"></i> <?php echo $nombre; ?></span>
                                        <?php if (!empty($fallados)): ?>
                                        <span style="font-size:0.6rem; color:#991b1b; font-weight:600;">
                                            <?php echo implode(', ', $fallados); ?>
                                        </span>
                                        <?php else: ?>
                                        <span style="font-size:0.6rem; color:#065f46; font-weight:600;">Todo OK</span>
                                        <?php endif; ?>
                                        <span style="margin-left:auto; color:#94a3b8;font-size:0.6rem; flex-shrink:0;"><?php echo date('d/m H:i', strtotime($log['fecha'])); ?></span>
                                    <?php else: ?>
                                        <span style="color:#64748b;font-size:0.6rem;"><i class="bi bi-<?php echo $icono_rol; ?>"></i> <?php echo $nombre; ?></span>
                                        <span style="margin-left:auto; color:#94a3b8;font-size:0.6rem;"><?php echo date('d/m H:i', strtotime($log['fecha'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function enviarNota(ordenId) {
    const mensaje = document.getElementById('mensajeNota').value.trim();
    const btn = document.querySelector('.btn-enviar-nota');
    if (!mensaje) { alert('Escriba un mensaje'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
    const formData = new FormData();
    formData.append('orden_id', ordenId);
    // Ya no se envía 'autor', se detecta automáticamente en el servidor por sesión
    formData.append('mensaje', mensaje);
    fetch('guardar_nota.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const chatBox = document.getElementById('chatBox');
            const emptyMsg = chatBox.querySelector('.sin-notas');
            if (emptyMsg) emptyMsg.remove();
            const cls = data.autor == 'tecnico' ? 'nota-tecnico' : 'nota-recepcion';
            const icon = data.autor == 'tecnico' ? 'tools' : 'headset';
            const label = data.autor_nombre || (data.autor == 'tecnico' ? 'Técnico' : 'Recepción');
            chatBox.insertAdjacentHTML('beforeend', `
                <div class="nota ${cls}">
                    <div class="nota-header">
                        <i class="bi bi-${icon}"></i> ${label}
                        <span class="nota-fecha">${data.fecha}</span>
                    </div>
                    <div class="nota-texto">${mensaje.replace(/\\n/g, '<br>')}</div>
                </div>
            `);
            chatBox.scrollTop = chatBox.scrollHeight;
            document.getElementById('mensajeNota').value = '';
        } else {
            alert('Error: ' + (data.error || 'No se pudo guardar'));
        }
    })
    .catch(err => { console.error(err); alert('Error de conexión'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Enviar'; });
}

window.addEventListener('load', () => {
    const chatBox = document.getElementById('chatBox');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
});
</script>

<script>
let ultimoTelefonoChequeo = '';
let ultimoTokenChequeo = '';
let ultimoTallerChequeo = '';

function guardarChequeo() {
    const form = document.getElementById('formChequeo');
    if (!form) { alert('Error: formulario no encontrado'); return; }
    const campos = ['imagen','touch','brillo','receiver','camaras','microfono','altavoz','sensor','wifi','botones','pegado','carga'];
    for (const c of campos) {
        if (!form.querySelector(`input[name="${c}"]:checked`)) {
            alert('Falta responder: ' + c.charAt(0).toUpperCase() + c.slice(1));
            return;
        }
    }
    const formData = new FormData(form);
    fetch('guardar_chequeo.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { alert('Error: ' + d.error); return; }
        if (d.all_ok && d.telefono) {
            ultimoTelefonoChequeo = d.telefono;
            ultimoTokenChequeo = d.token;
            ultimoTallerChequeo = d.taller_nombre;
            document.getElementById('telefonoChequeo').textContent = d.telefono;
            const modal = new bootstrap.Modal(document.getElementById('modalChequeoWhatsApp'));
            modal.show();
        } else {
            location.reload();
        }
    })
    .catch(e => { console.error(e); alert('Error de conexión: ' + e.message); });
}

function enviarWhatsAppChequeo() {
    const msg = 'Hola nos comunicamos de ' + ultimoTallerChequeo + ' por la orden N° ' + document.getElementById('ordenIdDisplay').textContent + ', el equipo se encuentra reparado listo para retirar';
    window.open('whatsapp://send?phone=54' + ultimoTelefonoChequeo + '&text=' + encodeURIComponent(msg), '_blank');
    location.reload();
}

function subirFoto(ordenId, input) {
    if (!input.files || !input.files[0]) return;
    const formData = new FormData();
    formData.append('orden_id', ordenId);
    formData.append('foto', input.files[0]);
    fetch('subir_foto.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo subir la foto'));
            input.value = '';
        }
    })
    .catch(e => { alert('Error de conexión'); input.value = ''; });
}

function eliminarFoto(id) {
    if (!confirm('¿Eliminar esta foto?')) return;
    const formData = new FormData();
    formData.append('id', id);
    fetch('eliminar_foto.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo eliminar'));
        }
    })
    .catch(e => { alert('Error de conexión'); });
}

function editarCampo(campo, valorActual) {
    const cont = campo === 'presupuesto'
        ? document.getElementById('valPresupuesto').parentNode
        : campo === 'costo'
            ? document.getElementById('valCosto').parentNode
            : document.getElementById('senaDisplay');
    const input = document.createElement('span');
    input.innerHTML = '<input type="number" step="0.01" class="form-control form-control-sm" style="display:inline-block;width:100px;font-size:0.85rem;" id="inputEdit" value="' + valorActual + '"> ' +
        '<button class="btn btn-sm btn-success" style="padding:0.1rem 0.4rem;font-size:0.7rem;" onclick="guardarCampo(\'' + campo + '\',' + <?php echo $id; ?> + ')"><i class="bi bi-check"></i></button> ' +
        '<button class="btn btn-sm btn-secondary" style="padding:0.1rem 0.4rem;font-size:0.7rem;" onclick="cancelarEdicion(this)"><i class="bi bi-x"></i></button>';
    if (campo === 'presupuesto' || campo === 'costo') {
        const p = document.getElementById(campo === 'presupuesto' ? 'valPresupuesto' : 'valCosto');
        p.style.display = 'none';
        p.parentNode.insertBefore(input, p.nextSibling);
    } else {
        cont.innerHTML = '';
        cont.appendChild(input);
    }
}

function cancelarEdicion(btn) {
    location.reload();
}

function guardarCampo(campo, ordenId) {
    const val = document.getElementById('inputEdit').value;
    const formData = new FormData();
    formData.append('orden_id', ordenId);
    if (campo === 'costo') {
        formData.append('costo', val);
    } else {
        const otroCampo = campo === 'presupuesto' ? 'sena' : 'presupuesto';
        const otroVal = document.getElementById(otroCampo === 'presupuesto' ? 'valPresupuesto' : 'valSena');
        const otroNum = otroVal ? parseFloat(otroVal.textContent.replace(/[^0-9.,]/g,'').replace(',','.')) || 0 : 0;
        if (campo === 'presupuesto') {
            formData.append('presupuesto', val);
            formData.append('sena', otroNum);
        } else {
            formData.append('presupuesto', otroNum);
            formData.append('sena', val);
        }
    }
    fetch('guardar_presupuesto.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.ok) location.reload();
        else alert('Error: ' + (data.error || 'No se pudo guardar'));
    })
    .catch(e => { alert('Error de conexión'); });
}

function seleccionarChequeo(label, valor) {
    label.querySelector('input[type=radio]').checked = true;
    const isDark = document.body.classList.contains('dark-mode');
    const otros = label.parentElement.querySelectorAll('label');
    if (valor === 'si') {
        label.style.background = isDark ? '#0e4a7a' : '#e0f2fe';
        label.style.color = isDark ? 'white' : '#1e293b';
        label.style.borderColor = 'var(--jb-cyan)';
    } else {
        label.style.background = isDark ? '#7f1d1d' : '#fee2e2';
        label.style.color = isDark ? 'white' : '#1e293b';
        label.style.borderColor = '#ef4444';
    }
    otros.forEach(l => {
        if (l !== label) {
            l.style.background = isDark ? '#1e293b' : 'white';
            l.style.color = isDark ? '#cbd5e1' : '#1e293b';
            l.style.borderColor = isDark ? '#475569' : '#d1d5db';
        }
    });
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
if (localStorage.getItem('jb_dark_mode') === '1') {
    document.body.classList.add('dark-mode');
}
updateDarkModeIcon();

</script>
<!-- Modal WhatsApp Chequeo OK -->
<div class="modal fade" id="modalChequeoWhatsApp" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-whatsapp text-success"></i> ¿Notificar al cliente?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">El equipo pasó todos los chequeos correctamente.</p>
                <p class="text-muted small-text">¿Desea notificar al cliente por WhatsApp?</p>
                <p class="mb-0"><strong>Teléfono:</strong> <span id="telefonoChequeo"></span></p>
                <hr>
                <p class="small-text text-muted mb-0"><i class="bi bi-info-circle"></i> Mensaje: <em>"Hola nos comunicamos de [Taller] por la orden N° [n], el equipo se encuentra reparado listo para retirar"</em></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload();">No notificar</button>
                <button type="button" class="btn btn-success" onclick="enviarWhatsAppChequeo()"><i class="bi bi-whatsapp"></i> Notificar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require 'includes/api_token_script.php'; ?>
</body>
</html>