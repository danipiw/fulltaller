<?php
require_once __DIR__ . '/includes/verificar_sesion_admin.php';

$output = '';
$error = '';

// Check if git is available
exec('git --version 2>&1', $gitVer, $gitCode);
$gitDisponible = ($gitCode === 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    if (!$gitDisponible) {
        $error = 'Git no está disponible en este servidor.';
    } else {
        $dir = __DIR__ . '/..';
        $comando = "cd $dir && git pull 2>&1";
        exec($comando, $outputLines, $returnCode);
        $output = implode("\n", $outputLines);
        if ($returnCode !== 0) {
            $error = 'Error al actualizar. ¿El repo está configurado?';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-navy: #001845; }
        body { background: #f1f5f9; font-family: system-ui, sans-serif; }
        .navbar-custom { background: var(--jb-navy); padding: 12px 24px; }
        .navbar-custom h4 { color: white; margin: 0; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        pre { background: #1e293b; color: #e2e8f0; padding: 16px; border-radius: 8px; font-size: 0.85rem; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <nav class="navbar-custom d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-arrow-repeat"></i> Actualizar Sistema</h4>
        <a href="talleres.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </nav>
    <div class="container p-4" style="max-width:600px;">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($output): ?>
        <div class="card p-3 mb-3">
            <h6>Resultado:</h6>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        </div>
        <?php endif; ?>
        <div class="card p-4">
            <?php if ($gitDisponible): ?>
            <p class="text-muted">Hace click para traer los últimos cambios desde GitHub.</p>
            <form method="POST">
                <button type="submit" name="actualizar" class="btn btn-primary w-100" onclick="this.disabled=true;this.innerHTML='Actualizando...'">
                    <i class="bi bi-arrow-repeat"></i> Actualizar desde Git
                </button>
            </form>
            <?php else: ?>
            <div class="alert alert-warning">Git no está instalado en este servidor.</div>
            <p class="text-muted">Alternativas:</p>
            <ul>
                <li><strong>FileZilla</strong> → Ver menú "Directorios" → "Comparar directorios" → subí solo los modificados</li>
                <li><strong>WinSCP</strong> → sincronización automática de carpetas</li>
                <li><strong>cPanel File Manager</strong> → subí archivo por archivo</li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
