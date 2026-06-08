<?php

include 'includes/conexion.php';

$config_imp = [];
$r_imp = $conn->query("SELECT clave, valor FROM configuracion");
if ($r_imp) {
    while ($f_imp = $r_imp->fetch_assoc()) {
        $config_imp[$f_imp['clave']] = $f_imp['valor'];
    }
}

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

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host_i = $_SERVER['HTTP_HOST'];
$token_i = $orden['token'] ?? '';
$seg_i = $config_imp['seguimiento_activo'] ?? '1';
$tie_i = $config_imp['tienda_activa'] ?? '0';
if ($seg_i === '1' && $tie_i === '1') {
    $tracking_url = "$protocol://$host_i/modulos/tienda/?token=$token_i";
} else {
    $tracking_url = "$protocol://$host_i/seguimiento.php?token=$token_i";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden Unificada #<?php echo htmlspecialchars($orden['id']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ===== CONTENEDOR A4 ===== */
        .a4-container {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
            overflow: hidden;
        }

        /* ===== MITAD SUPERIOR: ORDEN TALLER (148.5mm) ===== */
        .hoja-taller {
            width: 210mm;
            height: 148.5mm;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #1a1a2e;
            background: white;
            padding: 4mm 5mm;
        }

        /* ===== MITAD INFERIOR: ORDEN CLIENTE (148.5mm) ===== */
        .hoja-cliente {
            width: 210mm;
            height: 148.5mm;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #1a1a2e;
            background: white;
            padding: 8mm 10mm;
        }

                /* ===== LÍNEA DE CORTE ===== */
        .linea-corte {
            position: absolute;
            top: 148.5mm;
            left: 0;
            width: 100%;
            height: 0;
            border-top: 1px dashed #999; /* un poco más visible */
            z-index: 100;
        }
        .linea-corte::before,
        .linea-corte::after {
            content: '✂';
            position: absolute;
            top: -10px;
            font-size: 12px;
            color: #666; /* un poco más visible */
        }
        .linea-corte::before { left: 5mm; }
        .linea-corte::after { right: 5mm; }

        /* ============================================
           ===== ESTILOS ORDEN TALLER (COPIA EXACTA) =====
           ============================================ */
        .hoja-taller .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 2.5px solid #1a1a2e;
        }
        .hoja-taller .header-left {
            display: flex;
            flex-direction: column;
            min-width: 110px;
        }
        .hoja-taller .header-left .orden-box {
            border: 2px solid #1a1a2e;
            border-radius: 6px;
            padding: 4px 14px;
            text-align: center;
            background: #fafafa;
            margin-bottom: 6px;
            display: inline-block;
        }
        .hoja-taller .header-left .orden-box .label {
            font-size: 9px;
            color: #4a4a5a;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .hoja-taller .header-left .orden-box .numero {
            font-size: 24px;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1.1;
        }
        .hoja-taller .fecha-ingreso {
            font-size: 10px;
            color: #4a4a5a;
        }
        .hoja-taller .fecha-ingreso strong {
            display: block;
            font-size: 9px;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            font-weight: 700;
        }
        .hoja-taller .header-center {
            text-align: center;
            flex: 1;
            padding: 0 8px;
        }
        .hoja-taller .header-center h1 {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a2e;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .hoja-taller .header-right {
            text-align: center;
            min-width: 70px;
        }
        .hoja-taller .header-right .qr-code img {
            width: 60px;
            height: 60px;
        }
        .hoja-taller .header-right .qr-label {
            font-size: 7px;
            color: #6c757d;
            margin-top: 2px;
            line-height: 1;
        }
        .hoja-taller .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin-bottom: 3px;
        }
        .hoja-taller .card {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 4px 6px;
            background: #fafafa;
        }
        .hoja-taller .card-title {
            font-size: 9px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1.5px solid #1a1a2e;
            text-align: center;
        }
        .hoja-taller .data-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 11px;
        }
        .hoja-taller .data-row .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 65px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .hoja-taller .data-row .value {
            color: #2a2a3e;
            font-weight: 500;
        }
        .hoja-taller .falla-obs-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin-bottom: 3px;
        }
        .hoja-taller .text-card {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 4px 6px;
            background: #fafafa;
        }
        .hoja-taller .text-card .card-title {
            font-size: 9px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
            padding-bottom: 2px;
            border-bottom: 1.5px solid #1a1a2e;
            text-align: center;
        }
        .hoja-taller .text-content {
            font-size: 11px;
            line-height: 1.3;
            color: #2a2a3e;
            min-height: 28px;
            font-weight: 500;
        }
        .hoja-taller .checklist-section {
            margin-bottom: 3px;
        }
        .hoja-taller .checklist-title {
            font-size: 9px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            margin-bottom: 2px;
            padding-bottom: 2px;
            border-bottom: 1.5px solid #1a1a2e;
        }
        .hoja-taller .checklist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 3px;
        }
        .hoja-taller .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1.5px solid #1a1a2e;
            border-radius: 3px;
            padding: 2px 5px;
            background: #fafafa;
        }
        .hoja-taller .check-item span {
            color: #1a1a2e;
            font-weight: 600;
            font-size: 10px;
        }
        .hoja-taller .checks {
            display: flex;
            gap: 3px;
        }
        .hoja-taller .check-box {
            width: 16px;
            height: 12px;
            border: 1.5px solid #1a1a2e;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 700;
            color: #1a1a2e;
            background: #fff;
        }
        .hoja-taller .firmas-section {
            margin-bottom: 3px;
        }
        .hoja-taller .firmas-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
        }
        .hoja-taller .firma-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 3px 6px;
            background: #fafafa;
            text-align: center;
        }
        .hoja-taller .firma-box h4 {
            font-size: 9px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .hoja-taller .firma-linea {
            border-bottom: 1.5px solid #1a1a2e;
            min-height: 22px;
            margin: 2px 0;
        }
        .hoja-taller .firma-label {
            font-size: 10px;
            color: #4a4a5a;
            font-weight: 500;
        }
        .hoja-taller .bottom-row {
            display: grid;
            grid-template-columns: 7.5fr 1.5fr;
            gap: 4px;
            align-items: stretch;
            height: 48mm;
        }
        .hoja-taller .talonario-section {
            border: 2px dashed #1a1a2e;
            border-radius: 4px;
            padding: 5px 8px;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .hoja-taller .talonario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
            padding-bottom: 3px;
            border-bottom: 2px dashed #1a1a2e;
        }
        .hoja-taller .talonario-header h3 {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .hoja-taller .talonario-orden {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            background: #fff;
            padding: 1px 6px;
            border-radius: 3px;
            border: 1.5px solid #1a1a2e;
        }
        .hoja-taller .talonario-datos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 12px;
            margin-bottom: 4px;
        }
        .hoja-taller .talonario-datos-grid .data-row {
            margin-bottom: 2px;
            font-size: 11px;
        }
        .hoja-taller .talonario-datos-grid .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 50px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .hoja-taller .talonario-datos-grid .value {
            color: #2a2a3e;
            font-weight: 500;
        }
        .hoja-taller .talonario-garantia {
            border-top: 2px dashed #1a1a2e;
            padding-top: 4px;
            margin-top: auto;
        }
        .hoja-taller .talonario-garantia h4 {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .hoja-taller .garantia-text {
            font-size: 9px;
            line-height: 1.35;
            color: #3a3a4a;
        }
        .hoja-taller .garantia-text p {
            margin-bottom: 2px;
        }
        .hoja-taller .garantia-text strong {
            color: #1a1a2e;
            font-weight: 700;
        }
        .hoja-taller .etiqueta-section {
            border: 2px solid #1a1a2e;
            border-radius: 4px;
            padding: 3px;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .hoja-taller .etiqueta-orden-big {
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            color: #1a1a2e;
            padding: 1px 0;
            border-bottom: 1.5px solid #1a1a2e;
            margin-bottom: 2px;
        }
        .hoja-taller .etiqueta-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .hoja-taller .etiqueta-datos {
            font-size: 7px;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        .hoja-taller .etiqueta-datos .data-row {
            margin-bottom: 1px;
        }
        .hoja-taller .etiqueta-datos .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 32px;
        }
        .hoja-taller .etiqueta-datos .value {
            color: #2a2a3e;
            font-weight: 600;
        }
        .hoja-taller .patron-wrapper {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2px;
            margin-top: auto;
        }
        .hoja-taller .patron-wrapper h4 {
            font-size: 7px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .hoja-taller .patron-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px;
            max-width: 66px;
            margin: 0 auto;
        }
        .hoja-taller .patron-grid .circle {
            width: 18px;
            height: 18px;
            border: 2px solid #1a1a2e;
            border-radius: 50%;
            background: #fff;
        }
        .hoja-taller .password-line {
            text-align: center;
            width: 100%;
            margin-top: 2px;
        }
        .hoja-taller .password-line h4 {
            font-size: 7px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .hoja-taller .password-input {
            border-bottom: 2.5px solid #1a1a2e;
            min-height: 12px;
            margin: 0 auto;
            width: 100%;
            max-width: 66px;
        }

        /* ============================================
           ===== ESTILOS ORDEN CLIENTE (COPIA EXACTA) =====
           ============================================ */
        .hoja-cliente .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 2.5px solid #1a1a2e;
        }
        .hoja-cliente .header-left {
            min-width: 120px;
            font-size: 10px;
            color: #4a4a5a;
        }
        .hoja-cliente .header-left .orden-box {
            border: 2px solid #1a1a2e;
            border-radius: 6px;
            padding: 4px 14px;
            text-align: center;
            background: #fafafa;
            margin-bottom: 6px;
            display: inline-block;
        }
        .hoja-cliente .header-left .orden-box .label {
            font-size: 9px;
            color: #4a4a5a;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .hoja-cliente .header-left .orden-box .numero {
            font-size: 24px;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1.1;
        }
        .hoja-cliente .header-left strong {
            font-weight: 700;
            display: block;
            margin-bottom: 3px;
            color: #1a1a2e;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .hoja-cliente .header-center {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }
        .hoja-cliente .header-center h1 {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .hoja-cliente .header-center .header-logo { margin-bottom: 6px; }
        .hoja-cliente .header-center .header-logo img { max-height: 60px; max-width: 200px; }
        .hoja-cliente .header-center .taller-nombre {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 3px;
        }
        .hoja-cliente .header-center .taller-datos {
            font-size: 10px;
            line-height: 1.5;
            color: #4a4a5a;
        }
        .hoja-cliente .header-right {
            text-align: center;
            min-width: 70px;
        }
        .hoja-cliente .header-right .qr-code img {
            width: 90px;
            height: 90px;
        }
        .hoja-cliente .header-right .qr-label {
            font-size: 7px;
            color: #6c757d;
            margin-top: 2px;
            line-height: 1;
        }
        .hoja-cliente .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }
        .hoja-cliente .card {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 6px 8px;
            background: #fafafa;
        }
        .hoja-cliente .card-title {
            font-size: 10px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #1a1a2e;
        }
        .hoja-cliente .data-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 11px;
        }
        .hoja-cliente .data-row .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 70px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .hoja-cliente .data-row .value {
            font-weight: 500;
            color: #2a2a3e;
        }
        .hoja-cliente .falla-obs-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }
        .hoja-cliente .falla-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 6px 8px;
            background: #fafafa;
        }
        .hoja-cliente .falla-box .title {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-decoration: underline;
            text-underline-offset: 2px;
            margin-bottom: 4px;
        }
        .hoja-cliente .falla-box .content {
            font-size: 11px;
            color: #2a2a3e;
            min-height: 30px;
            font-weight: 500;
        }
        .hoja-cliente .obs-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 6px 8px;
            background: #fafafa;
        }
        .hoja-cliente .obs-box .title {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-decoration: underline;
            text-underline-offset: 2px;
            margin-bottom: 4px;
        }
        .hoja-cliente .obs-box .content {
            font-size: 11px;
            color: #2a2a3e;
            min-height: 30px;
            font-weight: 500;
        }
        .hoja-cliente .terminos-section {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 8px 10px;
            background: #fafafa;
        }
        .hoja-cliente .terminos-text {
            font-size: 9px;
            line-height: 1.45;
            color: #3a3a4a;
        }
        .hoja-cliente .terminos-text p {
            margin-bottom: 3px;
            text-align: justify;
        }

        /* ===== BOTÓN IMPRIMIR ===== */
        .no-print {
            display: block;
        }
        .print-btn {
            position: fixed;
            top: 15px;
            right: 15px;
            padding: 10px 20px;
            background: #1a1a2e;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 12px;
            border-radius: 6px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            z-index: 1000;
        }
        .print-btn:hover {
            background: #33334a;
        }

                /* ===== IMPRESIÓN ===== */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .a4-container {
                width: 100%;
                height: 100vh;
                margin: 0;
                box-shadow: none;
            }
            .hoja-taller {
                width: 100%;
                height: 50vh;
                padding: 4mm 5mm;
                page-break-inside: avoid;
            }
            .hoja-cliente {
                width: 100%;
                height: 50vh;
                padding: 8mm 10mm;
                page-break-inside: avoid;
            }
            /* La línea de corte AHORA SÍ se imprime */
            .linea-corte {
                border-top: 1px dashed #999;
            }
            .linea-corte::before,
            .linea-corte::after {
                color: #666;
            }
        }

        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir Orden Unificada</button>

<div class="a4-container">
    <!-- ===== MITAD SUPERIOR: ORDEN TALLER ===== -->
    <div class="hoja-taller">
        <!-- HEADER TALLER -->
        <div class="header">
            <div class="header-left">
                <div class="orden-box">
                    <div class="label">Orden</div>
                    <div class="numero"><?php echo htmlspecialchars($orden['id']); ?></div>
                </div>
                <div class="fecha-ingreso">
                    <strong>Fecha y hora de Ingreso</strong>
                    <?php echo date('j/n/Y', strtotime($orden['fecha_ingreso'])); ?><br>
                    <?php echo date('H:i:s', strtotime($orden['fecha_ingreso'])); ?>
                </div>
            </div>
            <div class="header-center">
                <h1>Orden de Reparación</h1>
            </div>
            <div class="header-right">
                <?php if (!empty($orden['token'])): ?>
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode($tracking_url); ?>" alt="QR">
                    <div class="qr-label">Escanéa para seguimiento</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DATOS PRINCIPALES TALLER -->
        <div class="main-grid">
            <div class="card">
                <div class="card-title">Cliente</div>
                <div class="data-row">
                    <span class="label">Nombres:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">Teléfono:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['telefono']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">DNI:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['dni']); ?></span>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Información del Equipo</div>
                <div class="data-row">
                    <span class="label">Tipo:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['tipo']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">Marca:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['marca']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">Modelo:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['modelo']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">IMEI:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['imei']); ?></span>
                </div>
            </div>
        </div>

        <!-- FALLA Y OBSERVACIONES TALLER -->
        <div class="falla-obs-row">
            <div class="text-card">
                <div class="card-title">Falla del equipo</div>
                <div class="text-content"><?php echo nl2br(htmlspecialchars($orden['falla'])); ?></div>
            </div>
            <div class="text-card">
                <div class="card-title">Observaciones</div>
                <div class="text-content"><?php echo !empty($orden['observaciones']) ? nl2br(htmlspecialchars($orden['observaciones'])) : ''; ?></div>
            </div>
        </div>

        <!-- CHECKLIST TALLER -->
        <div class="checklist-section">
            <div class="checklist-title">Condiciones al ingresar</div>
            <div class="checklist-grid">
                <div class="check-item"><span>ENCIENDE</span><div class="checks"><span class="check-box">SI</span><span class="check-box">NO</span></div></div>
                <div class="check-item"><span>DA IMAGEN</span><div class="checks"><span class="check-box">SI</span><span class="check-box">NO</span></div></div>
                <div class="check-item"><span>PANTALLA TRIZADA</span><div class="checks"><span class="check-box">SI</span><span class="check-box">NO</span></div></div>
                <div class="check-item"><span>ARO EN BUEN ESTADO</span><div class="checks"><span class="check-box">SI</span><span class="check-box">NO</span></div></div>
                <div class="check-item"><span>TAPA EN BUEN ESTADO</span><div class="checks"><span class="check-box">SI</span><span class="check-box">NO</span></div></div>
                <div class="check-item"><span>CARGA</span><div class="checks"><span class="check-box">SI</span><span class="check-box">NO</span></div></div>
            </div>
        </div>

        <!-- FIRMAS TALLER -->
        <div class="firmas-section">
            <div class="firmas-row">
                <div class="firma-box">
                    <h4>Firma de Ingreso</h4>
                    <div class="firma-linea"></div>
                    <div class="firma-label">Firma del Cliente</div>
                </div>
                <div class="firma-box">
                    <h4>Firma de Retiro</h4>
                    <div class="firma-linea"></div>
                    <div class="firma-label">Firma del Cliente</div>
                </div>
            </div>
        </div>

        <!-- BOTTOM ROW: TALONARIO + ETIQUETA TALLER -->
        <div class="bottom-row">
            <div class="talonario-section">
                <div class="talonario-header">
                    <h3>Comprobante de Retiro y Garantía</h3>
                    <span class="talonario-orden">Orden Nº <?php echo htmlspecialchars($orden['id']); ?></span>
                </div>
                <div class="talonario-datos-grid">
                    <div class="data-row">
                        <span class="label">Cliente:</span>
                        <span class="value"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="label">Falla:</span>
                        <span class="value"><?php echo htmlspecialchars($orden['falla']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="label">Equipo:</span>
                        <span class="value"><?php echo htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="label">Fecha:</span>
                        <span class="value">____/____/________</span>
                    </div>
                </div>
                <div class="talonario-garantia">
                    <h4>Garantía</h4>
                    <div class="garantia-text">
                        <p>La garantía cubre únicamente la reparación realizada por un plazo de <strong>30 días corridos</strong> desde la fecha de entrega.</p>
                        <p>No cubre golpes, humedad, manipulación de terceros ni daños ajenos a la reparación efectuada.</p>
                        <p>Las reparaciones de software y/o desbloqueos no tienen garantía.</p>
                        <p>Conserve este comprobante para usar en caso de solicitar garantía.</p>
                        <p><strong>Gracias por confiar en <?php echo htmlspecialchars($config_imp['taller_nombre'] ?? 'FullTaller'); ?>!</strong></p>
                    </div>
                </div>
            </div>
            <div class="etiqueta-section">
                <div class="etiqueta-orden-big">#<?php echo htmlspecialchars($orden['id']); ?></div>
                <div class="etiqueta-content">
                    <div class="etiqueta-datos">
                        <div class="data-row">
                            <span class="label">Ingreso:</span>
                            <span class="value"><?php echo date('j/n/Y', strtotime($orden['fecha_ingreso'])); ?></span>
                        </div>
                        <div class="data-row">
                            <span class="label">Modelo:</span>
                            <span class="value"><?php echo htmlspecialchars($orden['modelo']); ?></span>
                        </div>
                        <div class="data-row">
                            <span class="label">Falla:</span>
                            <span class="value"><?php echo htmlspecialchars(substr($orden['falla'], 0, 14)) . (strlen($orden['falla']) > 14 ? '...' : ''); ?></span>
                        </div>
                    </div>
                    <div class="patron-wrapper">
                        <h4>Patrón</h4>
                        <div class="patron-grid">
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                            <div class="circle"></div>
                        </div>
                    </div>
                    <div class="password-line">
                        <h4>Contraseña</h4>
                        <div class="password-input"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== LÍNEA DE CORTE ===== -->
    <div class="linea-corte"></div>

    <!-- ===== MITAD INFERIOR: ORDEN CLIENTE ===== -->
    <div class="hoja-cliente">
        <!-- HEADER CLIENTE -->
        <div class="header">
            <div class="header-left">
                <div class="orden-box">
                    <div class="label">Orden</div>
                    <div class="numero"><?php echo htmlspecialchars($orden['id']); ?></div>
                </div>
                <strong>Fecha y hora de Ingreso:</strong>
                <?php echo date('j/n/Y', strtotime($orden['fecha_ingreso'])); ?><br>
                <?php echo date('H:i:s', strtotime($orden['fecha_ingreso'])); ?>
            </div>
            <div class="header-center">
                <?php if (!empty($config_imp['logo_ordenes']) && file_exists(__DIR__ . '/logo_ordenes.png')): ?>
                <div class="header-logo">
                    <img src="logo_ordenes.png?v=<?php echo filemtime(__DIR__ . '/logo_ordenes.png'); ?>" alt="Logo">
                </div>
                <?php endif; ?>
                <h1>Orden de Reparación</h1>
                <div class="taller-nombre"><?php echo htmlspecialchars($config_imp['taller_nombre'] ?? 'FullTaller'); ?></div>
                <div class="taller-datos">
                    <?php echo nl2br(htmlspecialchars($config_imp['taller_direccion'] ?? '')); ?><br>
                    <?php echo htmlspecialchars($config_imp['taller_telefono'] ?? ''); ?>
                </div>
            </div>
            <div class="header-right">
                <?php if (!empty($orden['token'])): ?>
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode($tracking_url); ?>" alt="QR">
                    <div class="qr-label">Escanéa para seguimiento</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DATOS PRINCIPALES CLIENTE -->
        <div class="main-grid">
            <div class="card">
                <div class="card-title">Cliente</div>
                <div class="data-row">
                    <span class="label">Nombres:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">Teléfono:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['telefono']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">DNI:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['dni']); ?></span>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Información del Equipo</div>
                <div class="data-row">
                    <span class="label">Tipo:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['tipo']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">Marca:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['marca']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">Modelo:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['modelo']); ?></span>
                </div>
                <div class="data-row">
                    <span class="label">IMEI:</span>
                    <span class="value"><?php echo htmlspecialchars($orden['imei']); ?></span>
                </div>
            </div>
        </div>

        <!-- FALLA Y OBSERVACIONES CLIENTE -->
        <div class="falla-obs-row">
            <div class="falla-box">
                <div class="title">Falla del equipo:</div>
                <div class="content"><?php echo nl2br(htmlspecialchars($orden['falla'])); ?></div>
            </div>
            <div class="obs-box">
                <div class="title">Observaciones</div>
                <div class="content"><?php echo !empty($orden['observaciones']) ? nl2br(htmlspecialchars($orden['observaciones'])) : ''; ?></div>
            </div>
        </div>

        <!-- TÉRMINOS Y CONDICIONES CLIENTE -->
        <div class="terminos-section">
            <div class="terminos-text">
                <?php
                $terminos = $config_imp['legal_terminos'] ?? '';
                $lineas = explode("\n", $terminos);
                foreach ($lineas as $linea):
                    $linea = trim($linea);
                    if (empty($linea)) continue;
                ?>
                <p><?php echo htmlspecialchars($linea); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>