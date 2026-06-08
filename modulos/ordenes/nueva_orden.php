<?php

include 'includes/verificar_sesion.php';

$protocol_n = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host_n = $_SERVER['HTTP_HOST'];
$tiene_tienda_n = isset($_SESSION['user_modulos']) && strpos($_SESSION['user_modulos'], 'tienda') !== false;
if ($tiene_tienda_n) {
    $tracking_base_n = "$protocol_n://$host_n/modulos/tienda/?token=";
} else {
    $tracking_base_n = "$protocol_n://$host_n/seguimiento.php?token=";
}

$clientes = $conn->query(
    "SELECT * FROM clientes ORDER BY nombre ASC"
);

$marcas = $conn->query(
    "SELECT * FROM marcas ORDER BY nombre ASC"
);

$tipos = $conn->query(
    "SELECT * FROM tipos ORDER BY nombre ASC"
);

$tipo_impresion_ord = 'separada';
$cfg_ord = $conn->query("SELECT clave, valor FROM configuracion WHERE clave='tipo_impresion'");
if ($cfg_ord && $cfg_ord->num_rows > 0) {
    $tipo_impresion_ord = $cfg_ord->fetch_assoc()['valor'];
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nueva Orden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Colores FullTaller */
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
        }

        html, body {
            height: 100vh;
            overflow: hidden;
        }
        body {
            background-color: #f0f4f8;
        }

        /* Navbar con botones */
        .nav-jb {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            padding: 0.2rem 1.5rem 0.2rem 0;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 10px rgba(0,56,168,0.3);
        }
        .nav-jb .nav-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            cursor: pointer;
        }
        .nav-jb .nav-btn[type="submit"] {
            background-color: rgba(0,168,232,0.25);
            border-color: var(--jb-cyan);
            color: white;
            font-weight: 600;
        }
        .nav-jb .nav-btn[type="submit"]:hover {
            background-color: var(--jb-cyan);
            color: var(--jb-navy);
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

        /* Cards */
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
        }
        .card-header i {
            color: var(--jb-cyan);
        }

        .form-section {
            height: 100%;
        }
        .form-section .card-body {
            padding: 0.8rem 1rem;
        }
        .compact-card-body {
            padding: 0.8rem 1rem !important;
        }
        .form-label {
            font-size: 0.85rem;
            margin-bottom: 2px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .form-control, .form-select {
            font-size: 0.85rem;
            padding: 0.35rem 0.5rem;
            text-transform: uppercase;
        }
        .falla-obs-card .card-body {
            display: flex;
            flex-direction: column;
        }
        textarea.form-control {
            min-height: 50px;
        }
        .page-content {
            height: calc(100vh - 80px);
            overflow-y: auto;
            padding-bottom: 60px;
        }
        .page-content::-webkit-scrollbar { width: 6px; }
        .page-content::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 3px; }
        .page-content::-webkit-scrollbar-thumb { background: var(--jb-azul); border-radius: 3px; }
        .btn-lg-final {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
        }
        .small-text {
            font-size: 0.75rem;
        }
        .mb-2-custom {
            margin-bottom: 0.25rem !important;
        }
        .aprobado-box {
            background-color: #d1e7dd;
            border: 2px solid #198754;
            border-radius: 8px;
            padding: 6px 10px;
        }
        .aprobado-box .form-check-label {
            color: #0f5132;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .aprobado-box .small-text {
            color: #0f5132;
            font-size: 0.8rem;
            margin-top: 4px;
        }

        /* Botón guardar */
        .btn-jb-guardar {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none;
            color: white;
        }
        .btn-jb-guardar:hover {
            background: linear-gradient(135deg, var(--jb-azul-oscuro), var(--jb-navy));
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,119,182,0.3);
        }
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
        body.dark-mode .table {
            background-color: #1a2235 !important;
            color: #e2e8f0;
        }
        body.dark-mode .table thead {
            background: linear-gradient(135deg, #0d1b3e, #1a2744) !important;
        }
        body.dark-mode .table tbody tr:hover {
            background-color: #243047 !important;
        }
        body.dark-mode .table-bordered td,
        body.dark-mode .table-bordered th {
            border-color: #2d3748 !important;
        }
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1a2235 !important;
            border-color: #2d3748 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .form-control::placeholder {
            color: #64748b;
        }
        body.dark-mode .filtros-estado {
            background-color: #1a2235 !important;
            border-left-color: var(--jb-cyan) !important;
        }
        body.dark-mode .modal-content {
            background-color: #1a2235 !important;
            color: #e2e8f0;
        }
        body.dark-mode .modal-header {
            border-bottom-color: #2d3748 !important;
        }
        body.dark-mode .modal-footer {
            border-top-color: #2d3748 !important;
        }
        body.dark-mode .dropdown-menu {
            background-color: #1a2235 !important;
            border-color: #2d3748 !important;
        }
        body.dark-mode .dropdown-item {
            color: #e2e8f0 !important;
        }
        body.dark-mode .dropdown-item:hover {
            background-color: #243047 !important;
        }
        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        body.dark-mode .orden-cliente,
        body.dark-mode .orden-taller {
            background-color: white !important;
            color: black !important;
        }
        body.dark-mode .orden-cliente *,
        body.dark-mode .orden-taller * {
            color: black !important;
        }
        body.dark-mode .orden-cliente .box,
        body.dark-mode .orden-taller .box,
        body.dark-mode .orden-cliente .falla-box,
        body.dark-mode .orden-taller .falla-box,
        body.dark-mode .orden-cliente .obs-box,
        body.dark-mode .orden-taller .obs-box {
            border-color: black !important;
        }
        body.dark-mode .sena-box {
            background-color: #0f2a1a !important;
            border-color: #1a4a2e !important;
        }
        body.dark-mode .sena-box .sena-monto {
            color: #4ade80 !important;
        }
        body.dark-mode .observaciones-box {
            background-color: #2a1f0a !important;
            border-color: #3d2e12 !important;
        }
        body.dark-mode .aprobado-box {
            background-color: #0f2a1a !important;
            border-color: #1a4a2e !important;
        }
        body.dark-mode .aprobado-box .form-check-label,
        body.dark-mode .aprobado-box .small-text {
            color: #4ade80 !important;
        }
        body.dark-mode .estado-checkbox label {
            opacity: 0.9;
        }
        body.dark-mode .est-INGRESADO label { background-color: #374151 !important; color: #9ca3af !important; }
        body.dark-mode .est-EN-REVISION label { background-color: #0e4a5a !important; color: #67e8f9 !important; }
        body.dark-mode .est-EN-ESPERA label { background-color: #5a4a0e !important; color: #fde047 !important; }
        body.dark-mode .est-APROBADO label { background-color: #0e4a2e !important; color: #6ee7b7 !important; }
        body.dark-mode .est-PRESUPUESTO-RECHAZADO label { background-color: #5a1a1a !important; color: #fca5a5 !important; }
        body.dark-mode .est-REPARADO label { background-color: #0e4a2e !important; color: #6ee7b7 !important; }
        body.dark-mode .est-SIN-REPARACION label { background-color: #1f2937 !important; color: #d1d5db !important; }
        body.dark-mode .est-ENTREGADO label { background-color: #0e2a5a !important; color: #93c5fd !important; }
        body.dark-mode .btn-jb-nueva {
            background: linear-gradient(135deg, var(--jb-cyan), var(--jb-azul)) !important;
        }
        body.dark-mode .btn-jb-guardar {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro)) !important;
        }
        body.dark-mode .btn-jb-primary {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro)) !important;
        }
        body.dark-mode .btn-jb-dark {
            background: linear-gradient(135deg, #4b5563, #1f2937) !important;
        }
        body.dark-mode .btn-print-cliente {
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro)) !important;
        }
        body.dark-mode .btn-print-taller {
            background: linear-gradient(135deg, #4b5563, #1f2937) !important;
        }
        body.dark-mode .btn-entregar {
            background: linear-gradient(135deg, #16a34a, #15803d) !important;
        }
        body.dark-mode h1,
        body.dark-mode h2 {
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
        body.dark-mode .print-btn {
            background: #374151 !important;
        }
        body.dark-mode .print-btn:hover {
            background: #4b5563 !important;
        }
        body.dark-mode .toast {
            background-color: #1a2235 !important;
            border: 1px solid #2d3748 !important;
        }
        body.dark-mode .toast-body {
            color: #e2e8f0 !important;
        }
        body.dark-mode .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        body.dark-mode .orden-info,
        body.dark-mode .presupuesto-box,
        body.dark-mode .retiro-box,
        body.dark-mode .info-equipo-bottom,
        body.dark-mode .patron-box,
        body.dark-mode .falla-obs-bottom,
        body.dark-mode .checklist-box {
            background-color: white !important;
            color: black !important;
        }
        body.dark-mode .orden-info *,
        body.dark-mode .presupuesto-box *,
        body.dark-mode .retiro-box *,
        body.dark-mode .info-equipo-bottom *,
        body.dark-mode .patron-box *,
        body.dark-mode .falla-obs-bottom *,
        body.dark-mode .checklist-box * {
            color: black !important;
        }
        body.dark-mode .check-item .checks span {
            border-color: black !important;
        }
        body.dark-mode .patron-grid .circle {
            border-color: black !important;
        }
        body.dark-mode .logo-area,
        body.dark-mode .logo-small {
            border-color: black !important;
        }
        body.dark-mode .orden-num-small {
            border-color: black !important;
        }

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
            <?php if ($ES_ADMIN): ?>
            <a href="../tienda/admin.php" class="sidebar-menu-item">
                <i class="bi bi-shop"></i>
                <span>Tienda</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Spacer to push dark mode to bottom -->
        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <!-- Modo Nocturno -->
        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>
    </div>
</div>

<!-- Navbar -->
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
        <div class="nav-center d-none d-md-block" style="flex:1; text-align:center;">
            <span style="display:inline-flex; align-items:center; gap:10px; color:white;">
                <img src="logo.png" alt="FullTaller" class="nav-logo">
                <span style="font-size:0.95rem; font-weight:500;">Sistema de gestion de ordenes</span>
            </span>
        </div>

        <!-- DERECHA: Guardar -->
        <div class="nav-right d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0 justify-content-end">
            <button type="button" class="nav-btn" id="btnGuardar" onclick="guardarOrdenManual()" style="font-size:1.2rem; padding:0.6rem 1.5rem; z-index:5;"><i class="bi bi-check-lg"></i> Guardar</button>
        </div>
    </div>
</nav>

<div class="page-content px-1">

    <form action="guardar_orden.php" method="POST" id="formOrden">

        <div class="row g-3">

            <!-- COLUMNA 1: Cliente -->
            <div class="col-md-4">
                <div class="card form-section" style="height:100%;">
                    <div class="card-header">
                        <i class="bi bi-person"></i> Cliente
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalCliente">
                            <i class="bi bi-plus-lg"></i> Nuevo Cliente
                        </button>
                        <div class="mb-2">
                            <label class="form-label">Cliente <span class="small-text text-muted">(Apellido primero)</span></label>
                            <select name="cliente_id" id="cliente_select" class="form-select" onchange="cargarCliente()" required>
                                <option value="">Seleccionar</option>
                                <?php while($cliente = $clientes->fetch_assoc()) { ?>
                                    <option value="<?php echo $cliente['id']; ?>" data-dni="<?php echo $cliente['dni']; ?>" data-telefono="<?php echo $cliente['telefono']; ?>">
                                        <?php echo strtoupper($cliente['nombre']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">DNI</label>
                            <input type="text" name="dni" id="dni" class="form-control" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Teléfono <span class="small-text text-muted">(Verificar número)</span></label>
                            <input type="text" name="telefono" id="telefono" class="form-control" required oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA 2: Equipo -->
            <div class="col-md-4">
                <div class="card form-section" style="height:100%;">
                    <div class="card-header">
                        <i class="bi bi-phone"></i> Equipo
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label">IMEI <span class="small-text text-muted">(Si no es visible, generar)</span></label>
                            <div class="d-flex gap-2">
                                <input type="text" name="imei" id="imei" class="form-control" required oninput="this.value = this.value.toUpperCase()">
                                <button type="button" class="btn btn-secondary" onclick="generarIMEI()">Generar</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tipo <span class="small-text text-muted">(Celular, notebook, consola, etc.)</span></label>
                            <div class="d-flex gap-2">
                                <select id="selectTipo" name="tipo" class="form-select" required>
                                    <option value="">Seleccionar</option>
                                    <?php while($tipo = $tipos->fetch_assoc()) { ?>
                                        <option value="<?php echo strtoupper($tipo['nombre']); ?>"><?php echo strtoupper($tipo['nombre']); ?></option>
                                    <?php } ?>
                                </select>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTipo">+</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Marca</label>
                            <div class="d-flex gap-2">
                                <select id="selectMarca" name="marca" class="form-select" required>
                                    <option value="">Seleccionar</option>
                                    <?php while($marca = $marcas->fetch_assoc()) { ?>
                                        <option value="<?php echo strtoupper($marca['nombre']); ?>"><?php echo strtoupper($marca['nombre']); ?></option>
                                    <?php } ?>
                                </select>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMarca">+</button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" class="form-control" required oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA 3: Falla+Obs (arriba) + Presupuesto+Opciones (abajo) -->
            <div class="col-md-4 d-flex flex-column">
                <div class="card form-section flex-grow-1 mb-2">
                    <div class="card-header">
                        <i class="bi bi-exclamation-triangle"></i> Falla y Obs
                    </div>
                    <div class="card-body d-flex flex-column" style="gap:8px;">
                        <div class="flex-grow-1 d-flex flex-column">
                            <label class="form-label">Falla del equipo</label>
                            <textarea name="falla" class="form-control flex-grow-1" style="min-height:80px;" required placeholder="Describa la falla..." oninput="this.value = this.value.toUpperCase()"></textarea>
                        </div>
                        <div class="flex-grow-1 d-flex flex-column">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control flex-grow-1" style="min-height:80px;" placeholder="Notas internas..." oninput="this.value = this.value.toUpperCase()"></textarea>
                        </div>
                    </div>
                </div>
                <div class="card form-section">
                    <div class="card-header">
                        <i class="bi bi-gear"></i> Opciones y Presupuesto
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="express" id="express" value="1">
                                    <label class="form-check-label" for="express"><strong><i class="bi bi-lightning-charge-fill text-danger"></i> Express 24hs</strong></label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="tiene_sena" onchange="toggleSena()">
                                    <label class="form-check-label" for="tiene_sena"><strong><i class="bi bi-cash-coin text-success"></i> Dejó seña</strong></label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2" id="sena_container" style="display:none;">
                            <label class="form-label">Seña ($)</label>
                            <input type="number" step="0.01" name="sena" id="sena" class="form-control" placeholder="0.00">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Presupuesto ($)</label>
                            <input type="number" step="0.01" name="presupuesto" class="form-control" placeholder="0.00">
                        </div>
                        <div class="aprobado-box">
                            <div class="form-check d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" name="estado_inicial" id="estado_inicial" value="APROBADO">
                                <label class="form-check-label" for="estado_inicial"><i class="bi bi-check-circle-fill"></i> Aprobado</label>
                                <span class="small-text" style="margin-left:4px;">Presupuesto ya aprobado por el cliente.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </form>
</div>

<!-- MODAL CLIENTE -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCliente" action="nuevo_cliente.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI</label>
                        <input type="text" name="dni" class="form-control" required oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" required oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL MARCA -->
<div class="modal fade" id="modalMarca" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formMarca" action="guardar_marca.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Realme" required oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TIPO -->
<div class="modal fade" id="modalTipo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formTipo" action="guardar_tipo.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Tipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Smart TV" required oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const TIPO_IMPRESION = '<?php echo $tipo_impresion_ord; ?>';
function generarIMEI() {
    let numero = 'TEMP-' + Math.floor(Math.random() * 999999);
    document.getElementById('imei').value = numero.toUpperCase();
}

function cargarCliente() {
    let select = document.getElementById('cliente_select');
    let opcion = select.options[select.selectedIndex];
    let dni = opcion.getAttribute('data-dni');
    let telefono = opcion.getAttribute('data-telefono');
    document.getElementById('dni').value = dni ? dni.toUpperCase() : '';
    document.getElementById('telefono').value = telefono ? telefono.toUpperCase() : '';
}

function toggleSena() {
    let checkbox = document.getElementById('tiene_sena');
    let container = document.getElementById('sena_container');
    let input = document.getElementById('sena');

    if (checkbox.checked) {
        container.style.display = 'block';
        input.setAttribute('required', 'required');
    } else {
        container.style.display = 'none';
        input.removeAttribute('required');
        input.value = '';
    }
}

// CLIENTE AJAX
let formCliente = document.getElementById('formCliente');
if(formCliente) {
    formCliente.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        fetch('nuevo_cliente.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                let select = document.getElementById('cliente_select');
                let option = document.createElement('option');
                option.value = data.id;
                option.text = (data.nombre + ' - DNI: ' + data.dni).toUpperCase();
                option.setAttribute('data-dni', data.dni ? data.dni.toUpperCase() : '');
                option.setAttribute('data-telefono', data.telefono ? data.telefono.toUpperCase() : '');
                option.selected = true;
                select.appendChild(option);
                document.getElementById('dni').value = data.dni ? data.dni.toUpperCase() : '';
                document.getElementById('telefono').value = data.telefono ? data.telefono.toUpperCase() : '';
                bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
                formCliente.reset();
            }
        });
    });
}

// MARCA AJAX
let formMarca = document.getElementById('formMarca');
if(formMarca) {
    formMarca.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        fetch('guardar_marca.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(texto => {
            let data = JSON.parse(texto);
            if(data.success) {
                let select = document.getElementById('selectMarca');
                let option = document.createElement('option');
                option.value = data.nombre.toUpperCase();
                option.text = data.nombre.toUpperCase();
                option.selected = true;
                select.appendChild(option);
                bootstrap.Modal.getInstance(document.getElementById('modalMarca')).hide();
                formMarca.reset();
            }
        });
    });
}

// TIPO AJAX
let formTipo = document.getElementById('formTipo');
if(formTipo) {
    formTipo.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        fetch('guardar_tipo.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                let select = document.getElementById('selectTipo');
                let option = document.createElement('option');
                option.value = data.nombre.toUpperCase();
                option.text = data.nombre.toUpperCase();
                option.selected = true;
                select.appendChild(option);
                bootstrap.Modal.getInstance(document.getElementById('modalTipo')).hide();
                formTipo.reset();
            }
        });
    });
}
</script>
<script>
var trackingBaseUrl = '<?php echo $tracking_base_n; ?>';
var ultimaOrdenId = null;
var ultimoTelefono = '';
var ultimoToken = '';

function guardarOrdenManual() {
    const form = document.getElementById('formOrden');
    const btn = document.getElementById('btnGuardar');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';

    fetch('guardar_orden.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ultimaOrdenId = data.id;
            ultimoTelefono = data.telefono || '';
            ultimoToken = data.token || '';
            document.getElementById('mensaje-exito').textContent = data.message;
            var modal1 = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
            modal1.show();
        } else {
            mostrarMensaje('Error: ' + (data.error || 'No se pudo guardar'), 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        mostrarMensaje('Error al guardar la orden', 'danger');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });

    return false;
}

function enviarWhatsAppSeguimiento() {
    if (!ultimoTelefono || !ultimoToken) {
        mostrarMensaje('El cliente no tiene teléfono o token', 'danger');
        return;
    }
    var url = trackingBaseUrl + ultimoToken;
    var msg = 'Segu\u00ed el estado de tu orden #' + ultimaOrdenId + ' ac\u00e1: ' + url;
    window.open('whatsapp://send?phone=54' + ultimoTelefono + '&text=' + encodeURIComponent(msg), '_blank');
    bootstrap.Modal.getInstance(document.getElementById('modalSeguimiento')).hide();
    var modal2 = new bootstrap.Modal(document.getElementById('modalImprimir'));
    modal2.show();
}

function saltarSeguimiento() {
    bootstrap.Modal.getInstance(document.getElementById('modalSeguimiento')).hide();
    var modal2 = new bootstrap.Modal(document.getElementById('modalImprimir'));
    modal2.show();
}

function imprimirYRedirigir(tipo) {
    if (tipo === 'unificada') {
        window.open('imprimir_unificada.php?id=' + ultimaOrdenId + '&print=1', '_blank');
    } else if (tipo === 'ambas' && TIPO_IMPRESION === 'unificada') {
        window.open('imprimir_unificada.php?id=' + ultimaOrdenId + '&print=1', '_blank');
    } else {
        if (tipo === 'taller' || tipo === 'ambas') {
            window.open('imprimir_taller.php?id=' + ultimaOrdenId, '_blank');
        }
        if (tipo === 'ambas') {
            setTimeout(function() {
                window.open('imprimir_cliente.php?id=' + ultimaOrdenId, '_blank');
            }, 500);
        }
    }
    bootstrap.Modal.getInstance(document.getElementById('modalImprimir')).hide();
    window.location.href = 'listado.php';
}

function mostrarMensaje(texto, tipo = 'success') {
    const prev = document.getElementById('mensaje-flotante');
    if (prev) prev.remove();

    const div = document.createElement('div');
    div.id = 'mensaje-flotante';
    div.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 99999;
        background: ${tipo === 'success' ? 'linear-gradient(135deg, #16a34a, #15803d)' : 'linear-gradient(135deg, #dc3545, #b02a37)'};
        color: white;
        padding: 20px 40px;
        border-radius: 12px;
        font-size: 1.2rem;
        font-weight: 600;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        animation: fadeInScale 0.3s ease;
        text-align: center;
    `;
    div.innerHTML = `<i class="bi bi-check-circle-fill" style="margin-right:8px;"></i>${texto}`;
    document.body.appendChild(div);

    setTimeout(() => {
        div.style.animation = 'fadeOutScale 0.3s ease forwards';
        setTimeout(() => div.remove(), 300);
    }, 1500);
}
</script>
<style>
@keyframes fadeInScale {
    from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}
@keyframes fadeOutScale {
    from { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    to { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
// Modo nocturno
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

// Al cargar la página
if (localStorage.getItem('jb_dark_mode') === '1') {
    document.body.classList.add('dark-mode');
}
updateDarkModeIcon();


</script>

<!-- MODAL IMPRIMIR -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-share"></i> ¿Enviar seguimiento?</h5>
            </div>
            <div class="modal-body text-center">
                <p id="mensaje-exito" style="font-weight:600; color:var(--jb-azul-oscuro);"></p>
                <p class="text-muted small-text">¿Desea enviar el link de seguimiento al cliente por WhatsApp?</p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-success" onclick="enviarWhatsAppSeguimiento()"><i class="bi bi-whatsapp"></i> Enviar seguimiento</button>
                    <button class="btn btn-secondary" onclick="saltarSeguimiento()">No enviar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImprimir" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-printer"></i> ¿Imprimir orden?</h5>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted small-text">¿Desea imprimir la orden de reparación?</p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-secondary" onclick="window.location.href='listado.php'">No</button>
                    <?php if ($tipo_impresion_ord === 'unificada'): ?>
                    <button class="btn btn-jb-primary" onclick="imprimirYRedirigir('unificada')">Imprimir Orden</button>
                    <?php else: ?>
                    <button class="btn btn-jb-dark" onclick="imprimirYRedirigir('taller')">Solo Taller</button>
                    <button class="btn btn-jb-primary" onclick="imprimirYRedirigir('ambas')">Ambas</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/api_token_script.php'; ?>
</body>
</html>