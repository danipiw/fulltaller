<?php

include 'includes/conexion.php';

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

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden #<?php echo htmlspecialchars($orden['id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: white; color: black; }
        .orden { border: 2px solid black; padding: 20px; margin-bottom: 40px; }
        .titulo { text-align: center; font-size: 32px; font-weight: bold; }
        .subtitulo { text-align: center; margin-bottom: 30px; }
        .box { border: 1px solid black; padding: 10px; margin-bottom: 15px; min-height: 120px; }
        .firma { margin-top: 70px; border-top: 1px solid black; width: 250px; text-align: center; }
        .legal { font-size: 12px; margin-top: 20px; }
        @media print { .no-print { display: none; } }

        /* ===== MODO NOCTURNO ===== */
        .btn-dark-mode {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 9999;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, var(--jb-navy), var(--jb-azul-oscuro));
            color: var(--jb-cyan);
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-dark-mode:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0,168,232,0.4);
        }

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
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="text-center mb-4 no-print">
        <button class="btn btn-dark" onclick="window.print()">Imprimir Orden</button>
    </div>

    <!-- COPIA CLIENTE -->
    <div class="orden">
        <div class="titulo">ORDEN DE REPARACIÓN</div>
        <div class="subtitulo">
            Orden Nº: <strong><?php echo htmlspecialchars($orden['id']); ?></strong>
            | Fecha: <?php echo htmlspecialchars($orden['fecha_ingreso']); ?>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box">
                    <h5>Cliente</h5>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['cliente_nombre']); ?></p>
                    <p><strong>DNI:</strong> <?php echo htmlspecialchars($orden['dni']); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($orden['telefono']); ?></p>
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <h5>Equipo</h5>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($orden['tipo']); ?></p>
                    <p><strong>Marca:</strong> <?php echo htmlspecialchars($orden['marca']); ?></p>
                    <p><strong>Modelo:</strong> <?php echo htmlspecialchars($orden['modelo']); ?></p>
                    <p><strong>IMEI:</strong> <?php echo htmlspecialchars($orden['imei']); ?></p>
                </div>
            </div>
        </div>

        <div class="box">
            <h5>Falla reportada</h5>
            <?php echo nl2br(htmlspecialchars($orden['falla'])); ?>
        </div>

        <div class="box">
            <h5>Presupuesto</h5>
            $ <?php echo number_format($orden['presupuesto'], 2); ?>
        </div>

        <div class="legal">
            <p>• El servicio técnico no se responsabiliza por equipos abandonados luego de 30 días.</p>
            <p>• Todo equipo ingresado queda sujeto a revisión técnica.</p>
            <p>• La garantía cubre únicamente la reparación realizada.</p>
        </div>

        <div class="firma">Firma del cliente</div>
    </div>

    <!-- COPIA TALLER -->
    <div class="orden">
        <div class="titulo">COPIA TALLER</div>
        <div class="subtitulo">
            Orden Nº: <strong><?php echo htmlspecialchars($orden['id']); ?></strong>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="box">
                    <h5>Cliente</h5>
                    <p><?php echo htmlspecialchars($orden['cliente_nombre']); ?></p>
                    <p><?php echo htmlspecialchars($orden['telefono']); ?></p>
                </div>
            </div>
            <div class="col-6">
                <div class="box">
                    <h5>Equipo</h5>
                    <p><?php echo htmlspecialchars($orden['marca'] . ' ' . $orden['modelo']); ?></p>
                    <p>IMEI: <?php echo htmlspecialchars($orden['imei']); ?></p>
                </div>
            </div>
        </div>

        <div class="box">
            <h5>Checklist</h5>
            ☐ Enciende<br>
            ☐ Imagen<br>
            ☐ Touch<br>
            ☐ Tapas<br>
            ☐ Tornillos<br>
            ☐ Cámara<br>
            ☐ Parlante<br>
        </div>

        <div class="box">
            <h5>Observaciones Técnicas</h5>
            <br><br><br><br>
        </div>
    </div>
</div>


<!-- BOTÓN MODO NOCTURNO -->
<button id="btnDarkMode" class="btn-dark-mode" onclick="toggleDarkMode()" title="Modo Nocturno">
    <i class="bi bi-moon-stars-fill" id="iconDarkMode"></i>
</button>

<script>
// Modo nocturno
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('jb_dark_mode', isDark ? '1' : '0');
    updateDarkModeIcon();
}

function updateDarkModeIcon() {
    const icon = document.getElementById('iconDarkMode');
    const btn = document.getElementById('btnDarkMode');
    if (document.body.classList.contains('dark-mode')) {
        icon.className = 'bi bi-sun-fill';
        btn.title = 'Modo Claro';
    } else {
        icon.className = 'bi bi-moon-stars-fill';
        btn.title = 'Modo Nocturno';
    }
}

// Al cargar la página
if (localStorage.getItem('jb_dark_mode') === '1') {
    document.body.classList.add('dark-mode');
}
updateDarkModeIcon();
</script>

</body>
</html>