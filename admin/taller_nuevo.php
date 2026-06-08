<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $subdominio = trim($_POST['subdominio']);
    $db_host = trim($_POST['db_host']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $db_name = trim($_POST['db_name']);
    $dias = (int)($_POST['dias'] ?? 30);
    $modulos = [];
    if (!empty($_POST['mod_ordenes'])) $modulos[] = 'ordenes';
    if (!empty($_POST['mod_pos'])) $modulos[] = 'pos';
    if (!empty($_POST['mod_inventario'])) $modulos[] = 'inventario';
    if (!empty($_POST['mod_finanzas'])) $modulos[] = 'finanzas';
    if (!empty($_POST['mod_tienda'])) $modulos[] = 'tienda';
    $modulos_str = implode(',', $modulos);
    if (empty($modulos_str)) $modulos_str = 'ordenes';

    if (empty($nombre) || empty($subdominio) || empty($db_host) || empty($db_user) || empty($db_name)) {
        $error = 'Todos los campos obligatorios deben completarse';
    } else {
        $license_key = strtoupper(bin2hex(random_bytes(8)));

        require_once __DIR__ . '/../includes/conexion_central.php';

        $stmt = $conn_central->prepare("SELECT id FROM talleres WHERE subdominio = ? OR db_name = ? LIMIT 1");
        $stmt->bind_param("ss", $subdominio, $db_name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Ya existe un taller con ese subdominio o nombre de base de datos';
        } else {
            $stmt = $conn_central->prepare("INSERT INTO talleres (nombre, subdominio, db_host, db_user, db_pass, db_name, license_key, modulos, fecha_alta, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY))");
            $stmt->bind_param("ssssssssi", $nombre, $subdominio, $db_host, $db_user, $db_pass, $db_name, $license_key, $modulos_str, $dias);
            if ($stmt->execute()) {
                $taller_id = $conn_central->insert_id;

                $setup_url = "../setup_taller.php?taller_id=$taller_id&key=$license_key";
                $mensaje = "Taller creado correctamente. License Key: <code>$license_key</code><br>
                           <a href='$setup_url' class='btn btn-sm btn-primary mt-2'>Ejecutar setup de BD</a>
                           <a href='talleres.php' class='btn btn-sm btn-secondary mt-2'>Volver</a>";
            } else {
                $error = 'Error al crear el taller: ' . $conn_central->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Taller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-navy: #001845; }
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar-custom { background: var(--jb-navy); padding: 12px 24px; }
        .navbar-custom h4 { color: white; margin: 0; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .form-label { font-weight: 600; color: #334155; }
    </style>
</head>
<body>
    <nav class="navbar-custom d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-plus-circle"></i> Nuevo Taller</h4>
        <a href="talleres.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </nav>

    <div class="container p-4" style="max-width:700px;">
        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del taller *</label>
                        <input type="text" class="form-control" name="nombre" required placeholder="Ej: Taller Misiones">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subdominio *</label>
                        <input type="text" class="form-control" name="subdominio" required placeholder="Ej: taller1">
                        <div class="form-text">Será: <code>subdominio.midominio.com</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Host BD *</label>
                        <input type="text" class="form-control" name="db_host" required value="localhost">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario BD *</label>
                        <input type="text" class="form-control" name="db_user" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña BD</label>
                        <input type="text" class="form-control" name="db_pass">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre BD *</label>
                        <input type="text" class="form-control" name="db_name" required placeholder="Ej: taller_mision">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Días de suscripción</label>
                        <input type="number" class="form-control" name="dias" value="30" min="1" max="365">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Módulos habilitados</label>
                        <div class="d-flex gap-4 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_ordenes" value="1" id="mod_ordenes" checked>
                                <label class="form-check-label" for="mod_ordenes">Órdenes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_pos" value="1" id="mod_pos" checked>
                                <label class="form-check-label" for="mod_pos">POS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_inventario" value="1" id="mod_inventario" checked>
                                <label class="form-check-label" for="mod_inventario">Inventario</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_finanzas" value="1" id="mod_finanzas">
                                <label class="form-check-label" for="mod_finanzas">Finanzas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_tienda" value="1" id="mod_tienda">
                                <label class="form-check-label" for="mod_tienda">Tienda</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Crear Taller</button>
                        <a href="talleres.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
