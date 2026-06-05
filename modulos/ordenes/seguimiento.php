<?php
include 'includes/conexion.php';

$orden = null;
$error = '';
$token = $_GET['token'] ?? '';
$config_s = [];
$r_s = $conn->query("SELECT clave, valor FROM configuracion");
if ($r_s) {
    while ($f_s = $r_s->fetch_assoc()) {
        $config_s[$f_s['clave']] = $f_s['valor'];
    }
}

if ($token) {
    $stmt = $conn->prepare("
        SELECT o.*, c.nombre AS cliente_nombre, c.dni, c.telefono
        FROM ordenes o
        INNER JOIN clientes c ON o.cliente_id = c.id
        WHERE o.token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $orden = $result->fetch_assoc();
    if (!$orden) $error = 'Token inválido o orden no encontrada.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_buscar = trim($_POST['id'] ?? '');
    $dni_buscar = trim($_POST['dni'] ?? '');
    if ($id_buscar && $dni_buscar) {
        $stmt = $conn->prepare("
            SELECT o.*, c.nombre AS cliente_nombre, c.dni, c.telefono
            FROM ordenes o
            INNER JOIN clientes c ON o.cliente_id = c.id
            WHERE o.id = ? AND c.dni = ?
        ");
        $stmt->bind_param("is", $id_buscar, $dni_buscar);
        $stmt->execute();
        $result = $stmt->get_result();
        $orden = $result->fetch_assoc();
        if (!$orden) $error = 'No encontramos una orden con ese N° y DNI.';
    } else {
        $error = 'Completá el N° de orden y el DNI.';
    }
}

if ($orden) {
    $orden_id = $orden['id'];
    $historial = $conn->prepare("SELECT * FROM estados_log WHERE orden_id = ? ORDER BY fecha DESC");
    $historial->bind_param("i", $orden_id);
    $historial->execute();
    $historial_result = $historial->get_result();

    $fotos = $conn->prepare("SELECT filename FROM fotos WHERE orden_id = ?");
    $fotos->bind_param("i", $orden_id);
    $fotos->execute();
    $fotos_result = $fotos->get_result();

    $estado_actual = $orden['estado'];
    $badge_color = match ($estado_actual) {
        'INGRESADO' => 'bg-secondary',
        'EN REVISION' => 'bg-info text-dark',
        'EN ESPERA' => 'bg-warning text-dark',
        'APROBADO' => 'bg-primary',
        'PRESUPUESTO RECHAZADO' => 'bg-danger',
        'REPARADO' => 'bg-success',
        'SIN REPARACION' => 'bg-dark',
        'ENTREGADO' => 'bg-success',
        default => 'bg-secondary'
    };
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segumiento Orden #<?php echo $orden_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .header-taller { background: #1a1a2e; color: white; padding: 16px 0; text-align: center; }
        .header-taller h5 { margin: 0; font-weight: 700; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .card-title { font-weight: 700; font-size: 0.9rem; color: #1a1a2e; border-bottom: 1px solid #e9ecef; padding-bottom: 8px; margin-bottom: 12px; }
        .estado-badge { font-size: 0.85rem; padding: 6px 14px; border-radius: 20px; font-weight: 600; }
        .timeline { position: relative; padding-left: 28px; }
        .timeline::before { content: ''; position: absolute; left: 8px; top: 4px; bottom: 4px; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; padding-bottom: 16px; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-dot { position: absolute; left: -22px; top: 4px; width: 14px; height: 14px; border-radius: 50%; background: #1a1a2e; border: 2px solid white; }
        .timeline-item:last-child .timeline-dot { background: #198754; }
        .timeline-date { font-size: 0.75rem; color: #6c757d; }
        .timeline-text { font-size: 0.85rem; font-weight: 500; }
        .foto-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6; }
        .info-label { font-size: 0.78rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
        .info-value { font-size: 0.95rem; font-weight: 500; color: #1a1a2e; }
        .presupuesto-ok { color: #198754; font-weight: 700; font-size: 1rem; }
        .buscar-form { max-width: 400px; margin: 0 auto; }
        .footer-link { text-align: center; margin-top: 32px; padding: 16px; font-size: 0.8rem; color: #6c757d; }
        @media (max-width: 576px) { .header-taller { padding: 12px 0; } .header-taller h5 { font-size: 1rem; } }
    </style>
</head>
<body>

<div class="header-taller">
    <h5><i class="bi bi-tools me-2"></i><?php echo htmlspecialchars($config_s['taller_nombre'] ?? 'Taller'); ?></h5>
</div>

<div class="container py-4" style="max-width: 720px;">

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0" style="font-weight:700;">Orden #<?php echo $orden_id; ?></h6>
            <span class="estado-badge <?php echo $badge_color; ?>"><?php echo htmlspecialchars($estado_actual); ?></span>
        </div>
        <div class="text-end" style="font-size:0.8rem;color:#6c757d;">
            Ingreso: <?php echo date('d/m/Y H:i', strtotime($orden['fecha_ingreso'])); ?>
        </div>
    </div>

    <div class="card p-3">
        <div class="card-title"><i class="bi bi-person me-1"></i>Cliente</div>
        <div class="row g-2">
            <div class="col-6"><div class="info-label">Nombre</div><div class="info-value"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></div></div>
            <div class="col-6"><div class="info-label">Teléfono</div><div class="info-value"><?php echo htmlspecialchars($orden['telefono']); ?></div></div>
        </div>
    </div>

    <div class="card p-3">
        <div class="card-title"><i class="bi bi-phone me-1"></i>Equipo</div>
        <div class="row g-2">
            <div class="col-6"><div class="info-label">Tipo</div><div class="info-value"><?php echo htmlspecialchars($orden['tipo']); ?></div></div>
            <div class="col-6"><div class="info-label">Marca</div><div class="info-value"><?php echo htmlspecialchars($orden['marca']); ?></div></div>
            <div class="col-6"><div class="info-label">Modelo</div><div class="info-value"><?php echo htmlspecialchars($orden['modelo']); ?></div></div>
            <?php if ($orden['imei']): ?>
            <div class="col-6"><div class="info-label">IMEI</div><div class="info-value"><?php echo htmlspecialchars($orden['imei']); ?></div></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card p-3">
        <div class="card-title"><i class="bi bi-wrench me-1"></i>Detalle del Trabajo</div>
        <div class="info-label mb-1">Falla reportada</div>
        <div class="info-value mb-3"><?php echo nl2br(htmlspecialchars($orden['falla'])); ?></div>
        <?php if (!empty($orden['observaciones'])): ?>
        <div class="info-label mb-1">Observaciones</div>
        <div class="info-value"><?php echo nl2br(htmlspecialchars($orden['observaciones'])); ?></div>
        <?php endif; ?>
    </div>

    <div class="card p-3">
        <div class="card-title"><i class="bi bi-currency-dollar me-1"></i>Presupuesto</div>
        <div class="row g-2">
            <div class="col-6">
                <div class="info-label">Presupuesto</div>
                <div class="presupuesto-ok"><?php echo $orden['presupuesto'] ? '$' . number_format($orden['presupuesto'], 2) : 'Pendiente'; ?></div>
            </div>
            <div class="col-6">
                <div class="info-label">Seña</div>
                <div class="info-value"><?php echo $orden['sena'] > 0 ? '$' . number_format($orden['sena'], 2) : '—'; ?></div>
            </div>
        </div>
    </div>

    <?php if ($fotos_result->num_rows > 0): ?>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-images me-1"></i>Fotos</div>
        <div class="d-flex gap-2 flex-wrap">
            <?php while ($foto = $fotos_result->fetch_assoc()): ?>
            <a href="uploads/<?php echo htmlspecialchars($foto['filename']); ?>" target="_blank">
                <img src="uploads/<?php echo htmlspecialchars($foto['filename']); ?>" class="foto-thumb" alt="Foto">
            </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($historial_result->num_rows > 0): ?>
    <div class="card p-3">
        <div class="card-title"><i class="bi bi-clock-history me-1"></i>Historial de movimientos</div>
        <div class="timeline">
            <?php while ($h = $historial_result->fetch_assoc()): ?>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-text"><?php echo htmlspecialchars($h['estado']); ?></div>
                <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($h['fecha'])); ?> — <?php echo htmlspecialchars($h['cambiado_por_usuario'] ?? $h['cambiado_por'] ?? ''); ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer-link">
        <i class="bi bi-shield-check me-1"></i><?php echo htmlspecialchars($config_s['taller_nombre'] ?? 'FullTaller'); ?> — Seguimiento de ordenes
    </div>
</div>

</body>
</html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segumiento de Orden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; }
        .buscar-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 32px; }
        .buscar-card h4 { font-weight: 700; color: #1a1a2e; text-align: center; margin-bottom: 8px; }
        .buscar-card p { text-align: center; color: #6c757d; font-size: 0.9rem; margin-bottom: 24px; }
        .header-taller { background: #1a1a2e; color: white; padding: 16px 0; text-align: center; position: fixed; top: 0; left: 0; right: 0; z-index: 10; }
        .header-taller h5 { margin: 0; font-weight: 700; }
    </style>
</head>
<body>

<div class="header-taller">
    <h5><i class="bi bi-tools me-2"></i><?php echo htmlspecialchars($config_s['taller_nombre'] ?? 'Taller'); ?></h5>
</div>

<div class="container" style="max-width:480px;padding-top:80px;">
    <div class="buscar-card">
        <h4>Consultá el estado de tu orden</h4>
        <p>Ingresá el número de orden y tu DNI para ver el seguimiento.</p>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2" style="font-size:0.9rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label" style="font-weight:600;font-size:0.85rem;">N° de Orden</label>
                <input type="text" name="id" class="form-control form-control-lg" placeholder="Ej: 123" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-weight:600;font-size:0.85rem;">DNI</label>
                <input type="text" name="dni" class="form-control form-control-lg" placeholder="DNI del titular" required>
            </div>
            <button type="submit" class="btn btn-dark w-100 btn-lg">Consultar</button>
        </form>
    </div>

    <div style="text-align:center;margin-top:24px;font-size:0.8rem;color:#6c757d;">
        <i class="bi bi-shield-check me-1"></i><?php echo htmlspecialchars($config_s['taller_nombre'] ?? 'FullTaller'); ?> — Seguimiento de ordenes
    </div>
</div>

</body>
</html>
