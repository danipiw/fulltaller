<?php
include 'includes/verificar_sesion.php';
$pdo = $GLOBALS['pdo'];

// Obtener log de movimientos
$movimientos_log = [];
try {
    $stmt_log = $pdo->query("SELECT tipo, caja_id, modelo, marca, componente, descripcion, fecha FROM movimientos_log ORDER BY fecha DESC LIMIT 20");
    $movimientos_log = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e_log) {
    $movimientos_log = [];
}


// Guardar observaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_obs'])) {
    try {
        $stmt = $pdo->prepare("UPDATE caja_items SET notas = ? WHERE caja_id = ?");
        $stmt->execute([$_POST['observaciones'], $_POST['caja_id']]);
        $mensaje = "✅ Observaciones guardadas";
    } catch(Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Marcar componente como usado
if (isset($_POST['accion']) && $_POST['accion'] === 'usar_componente') {
    try {
        $stmt = $pdo->prepare("UPDATE caja_items SET usado = 1 WHERE caja_id = ? AND componente_nombre = ?");
        $stmt->execute([$_POST['caja_id'], $_POST['componente']]);
        

        // Registrar en log
        try {
            $stmt_info = $pdo->prepare("SELECT m.nombre as modelo, ma.nombre as marca FROM cajas c INNER JOIN caja_items ci ON c.id = ci.caja_id INNER JOIN modelos m ON ci.modelo_id = m.id INNER JOIN marcas ma ON m.marca_id = ma.id WHERE c.id = ? LIMIT 1");
            $stmt_info->execute([$_POST['caja_id']]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $stmt_log = $pdo->prepare("INSERT INTO movimientos_log (tipo, caja_id, modelo, marca, componente, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $descripcion = "Retiro de pieza '" . $_POST['componente'] . "' de caja #" . $_POST['caja_id'] . " - " . ($info['marca'] ?? 'N/A') . " " . ($info['modelo'] ?? 'N/A');
            $stmt_log->execute(['retiro', $_POST['caja_id'], $info['modelo'] ?? 'N/A', $info['marca'] ?? 'N/A', $_POST['componente'], $descripcion]);
        } catch(Exception $e_log) {
        }
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Restaurar componente
if (isset($_POST['accion']) && $_POST['accion'] === 'restaurar_componente') {
    try {
        $stmt = $pdo->prepare("UPDATE caja_items SET usado = 0 WHERE caja_id = ? AND componente_nombre = ?");
        $stmt->execute([$_POST['caja_id'], $_POST['componente']]);
        

        // Registrar en log
        try {
            $stmt_info = $pdo->prepare("SELECT m.nombre as modelo, ma.nombre as marca FROM cajas c INNER JOIN caja_items ci ON c.id = ci.caja_id INNER JOIN modelos m ON ci.modelo_id = m.id INNER JOIN marcas ma ON m.marca_id = ma.id WHERE c.id = ? LIMIT 1");
            $stmt_info->execute([$_POST['caja_id']]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $stmt_log = $pdo->prepare("INSERT INTO movimientos_log (tipo, caja_id, modelo, marca, componente, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $descripcion = "Restauración de pieza '" . $_POST['componente'] . "' en caja #" . $_POST['caja_id'] . " - " . ($info['marca'] ?? 'N/A') . " " . ($info['modelo'] ?? 'N/A');
            $stmt_log->execute(['restauracion', $_POST['caja_id'], $info['modelo'] ?? 'N/A', $info['marca'] ?? 'N/A', $_POST['componente'], $descripcion]);
        } catch(Exception $e_log) {
        }
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$caja_id = intval($_GET['caja'] ?? 0);
if (!$caja_id) {
    header('Location: buscador.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.id, c.numero, c.fecha_ingreso, m.nombre as modelo, ma.nombre as marca, ci.notas as observaciones
    FROM cajas c
    INNER JOIN caja_items ci ON c.id = ci.caja_id
    INNER JOIN modelos m ON ci.modelo_id = m.id
    INNER JOIN marcas ma ON m.marca_id = ma.id
    WHERE c.id = ? LIMIT 1
");
$stmt->execute([$caja_id]);
$bolsa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bolsa) {
    header('Location: buscador.php');
    exit;
}

$stmt = $pdo->prepare("SELECT componente_nombre, usado FROM caja_items WHERE caja_id = ? ORDER BY componente_nombre");
$stmt->execute([$caja_id]);
$componentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="manifest" href="manifest.php">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Inventario">
    <link rel="apple-touch-icon" href="../ordenes/icon.php?size=192">
    <title>Repuestos/Scrap - Detalle</title>
    <base href="<?php echo $BASE_PATH; ?>/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
            --ft-navy: #001845;
            --ft-azul-oscuro: #023e8a;
            --ft-azul: #0077b6;
            --ft-cyan: #00a8e8;
            --ft-cyan-claro: #48cae4;
            --ft-fondo: #f0f4f8;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', system-ui, sans-serif; }

        /* Navbar Ordenes */
        .nav-jb {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            padding: 0.2rem 1.5rem 0.2rem 0;
            margin-bottom: 1rem;
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
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-jb .nav-brand img { height: 36px; width: auto; }
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
        .nav-jb .nav-sep { color: rgba(255,255,255,0.3); margin: 0 0.25rem; }

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

        /* Hamburger */
        .btn-hamburger {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.4rem;
            flex-shrink: 0;
            margin-right: 4px;
        }
        .btn-hamburger:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }

        /* Sidebar Ordenes */
        .sidebar-jb {
            background: linear-gradient(180deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 100%) !important;
            color: white;
            border-right: 2px solid var(--jb-cyan);
        }
        .sidebar-jb .offcanvas-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-jb .offcanvas-title { color: var(--jb-cyan); font-weight: 700; }
        .sidebar-jb .btn-close {
            filter: brightness(0) invert(1);
        }
        .sidebar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.95rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .sidebar-menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(4px);
        }
        .sidebar-menu-item.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .sidebar-menu-item i {
            font-size: 1.3rem;
            width: 24px;
            text-align: center;
            color: var(--jb-cyan);
        }
        .sidebar-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 8px 0;
        }
        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }

        /* ===== CONTENIDO ===== */
        .container-ft { max-width: 1400px; margin: 0 auto; padding: 0 1rem 2rem; }
        
        .volver-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            color: var(--ft-azul);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .volver-link:hover { color: var(--ft-cyan); }
        
        h1 { color: var(--ft-navy); margin-bottom: 5px; font-size: 1.5rem; font-weight: 700; }
        .subtitle { color: #64748b; margin-bottom: 25px; font-size: 14px; }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 3px solid var(--ft-cyan);
            border-right: 3px solid var(--ft-cyan);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .info-header {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .bolsa-numero {
            background: linear-gradient(135deg, var(--ft-azul-oscuro), var(--ft-azul));
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            font-size: 32px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-datos .modelo {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        .info-datos .marca {
            color: #64748b;
            font-size: 16px;
            margin-top: 4px;
        }
        .info-datos .fecha {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .componentes-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 3px solid var(--ft-cyan);
            border-right: 3px solid var(--ft-cyan);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .componentes-section h2 {
            color: var(--ft-azul-oscuro);
            font-size: 14px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        
        .componente-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .componente-item.disponible {
            border-left: 4px solid #10b981;
        }
        .componente-item.usado {
            border-left: 4px solid #94a3b8;
            opacity: 0.7;
            background: #f1f5f9;
        }
        
        .comp-nombre {
            font-size: 15px;
            font-weight: 600;
            color: #334155;
        }
        .comp-estado {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .comp-estado.disponible {
            background: #d1fae5;
            color: #065f46;
        }
        .comp-estado.usado {
            background: #e2e8f0;
            color: #64748b;
        }
        
        .btn-usar {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-usar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        }
        
        .btn-restaurar {
            background: linear-gradient(135deg, var(--ft-cyan), var(--ft-azul));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-restaurar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,168,232,0.3);
        }
        
        .obs-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 3px solid var(--ft-cyan);
            border-right: 3px solid var(--ft-cyan);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .obs-section h2 {
            color: var(--ft-azul-oscuro);
            font-size: 14px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .obs-section textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #1e293b;
            font-size: 14px;
            resize: vertical;
            margin-bottom: 15px;
        }
        .obs-section textarea:focus {
            border-color: var(--ft-cyan);
            box-shadow: 0 0 0 3px rgba(0,168,232,0.15);
            outline: none;
        }
        .btn-guardar {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--ft-cyan), var(--ft-azul));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-guardar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,168,232,0.4);
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(16,185,129,0.3);
            display: none;
            z-index: 1000;
            font-weight: 600;
        }
        .toast.visible { display: block; animation: slideIn 0.3s ease; }
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }


        /* ===== INVENTARIO SIDEBAR (renamed) ===== */
        .inv-sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,24,69,0.5);
            display: none;
            z-index: 998;
        }
        .inv-sidebar-overlay.visible { display: block; }

        .inv-sidebar {
            position: fixed;
            top: 0;
            left: -320px;
            width: 320px;
            height: 100vh;
            background: white;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            z-index: 999;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .inv-sidebar.open { left: 0; }

        .inv-sidebar-header {
            background: linear-gradient(135deg, var(--ft-navy) 0%, var(--ft-azul-oscuro) 50%, var(--ft-azul) 100%);
            padding: 1rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .inv-sidebar-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .inv-sidebar-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .inv-sidebar-close:hover { background: rgba(255,255,255,0.2); }

        .inv-sidebar-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .inv-sidebar-section {
            margin-bottom: 1.5rem;
        }
        .inv-sidebar-section h4 {
            color: var(--ft-azul-oscuro);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }

        .log-lista {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .log-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px 12px;
            border-left: 3px solid var(--ft-cyan);
            font-size: 13px;
        }
        .log-item.ingreso {
            border-left-color: #10b981;
        }
        .log-item.retiro {
            border-left-color: #ef4444;
        }
        .log-item.restauracion {
            border-left-color: #00a8e8;
        }
        .log-fecha {
            color: #94a3b8;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .log-texto {
            color: #334155;
            line-height: 1.4;
        }
        .log-texto strong {
            color: var(--ft-azul-oscuro);
        }
        .log-vacio {
            color: #94a3b8;
            text-align: center;
            padding: 20px;
            font-size: 13px;
        }

        /* ===== MODO NOCTURNO ===== */
        body.dark-mode {
            background-color: #0f1729 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode h1 { color: #e2e8f0 !important; }
        body.dark-mode .nav-jb .nav-brand { color: white !important; }
        body.dark-mode .nav-jb .nav-btn { color: rgba(255,255,255,0.9) !important; }
        body.dark-mode .nav-jb .nav-btn.active { color: var(--jb-navy) !important; }
        body.dark-mode .sidebar-jb {
            background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important;
        }
        body.dark-mode .info-card { background-color: #1a2235 !important; }
        body.dark-mode .info-datos .modelo { color: #e2e8f0; }
        body.dark-mode .componentes-section { background-color: #1a2235 !important; }
        body.dark-mode .componente-item { background: #0f1729 !important; border-color: #2d3748; }
        body.dark-mode .componente-item.usado { background: #162032 !important; }
        body.dark-mode .comp-nombre { color: #e2e8f0; }
        body.dark-mode .obs-section { background-color: #1a2235 !important; }
        body.dark-mode .obs-section textarea { background-color: #0f1729 !important; border-color: #2d3748 !important; color: #e2e8f0; }
        body.dark-mode .obs-section h2 { color: var(--jb-cyan); }
        body.dark-mode .componentes-section h2 { color: var(--jb-cyan); }
        body.dark-mode .inv-sidebar { background: #1a2235; }
        body.dark-mode .inv-sidebar-section h4 { color: var(--jb-cyan); }
        body.dark-mode .log-item { background: #0f1729; color: #e2e8f0; }
        body.dark-mode .log-texto { color: #cbd5e1; }
        body.dark-mode .log-texto strong { color: var(--jb-cyan); }
        body.dark-mode .log-vacio { color: #64748b; }
        body.dark-mode .btn-guardar { background: linear-gradient(135deg, #0891b2, #0e7490); }

        @media (max-width: 576px) {
            .container-ft { padding: 0 0.5rem 1rem; }
            .info-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .bolsa-numero { font-size: 24px; padding: 10px 18px; }
            .info-datos .modelo { font-size: 18px; }
        }
    </style>
</head>
<body>

<!-- Sidebar Offcanvas (Ordenes) -->
<div class="offcanvas offcanvas-start sidebar-jb" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">
            <?php if ($ES_ADMIN): ?>
            <i class="bi bi-shield-lock me-2"></i> Admin
            <?php elseif ($ES_FULL): ?>
            <i class="bi bi-person-check me-2"></i> Full Órdenes
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
            <a href="<?php echo $BASE_PATH; ?>/../dashboard.php" class="sidebar-menu-item">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <a href="ingreso.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Ingreso</span>
            </a>

            <a href="buscador.php" class="sidebar-menu-item">
                <i class="bi bi-search"></i>
                <span>Buscar</span>
            </a>

            <hr class="sidebar-divider">
        </div>

        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>

        <hr class="sidebar-divider">

        <a href="<?php echo $BASE_PATH; ?>/../logout.php" class="sidebar-menu-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- Navbar -->
<nav class="nav-jb" style="padding:0;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem; position: relative;">
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="<?php echo $BASE_PATH; ?>/../dashboard.php" class="nav-btn d-none d-md-inline-flex" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.9);padding:0.4rem 0.9rem;border-radius:6px;font-size:0.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>

        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span style="display:inline-flex; align-items:center; gap:10px; color:white;">
                <img src="<?php echo $BASE_PATH; ?>/../ordenes/logo.png?v=<?php echo file_exists(__DIR__ . '/../ordenes/logo.png') ? filemtime(__DIR__ . '/../ordenes/logo.png') : 0; ?>" alt="FullTaller" class="nav-logo">
                <span class="d-none d-sm-inline" style="font-size:0.95rem; font-weight:500;">Repuestos/Scrap</span>
            </span>
        </div>

        <div class="nav-right" style="display: flex; align-items: center; gap: 2px;">
            <div style="display: flex; flex-direction: column; align-items: stretch; gap: 2px;" class="rol-salir-wrapper">
                <span class="rol-badge" style="padding:2px 10px; font-size:0.8rem; text-align:center; width:100%; display:block;">
                    <?php if ($ES_ADMIN): ?>
                    <i class="bi bi-shield-lock"></i>
                    <?php else: ?>
                    <i class="bi bi-<?php echo $ES_FULL ? 'person-check' : ($ES_TECNICO ? 'tools' : 'headset'); ?>"></i>
                    <?php endif; ?>
                    <span style="font-weight:400;font-size:0.7rem;"><?php echo htmlspecialchars($NOMBRE_USUARIO); ?></span>
                </span>
            </div>
        </div>
    </div>
</nav>

<!-- INVENTARIO SIDEBAR (movimientos) -->
<div class="inv-sidebar-overlay" id="invSidebarOverlay" onclick="toggleInvSidebar()"></div>
<div class="inv-sidebar" id="invSidebar">
    <div class="inv-sidebar-header">
        <h3><i class="bi bi-clock-history"></i> Movimientos</h3>
        <button class="inv-sidebar-close" onclick="toggleInvSidebar()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="inv-sidebar-body">
        <div class="inv-sidebar-section">
            <h4><i class="bi bi-clock-history"></i> Movimientos</h4>
            <div class="log-lista" id="logLista">
                <?php if (empty($movimientos_log)): ?>
                    <div class="log-vacio">No hay movimientos registrados</div>
                <?php else: ?>
                    <?php foreach ($movimientos_log as $log): ?>
                        <div class="log-item <?= htmlspecialchars($log['tipo']) ?>">
                            <div class="log-fecha"><i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($log['fecha'])) ?></div>
                            <div class="log-texto">
                                <?php if ($log['tipo'] == 'ingreso'): ?>
                                    <i class="bi bi-box-seam" style="color:#10b981"></i> <strong>Ingreso</strong> caja #<?= $log['caja_id'] ?><br>
                                    <small><?= htmlspecialchars($log['marca']) ?> <?= htmlspecialchars($log['modelo']) ?></small><br>
                                    <small style="color:#64748b"><?= htmlspecialchars($log['componente']) ?></small>
                                <?php elseif ($log['tipo'] == 'retiro'): ?>
                                    <i class="bi bi-box-arrow-up" style="color:#ef4444"></i> <strong>Retiro</strong> de caja #<?= $log['caja_id'] ?><br>
                                    <small><?= htmlspecialchars($log['marca']) ?> <?= htmlspecialchars($log['modelo']) ?></small><br>
                                    <small style="color:#64748b">Pieza: <?= htmlspecialchars($log['componente']) ?></small>
                                <?php elseif ($log['tipo'] == 'restauracion'): ?>
                                    <i class="bi bi-arrow-counterclockwise" style="color:#00a8e8"></i> <strong>Restauración</strong> caja #<?= $log['caja_id'] ?><br>
                                    <small><?= htmlspecialchars($log['marca']) ?> <?= htmlspecialchars($log['modelo']) ?></small><br>
                                    <small style="color:#64748b">Pieza: <?= htmlspecialchars($log['componente']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="container-ft">
    <a href="buscador.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver al buscador</a>
    
    <h1><i class="bi bi-clipboard-data"></i> Detalle</h1>
    
    
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="info-card">
        <div class="info-header">
            <div class="bolsa-numero"><i class="bi bi-phone"></i> #<?= $bolsa['numero'] ?></div>
            <div class="info-datos">
                <div class="modelo"><?= htmlspecialchars($bolsa['modelo']) ?></div>
                <div class="marca"><?= htmlspecialchars($bolsa['marca']) ?></div>
                <div class="fecha"><i class="bi bi-calendar"></i> Ingresado: <?= date('d/m/Y H:i', strtotime($bolsa['fecha_ingreso'])) ?></div>
            </div>
        </div>
    </div>
    
    <div class="componentes-section">
        <h2><i class="bi bi-gear"></i> Componentes</h2>
        <?php foreach ($componentes as $comp): ?>
            <div class="componente-item <?= $comp['usado'] ? 'usado' : 'disponible' ?>" id="item_<?= md5($comp['componente_nombre']) ?>">
                <span class="comp-nombre"><?= htmlspecialchars($comp['componente_nombre']) ?></span>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="comp-estado <?= $comp['usado'] ? 'usado' : 'disponible' ?>" id="estado_<?= md5($comp['componente_nombre']) ?>">
                        <?= $comp['usado'] ? '❌ Usado' : '✅ Disponible' ?>
                    </span>
                    <?php if (!$comp['usado']): ?>
                        <button class="btn-usar" onclick="usarComponente(<?= $caja_id ?>, '<?= htmlspecialchars($comp['componente_nombre']) ?>', this)">
                            <i class="bi bi-check-lg"></i> Usar
                        </button>
                    <?php else: ?>
                        <button class="btn-restaurar" onclick="restaurarComponente(<?= $caja_id ?>, '<?= htmlspecialchars($comp['componente_nombre']) ?>', this)">
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="obs-section">
        <h2><i class="bi bi-pencil-square"></i> Observaciones</h2>
        <form method="POST">
            <input type="hidden" name="caja_id" value="<?= $caja_id ?>">
            <textarea name="observaciones" placeholder="Escribí las observaciones generales de este equipo..."><?= htmlspecialchars($bolsa['observaciones'] ?? '') ?></textarea>
            <button type="submit" name="guardar_obs" class="btn-guardar"><i class="bi bi-save"></i> Guardar observaciones</button>
        </form>
    </div>
    
    <a href="buscador.php" class="volver-link"><i class="bi bi-arrow-left"></i> Volver al buscador</a>
</div>

<div class="toast" id="toast"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function usarComponente(cajaId, componente, btn) {
        if (!confirm('¿Marcar "' + componente + '" como usado?')) return;
        
        const formData = new FormData();
        formData.append('accion', 'usar_componente');
        formData.append('caja_id', cajaId);
        formData.append('componente', componente);
        
        fetch('detalle.php?caja=' + cajaId, {method: 'POST', body: formData})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    actualizarUI(componente, true);
                    mostrarToast('✅ Componente marcado como usado');
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
    
    function restaurarComponente(cajaId, componente, btn) {
        if (!confirm('¿Restaurar "' + componente + '" como disponible?')) return;
        
        const formData = new FormData();
        formData.append('accion', 'restaurar_componente');
        formData.append('caja_id', cajaId);
        formData.append('componente', componente);
        
        fetch('detalle.php?caja=' + cajaId, {method: 'POST', body: formData})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    actualizarUI(componente, false);
                    mostrarToast('✅ Componente restaurado como disponible');
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
    
    function actualizarUI(componente, usado) {
        const hash = md5(componente);
        const item = document.getElementById('item_' + hash);
        const estado = document.getElementById('estado_' + hash);
        const btnContainer = item.querySelector('div');
        
        item.classList.remove(usado ? 'disponible' : 'usado');
        item.classList.add(usado ? 'usado' : 'disponible');
        
        estado.classList.remove(usado ? 'disponible' : 'usado');
        estado.classList.add(usado ? 'usado' : 'disponible');
        estado.textContent = usado ? '❌ Usado' : '✅ Disponible';
        
        const btn = btnContainer.querySelector('button');
        btn.remove();
        
        const nuevoBtn = document.createElement('button');
        nuevoBtn.className = usado ? 'btn-restaurar' : 'btn-usar';
        nuevoBtn.innerHTML = usado ? '<i class="bi bi-arrow-counterclockwise"></i> Restaurar' : '<i class="bi bi-check-lg"></i> Usar';
        nuevoBtn.onclick = usado 
            ? () => restaurarComponente(<?= $caja_id ?>, componente, nuevoBtn)
            : () => usarComponente(<?= $caja_id ?>, componente, nuevoBtn);
        
        btnContainer.appendChild(nuevoBtn);
    }
    
    function mostrarToast(mensaje) {
        const toast = document.getElementById('toast');
        toast.textContent = mensaje;
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 3000);
    }
    
    function md5(string) {
        let hash = 0;
        for (let i = 0; i < string.length; i++) {
            const char = string.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(16);
    }
    
    function toggleInvSidebar() {
        document.getElementById('invSidebar').classList.toggle('open');
        document.getElementById('invSidebarOverlay').classList.toggle('visible');
    }
</script>

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
