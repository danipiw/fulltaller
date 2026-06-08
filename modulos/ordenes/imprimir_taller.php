<?php

include 'includes/conexion.php';

$config_imp_t = [];
$r_imp_t = $conn->query("SELECT clave, valor FROM configuracion");
if ($r_imp_t) {
    while ($f_imp_t = $r_imp_t->fetch_assoc()) {
        $config_imp_t[$f_imp_t['clave']] = $f_imp_t['valor'];
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

$protocol_t = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host_t = $_SERVER['HTTP_HOST'];
$token_t = $orden['token'] ?? '';
$seg_t = $config_imp_t['seguimiento_activo'] ?? '1';
$tie_t = $config_imp_t['tienda_activa'] ?? '0';
if ($seg_t === '1' && $tie_t === '1') {
    $tracking_url_t = "$protocol_t://$host_t/modulos/tienda/?token=$token_t";
} else {
    $tracking_url_t = "$protocol_t://$host_t/seguimiento.php?token=$token_t";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden Taller #<?php echo htmlspecialchars($orden['id']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #1a1a2e;
            background: white;
            width: 210mm;
            height: 150mm;
            padding: 4mm 5mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 2.5px solid #1a1a2e;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            min-width: 110px;
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

        .fecha-ingreso {
            font-size: 10px;
            color: #4a4a5a;
        }
        .fecha-ingreso strong {
            display: block;
            font-size: 9px;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            font-weight: 700;
        }

        .header-center {
            text-align: center;
            flex: 1;
            padding: 0 8px;
        }

        .header-center h1 {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a2e;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .header-right {
            text-align: center;
            min-width: 70px;
        }

        .header-right .qr-code img {
            width: 60px;
            height: 60px;
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
            gap: 4px;
            margin-bottom: 3px;
        }

        .card {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 4px 6px;
            background: #fafafa;
        }

        .card-title {
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

        .data-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 11px;
        }

        .data-row .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 65px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .data-row .value {
            color: #2a2a3e;
            font-weight: 500;
        }

        /* ===== FALLA Y OBSERVACIONES ===== */
        .falla-obs-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin-bottom: 3px;
        }

        .text-card {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 4px 6px;
            background: #fafafa;
        }

        .text-card .card-title {
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

        .text-content {
            font-size: 11px;
            line-height: 1.3;
            color: #2a2a3e;
            min-height: 28px;
            font-weight: 500;
        }

        /* ===== CHECKLIST ===== */
        .checklist-section {
            margin-bottom: 3px;
        }

        .checklist-title {
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

        .checklist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 3px;
        }

        .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1.5px solid #1a1a2e;
            border-radius: 3px;
            padding: 2px 5px;
            background: #fafafa;
        }

        .check-item span {
            color: #1a1a2e;
            font-weight: 600;
            font-size: 10px;
        }

        .checks {
            display: flex;
            gap: 3px;
        }

        .check-box {
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

        /* ===== FIRMAS ===== */
        .firmas-section {
            margin-bottom: 3px;
        }

        .firmas-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
        }

        .firma-box {
            border: 1.5px solid #1a1a2e;
            border-radius: 4px;
            padding: 3px 6px;
            background: #fafafa;
            text-align: center;
        }

        .firma-box h4 {
            font-size: 9px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .firma-linea {
            border-bottom: 1.5px solid #1a1a2e;
            min-height: 22px;
            margin: 2px 0;
        }

        .firma-label {
            font-size: 10px;
            color: #4a4a5a;
            font-weight: 500;
        }

        /* ============================================
           ===== BOTTOM ROW: TALONARIO + ETIQUETA =====
           ============================================ */
        .bottom-row {
            display: grid;
            grid-template-columns: 7.5fr 1.5fr;
            gap: 4px;
            align-items: stretch;
            height: 48mm;
        }

        /* --- TALONARIO --- */
        .talonario-section {
            border: 2px dashed #1a1a2e;
            border-radius: 4px;
            padding: 5px 8px;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .talonario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
            padding-bottom: 3px;
            border-bottom: 2px dashed #1a1a2e;
        }

        .talonario-header h3 {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .talonario-orden {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            background: #fff;
            padding: 1px 6px;
            border-radius: 3px;
            border: 1.5px solid #1a1a2e;
        }

        .talonario-datos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 12px;
            margin-bottom: 4px;
        }

        .talonario-datos-grid .data-row {
            margin-bottom: 2px;
            font-size: 11px;
        }

        .talonario-datos-grid .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 50px;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .talonario-datos-grid .value {
            color: #2a2a3e;
            font-weight: 500;
        }

        .talonario-garantia {
            border-top: 2px dashed #1a1a2e;
            padding-top: 4px;
            margin-top: auto;
        }

        .talonario-garantia h4 {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .garantia-text {
            font-size: 9px;
            line-height: 1.35;
            color: #3a3a4a;
        }

        .garantia-text p {
            margin-bottom: 2px;
        }

        .garantia-text strong {
            color: #1a1a2e;
            font-weight: 700;
        }

        /* --- ETIQUETA --- */
        .etiqueta-section {
            border: 2px solid #1a1a2e;
            border-radius: 4px;
            padding: 3px;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .etiqueta-orden-big {
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            color: #1a1a2e;
            padding: 1px 0;
            border-bottom: 1.5px solid #1a1a2e;
            margin-bottom: 2px;
        }

        .etiqueta-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .etiqueta-datos {
            font-size: 7px;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .etiqueta-datos .data-row {
            margin-bottom: 1px;
        }

        .etiqueta-datos .label {
            font-weight: 700;
            color: #1a1a2e;
            min-width: 32px;
        }

        .etiqueta-datos .value {
            color: #2a2a3e;
            font-weight: 600;
        }

        .patron-wrapper {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2px;
            margin-top: auto;
        }

        .patron-wrapper h4 {
            font-size: 7px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .patron-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px;
            max-width: 66px;
            margin: 0 auto;
        }

        .patron-grid .circle {
            width: 18px;
            height: 18px;
            border: 2px solid #1a1a2e;
            border-radius: 50%;
            background: #fff;
        }

        .password-line {
            text-align: center;
            width: 100%;
            margin-top: 2px;
        }

        .password-line h4 {
            font-size: 7px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .password-input {
            border-bottom: 2.5px solid #1a1a2e;
            min-height: 12px;
            margin: 0 auto;
            width: 100%;
            max-width: 66px;
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

<button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir Orden Taller</button>

<!-- ===== HEADER ===== -->
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
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode($tracking_url_t); ?>" alt="QR">
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
    <div class="text-card">
        <div class="card-title">Falla del equipo</div>
        <div class="text-content"><?php echo nl2br(htmlspecialchars($orden['falla'])); ?></div>
    </div>
    <div class="text-card">
        <div class="card-title">Observaciones</div>
        <div class="text-content"><?php echo !empty($orden['observaciones']) ? nl2br(htmlspecialchars($orden['observaciones'])) : ''; ?></div>
    </div>
</div>

<!-- ===== CHECKLIST UNIFICADO: CONDICIONES AL INGRESAR ===== -->
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

<!-- ===== FIRMAS (solo cliente) ===== -->
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

<!-- ===== BOTTOM ROW: TALONARIO + ETIQUETA ===== -->
<div class="bottom-row">
    <!-- TALONARIO RETIRO Y GARANTÍA -->
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
                <p><strong>Gracias por confiar en <?php echo htmlspecialchars($config_imp_t['taller_nombre'] ?? 'FullTaller'); ?>!</strong></p>
            </div>
        </div>
    </div>

    <!-- ETIQUETA DE EQUIPO -->
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

<?php if (isset($_GET['ambas']) && $_GET['ambas'] === '1'): ?>
<script>
(function() {
    var id = <?php echo $id; ?>;
    function irACliente() {
        window.location.href = 'imprimir_cliente.php?id=' + id + '&ambas=1';
    }
    window.onload = function() {
        setTimeout(function() { window.print(); }, 300);
        if (window.onafterprint !== undefined) {
            window.onafterprint = irACliente;
        } else if (window.matchMedia) {
            var mql = window.matchMedia('print');
            mql.addListener(function(m) {
                if (!m.matches) setTimeout(irACliente, 500);
            });
        } else {
            setTimeout(irACliente, 5000);
        }
    };
})();
</script>
<?php endif; ?>
</body>
</html>