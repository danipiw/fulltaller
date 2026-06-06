<?php
session_start();

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    header('Location: login.php');
    exit;
}

$modulos = explode(',', $_SESSION['user_modulos'] ?? '');
$modulos = array_map('trim', $modulos);
$nombre = htmlspecialchars($_SESSION['nombre']);
$taller = htmlspecialchars($_SESSION['taller_nombre'] ?? 'Sistema');
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $taller; ?></title>
    <link rel="manifest" href="/manifest.php">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/modulos/ordenes/icon.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; --jb-fondo: #f0f4f8; }
        body {
            background: var(--jb-fondo); min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .topbar {
            background: linear-gradient(135deg, var(--jb-navy), var(--jb-azul-oscuro));
            color: white; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar .user-info { font-size: 0.9rem; opacity: 0.9; }
        .topbar .user-info strong { opacity: 1; }
        .container-dash { max-width: 700px; margin: 60px auto; padding: 0 16px; }
        .dash-title { text-align: center; margin-bottom: 40px; }
        .dash-title h1 { font-size: 1.8rem; color: var(--jb-navy); font-weight: 700; }
        .dash-title p { color: #64748b; font-size: 1rem; }
        .modules-grid { display: grid; gap: 20px; grid-template-columns: 1fr 1fr; }
        .module-card {
            background: white; border-radius: 16px; padding: 32px 24px;
            text-align: center; cursor: pointer; transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 2px solid transparent;
            text-decoration: none; display: block;
        }
        .module-card:hover {
            transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.1);
            border-color: var(--jb-azul);
        }
        .module-card .icon { font-size: 3rem; margin-bottom: 16px; }
        .module-card .name { font-size: 1.3rem; font-weight: 700; color: var(--jb-navy); }
        .module-card .desc { font-size: 0.85rem; color: #64748b; margin-top: 4px; }
        .btn-logout {
            background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3);
            padding: 6px 16px; border-radius: 8px; text-decoration: none; font-size: 0.85rem;
        }
        .btn-logout:hover { background: rgba(255,255,255,0.25); color: white; }
        @media (max-width: 500px) {
            .modules-grid { grid-template-columns: 1fr; }
            .container-dash { margin: 30px auto; }
        }
    </style>
<script>if('serviceWorker'in navigator){navigator.serviceWorker.register('/sw.js').catch(function(){})}</script>
</head>
<body>

<div class="topbar">
    <div>
        <strong><?php echo $taller; ?></strong>
        <span class="user-info ms-3"><i class="bi bi-person-circle"></i> <?php echo $nombre; ?> (<?php echo $rol; ?>)</span>
    </div>
    <a href="../logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Salir</a>
</div>

<div class="container-dash">
    <div class="dash-title">
        <h1><i class="bi bi-grid-fill"></i> Panel Principal</h1>
        <p>Seleccion&aacute; el m&oacute;dulo al que quer&eacute;s ingresar</p>
    </div>

    <div class="modules-grid">
        <?php if (in_array('ordenes', $modulos)): ?>
        <a href="ordenes/listado.php" class="module-card" target="_blank">
            <div class="icon"><i class="bi bi-tools"></i></div>
            <div class="name">Ordenes</div>
            <div class="desc">Gesti&oacute;n de reparaciones y clientes</div>
        </a>
        <?php endif; ?>

        <?php if (in_array('pos', $modulos)): ?>
        <a href="pos/index.php" class="module-card" target="_blank">
            <div class="icon"><i class="bi bi-cart-check"></i></div>
            <div class="name">Punto de Venta</div>
            <div class="desc">Sistema de ventas y caja</div>
        </a>
        <?php endif; ?>

        <?php if (in_array('inventario', $modulos)): ?>
        <a href="inventario/ingreso.php" class="module-card" target="_blank">
            <div class="icon"><i class="bi bi-box-seam"></i></div>
            <div class="name">Repuestos / Scrap</div>
            <div class="desc">Control de scrap y componentes</div>
        </a>
        <?php endif; ?>

        <?php if (in_array('finanzas', $modulos)): ?>
        <a href="#" class="module-card" onclick="alert('M&oacute;dulo en desarrollo'); return false;">
            <div class="icon"><i class="bi bi-cash-stack"></i></div>
            <div class="name">Finanzas</div>
            <div class="desc">Reportes y gesti&oacute;n financiera</div>
        </a>
        <?php endif; ?>

        <?php if (!array_intersect(['ordenes', 'pos', 'inventario', 'finanzas'], $modulos)): ?>
        <div class="module-card" style="grid-column:1/-1;cursor:default;opacity:0.6;">
            <div class="icon"><i class="bi bi-shield-exclamation"></i></div>
            <div class="name">Sin acceso</div>
            <div class="desc">No ten&eacute;s m&oacute;dulos asignados. Contact&aacute; al administrador.</div>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
