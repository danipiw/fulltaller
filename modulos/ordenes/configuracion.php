<?php
include 'includes/verificar_sesion.php';

$mensaje = '';
$error = '';

// Load current config
$config = [];
$r = $conn->query("SELECT clave, valor FROM configuracion");
if ($r) {
    while ($f = $r->fetch_assoc()) {
        $config[$f['clave']] = $f['valor'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/estados_helper.php';

    $taller_nombre = $conn->real_escape_string($_POST['taller_nombre'] ?? '');
    $taller_direccion = $conn->real_escape_string($_POST['taller_direccion'] ?? '');
    $taller_telefono = $conn->real_escape_string($_POST['taller_telefono'] ?? '');
    $legal_terminos = $conn->real_escape_string($_POST['legal_terminos'] ?? '');
    $estados_recepcion = $conn->real_escape_string($_POST['estados_recepcion'] ?? '');
    $estados_tecnico = $conn->real_escape_string($_POST['estados_tecnico'] ?? '');
    $tipo_impresion = $conn->real_escape_string($_POST['tipo_impresion'] ?? 'dos_vias');

    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('taller_nombre', '$taller_nombre') ON DUPLICATE KEY UPDATE valor = '$taller_nombre'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('taller_direccion', '$taller_direccion') ON DUPLICATE KEY UPDATE valor = '$taller_direccion'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('taller_telefono', '$taller_telefono') ON DUPLICATE KEY UPDATE valor = '$taller_telefono'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('legal_terminos', '$legal_terminos') ON DUPLICATE KEY UPDATE valor = '$legal_terminos'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('estados_recepcion', '$estados_recepcion') ON DUPLICATE KEY UPDATE valor = '$estados_recepcion'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('estados_tecnico', '$estados_tecnico') ON DUPLICATE KEY UPDATE valor = '$estados_tecnico'");
    $conn->query("INSERT INTO configuracion (clave, valor) VALUES ('tipo_impresion', '$tipo_impresion') ON DUPLICATE KEY UPDATE valor = '$tipo_impresion'");

    if ($conn->error) {
        $error = 'Error al guardar: ' . $conn->error;
    } else {
        $mensaje = 'Configuración guardada correctamente.';
        $config['taller_nombre'] = $taller_nombre;
        $config['taller_direccion'] = $taller_direccion;
        $config['taller_telefono'] = $taller_telefono;
        $config['legal_terminos'] = $legal_terminos;
        $config['estados_recepcion'] = $estados_recepcion;
        $config['estados_tecnico'] = $estados_tecnico;
        $config['tipo_impresion'] = $tipo_impresion;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .nav-jb { background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%); padding: 0.2rem 1.5rem; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .nav-jb .nav-btn { color: rgba(255,255,255,0.85); text-decoration: none; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
        .nav-jb .nav-btn:hover { background: rgba(255,255,255,0.15); color: white; }
        .card-config { border-radius: 12px; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card-config h5 { color: var(--jb-navy); }
        body.dark-mode { background: #0f1729; color: #e2e8f0; }
        body.dark-mode .card-config { background: #1a2235 !important; color: #e2e8f0; }
        body.dark-mode .form-control { background: #0f1729; color: #e2e8f0; border-color: #2d3748; }
        body.dark-mode .form-label { color: #cbd5e1; }
    </style>
</head>
<body>

<nav class="nav-jb d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <a href="listado.php" class="nav-btn"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span style="color:white; font-weight:600; font-size:0.95rem;"><i class="bi bi-gear"></i> Configuración</span>
    </div>
</nav>

<div class="container py-3">

    <?php if ($mensaje): ?>
    <div class="alert alert-success py-2" style="font-size:0.9rem;"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger py-2" style="font-size:0.9rem;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-shop"></i> Nombre del Taller</h5>
                <p class="text-muted small">Este nombre aparecerá en las órdenes y comprobantes impresos.</p>
                <input type="text" name="taller_nombre" class="form-control" value="<?php echo htmlspecialchars($config['taller_nombre'] ?? 'Mi Taller'); ?>" required>
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-geo-alt"></i> Dirección del Taller</h5>
                <p class="text-muted small">Aparecerá en las órdenes impresas.</p>
                <input type="text" name="taller_direccion" class="form-control" value="<?php echo htmlspecialchars($config['taller_direccion'] ?? ''); ?>">
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-telephone"></i> Teléfono del Taller</h5>
                <p class="text-muted small">Aparecerá en las órdenes impresas.</p>
                <input type="text" name="taller_telefono" class="form-control" value="<?php echo htmlspecialchars($config['taller_telefono'] ?? ''); ?>">
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-file-earmark-text"></i> Términos Legales</h5>
                <p class="text-muted small">Estos términos aparecerán en el comprobante que se entrega al cliente. Cada línea es un punto.</p>
                <textarea name="legal_terminos" class="form-control" rows="10" style="font-size:0.85rem; font-family:monospace;"><?php echo htmlspecialchars($config['legal_terminos'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-diagram-3"></i> Estados personalizados</h5>
                <p class="text-muted small">Personalizá los estados que aparecen en el listado de órdenes. Ingresá los nombres separados por coma. Si se deja vacío, se usan los estados por defecto.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Estados para Recepción</label>
                        <input type="text" name="estados_recepcion" class="form-control" value="<?php echo htmlspecialchars($config['estados_recepcion'] ?? ''); ?>" placeholder="INGRESADO, EN ESPERA, APROBADO, ...">
                        <div class="form-text">Ej: <code>INGRESADO, EN ESPERA, APROBADO, RECHAZADO</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estados para Técnico</label>
                        <input type="text" name="estados_tecnico" class="form-control" value="<?php echo htmlspecialchars($config['estados_tecnico'] ?? ''); ?>" placeholder="EN REVISION, REPARADO, ...">
                        <div class="form-text">Ej: <code>EN REVISION, EN ESPERA, REPARADO, SIN REPARACION</code></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-printer"></i> Tipo de Impresión</h5>
                <p class="text-muted small">Elegí cómo se imprimen las órdenes al seleccionar "Ambas".</p>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipo_impresion" value="dos_vias" id="ti_dos_vias" <?php echo ($config['tipo_impresion'] ?? 'dos_vias') === 'dos_vias' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ti_dos_vias">
                            <strong>Dos vías (A5)</strong><br>
                            <small class="text-muted">Imprime taller y cliente en hojas separadas</small>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipo_impresion" value="unificada" id="ti_unificada" <?php echo ($config['tipo_impresion'] ?? '') === 'unificada' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ti_unificada">
                            <strong>Unificada (A4)</strong><br>
                            <small class="text-muted">Taller + Cliente en una sola hoja A4</small>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar Configuración</button>
        <a href="listado.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
