<?php

include 'includes/conexion.php';

$config_imp = [];
$r_imp = $conn->query("SELECT clave, valor FROM configuracion WHERE clave IN ('taller_nombre','taller_direccion','taller_telefono','legal_terminos','tipo_impresion','logo_ordenes')");
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
$tiene_tienda_i = strpos($GLOBALS['taller_modulos'] ?? '', 'tienda') !== false;
$base_i = !empty($GLOBALS['taller_subdominio']) ? '' : '/modulos';
if ($tiene_tienda_i) {
    $tracking_url = "$protocol://$host_i{$base_i}/tienda/?token=$token_i";
} else {
    $tracking_url = "$protocol://$host_i{$base_i}/ordenes/seguimiento.php?token=$token_i";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden Cliente #<?php echo htmlspecialchars($orden['id']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #1a1a2e;
            background: white;
            width: 210mm;
            height: 150mm;
            padding: 8mm 10mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 2.5px solid #1a1a2e;
        }

        .header-left {
            min-width: 120px;
            font-size: 10px;
            color: #4a4a5a;
        }

        .header-left .orden-box {
            border: 2px solid #1a1a2e;
            border-radius: 6px;
            padding: 4px 14px;
            text-align: center;
            background: #fafafa;
            margin-bottom: 6px;
            display: inline-block;
        }

        .header-left .orden-box .label {
            font-size: 9px;
            color: #4a4a5a;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .header-left .orden-box .numero {
            font-size: 24px;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1.1;
        }

        .header-left strong {
            font-weight: 700;
            display: block;
            margin-bottom: 3px;
            color: #1a1a2e;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-center {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }

        .header-center h1 {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .header-center .header-logo { margin-bottom: 6px; }
        .header-center .header-logo img { max-height: 60px; max-width: 200px; }
        .header-center .taller-nombre {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 3px;
        }

        .header-center .taller-datos {
            font-size: 10px;
            line-height: 1.5;
            color: #4a4a5a;
        }

        .header-right {
            text-align: center;
            min-width: 70px;
        }

        .header-right .qr-code img {
            width: 90px;
            height: 90px;
        }

        .header-right .qr-label {
            font-size: 7px;
            color: #6c757d;
            margin-top: 2px;
            line-height: 1;
        }

        /* ===== GRID PRINCIPAL ===== */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }

        .card {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 6px 8px;
            background: #fafafa;
        }

        .card-title {
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

        .data-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 11px;
        }

        .data-row .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 70px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .data-row .value {
            font-weight: 500;
            color: #2a2a3e;
        }

        /* ===== FALLA Y OBSERVACIONES ===== */
        .falla-obs-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }

        .falla-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 6px 8px;
            background: #fafafa;
        }

        .falla-box .title {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-decoration: underline;
            text-underline-offset: 2px;
            margin-bottom: 4px;
        }

        .falla-box .content {
            font-size: 11px;
            color: #2a2a3e;
            min-height: 30px;
            font-weight: 500;
        }

        .obs-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 6px 8px;
            background: #fafafa;
        }

        .obs-box .title {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-decoration: underline;
            text-underline-offset: 2px;
            margin-bottom: 4px;
        }

        .obs-box .content {
            font-size: 11px;
            color: #2a2a3e;
            min-height: 30px;
            font-weight: 500;
        }

        /* ===== PRESUPUESTO Y SEÑA ===== */
        .pago-section {
            margin-bottom: 6px;
        }

        .pago-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
        }

        .pago-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 5px 8px;
            background: #fafafa;
            text-align: center;
        }

        .pago-box.pago-restante {
            background: #e8f4f8;
            border-color: #0077b6;
        }

        .pago-label {
            display: block;
            font-size: 8px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .pago-value {
            display: block;
            font-size: 16px;
            font-weight: 800;
            color: #1a1a2e;
        }

        .pago-restante .pago-value {
            color: #0077b6;
        }

        /* ===== TÉRMINOS Y CONDICIONES ===== */
        .terminos-section {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 8px 10px;
            background: #fafafa;
        }

        .terminos-text {
            font-size: 9px;
            line-height: 1.45;
            color: #3a3a4a;
        }

        .terminos-text p {
            margin-bottom: 3px;
            text-align: justify;
        }

        /* Print button */
        .no-print {
            display: block;
        }

        .print-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 16px;
            background: #1a1a2e;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 11px;
            border-radius: 6px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }

        .print-btn:hover {
            background: #33334a;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                width: 100%;
                height: 100%;
            }
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir Orden Cliente</button>

<!-- ===== HEADER ===== -->
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

<!-- ===== DATOS PRINCIPALES ===== -->
<div class="main-grid">
    <!-- Cliente -->
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

    <!-- Equipo -->
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

<!-- ===== FALLA Y OBSERVACIONES ===== -->
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

<!-- ===== PRESUPUESTO Y SEÑA ===== -->
<div class="pago-section">
    <div class="pago-row">
        <div class="pago-box">
            <span class="pago-label">Presupuesto</span>
            <span class="pago-value">$<?php echo number_format((float)($orden['presupuesto'] ?? 0), 2); ?></span>
        </div>
        <div class="pago-box">
            <span class="pago-label">Seña</span>
            <span class="pago-value">$<?php echo number_format((float)($orden['sena'] ?? 0), 2); ?></span>
        </div>
        <div class="pago-box pago-restante">
            <span class="pago-label">Saldo Restante</span>
            <span class="pago-value">$<?php echo number_format((float)(($orden['presupuesto'] ?? 0) - ($orden['sena'] ?? 0)), 2); ?></span>
        </div>
    </div>
</div>

<!-- ===== TÉRMINOS Y CONDICIONES ===== -->
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

<?php if (isset($_GET['ambas']) && $_GET['ambas'] === '1'): ?>
<script>
(function() {
    window.onload = function() {
        setTimeout(function() { window.print(); }, 500);
    };
})();
</script>
<?php endif; ?>
</body>
</html>
