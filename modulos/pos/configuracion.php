<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false || !esAdminPOS()) {
    header('Location: index.php');
    exit;
}

$db = getDB();

$config = [];
$r = $db->query("SELECT clave, valor FROM configuracion");
if ($r) {
    while ($f = $r->fetch_assoc()) {
        $config[$f['clave']] = $f['valor'];
    }
}

$mensaje = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'taller_nombre', 'taller_direccion', 'taller_telefono',
        'legal_terminos', 'estados_recepcion', 'estados_tecnico',
        'admin_nombre', 'admin_telefono',
        'tipo_impresion'
    ];
    foreach ($fields as $field) {
        $val = $db->real_escape_string($_POST[$field] ?? '');
        $db->query("INSERT INTO configuracion (clave, valor) VALUES ('$field', '$val') ON DUPLICATE KEY UPDATE valor = '$val'");
    }
    if ($db->error) {
        $error = 'Error al guardar: ' . $db->error;
    } else {
        $mensaje = 'Configuración guardada correctamente.';
        foreach ($fields as $field) {
            $config[$field] = $_POST[$field] ?? '';
        }
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .card-config { border-radius: 12px; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card-config h5 { color: var(--jb-navy); }
        body.dark-mode .card-config { background: #1a2235 !important; color: #e2e8f0; }
        body.dark-mode .card-config h5 { color: #e2e8f0; }
        body.dark-mode .form-control { background: #0f1729; color: #e2e8f0; border-color: #2d3748; }
        body.dark-mode .form-label { color: #cbd5e1; }
        body.dark-mode .form-text { color: #64748b; }
    </style>
</head>
<body>

<?php require 'includes/sidebar.php'; ?>

<nav class="nav-jb">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 0 0 0.25rem;">
        <div class="nav-left" style="display:flex;align-items:center;gap:10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <img src="logo.png" alt="FullTaller" class="nav-logo" onerror="this.style.display='none'">
                <span style="color:white;font-size:0.95rem;font-weight:500;">Configuración</span>
            </a>
        </div>
        <div class="nav-center d-none d-md-flex" style="align-items:center;">
            <a href="index.php" class="nav-btn">🛒 Vender</a>
            <span class="nav-sep">|</span>
            <a href="productos.php" class="nav-btn">📦 Productos</a>
            <?php if (esAdminPOS()): ?>
            <span class="nav-sep">|</span>
            <a href="usuarios.php" class="nav-btn">👥 Usuarios</a>
            <?php endif; ?>
            <span class="nav-sep">|</span>
            <a href="ventas.php" class="nav-btn">📊 Historial</a>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📥 Ingreso</a>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📤 Egreso</a>
            <span class="nav-sep">|</span>
            <a href="corte_caja.php" class="nav-btn">🔒 Corte</a>
        </div>
        <div class="nav-right">
            <span class="rol-badge">
                <?php echo esAdminPOS() ? '👑' : '👤'; ?>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
            <a href="logout.php" class="nav-btn">➤ Salir</a>
        </div>
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
                <h5><i class="bi bi-person-badge"></i> Administrador del Taller</h5>
                <p class="text-muted small">Estos datos se usan para enviar el resumen de cierre de caja por WhatsApp.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del administrador</label>
                        <input type="text" name="admin_nombre" class="form-control" value="<?php echo htmlspecialchars($config['admin_nombre'] ?? ''); ?>" placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono (con código de país, sin +)</label>
                        <input type="text" name="admin_telefono" class="form-control" value="<?php echo htmlspecialchars($config['admin_telefono'] ?? ''); ?>" placeholder="Ej: 5491112345678">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-file-earmark-text"></i> Términos Legales</h5>
                <p class="text-muted small">Estos términos aparecerán en el comprobante que se entrega al cliente. Cada línea es un punto.</p>
                <textarea name="legal_terminos" class="form-control" rows="8" style="font-size:0.85rem; font-family:monospace;"><?php echo htmlspecialchars($config['legal_terminos'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="card card-config mb-3">
            <div class="card-body">
                <h5><i class="bi bi-diagram-3"></i> Estados personalizados</h5>
                <p class="text-muted small">Personalizá los estados que aparecen en el listado de órdenes. Ingresá los nombres separados por coma.</p>
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
                <h5><i class="bi bi-printer"></i> Tipo de impresión (Órdenes)</h5>
                <p class="text-muted small">Elegí cómo se imprimen las órdenes de reparación desde el módulo Órdenes.</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="tipo_impresion" value="dos_vias" id="ti_dos_vias" <?php echo ($config['tipo_impresion'] ?? 'dos_vias') === 'dos_vias' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ti_dos_vias">
                        <strong>Dos vías (A5)</strong> — Taller + Cliente en hojas separadas
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipo_impresion" value="unificada" id="ti_unificada" <?php echo ($config['tipo_impresion'] ?? '') === 'unificada' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ti_unificada">
                        <strong>Unificada (A4)</strong> — Taller arriba + Cliente abajo en una sola hoja
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar Configuración</button>
        <a href="index.php" class="btn btn-secondary">Volver al POS</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
