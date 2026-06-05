<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID inválido');
}

$id = (int)$_GET['id'];

require_once __DIR__ . '/../includes/conexion_central.php';

$taller = $conn_central->query("SELECT * FROM talleres WHERE id = $id")->fetch_assoc();
if (!$taller) {
    die('Taller no encontrado');
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $subdominio = trim($_POST['subdominio']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];

    $modulos = [];
    if (!empty($_POST['mod_ordenes'])) $modulos[] = 'ordenes';
    if (!empty($_POST['mod_pos'])) $modulos[] = 'pos';
    $modulos_str = implode(',', $modulos);
    if (empty($modulos_str)) $modulos_str = 'ordenes';

    if (!empty($db_pass)) {
        $stmt = $conn_central->prepare("UPDATE talleres SET nombre=?, subdominio=?, db_user=?, db_pass=?, modulos=?, fecha_vencimiento=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nombre, $subdominio, $db_user, $db_pass, $modulos_str, $fecha_vencimiento, $id);
    } else {
        $stmt = $conn_central->prepare("UPDATE talleres SET nombre=?, subdominio=?, db_user=?, modulos=?, fecha_vencimiento=? WHERE id=?");
        $stmt->bind_param("sssssi", $nombre, $subdominio, $db_user, $modulos_str, $fecha_vencimiento, $id);
    }

    if ($stmt->execute()) {
        $mensaje = 'Taller actualizado correctamente';
        $taller = $conn_central->query("SELECT * FROM talleres WHERE id = $id")->fetch_assoc();
    } else {
        $error = 'Error al actualizar: ' . $conn_central->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Taller</title>
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
        <h4><i class="bi bi-pencil"></i> Editar Taller: <?php echo htmlspecialchars($taller['nombre']); ?></h4>
        <a href="talleres.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </nav>

    <div class="container p-4" style="max-width:700px;">
        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($taller['nombre']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subdominio</label>
                        <input type="text" class="form-control" name="subdominio" value="<?php echo htmlspecialchars($taller['subdominio']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario BD</label>
                        <input type="text" class="form-control" name="db_user" value="<?php echo htmlspecialchars($taller['db_user']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña BD (dejar vacío para no cambiar)</label>
                        <input type="text" class="form-control" name="db_pass" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha vencimiento</label>
                        <input type="date" class="form-control" name="fecha_vencimiento" value="<?php echo $taller['fecha_vencimiento']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Key</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($taller['license_key']); ?>" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Módulos habilitados</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_ordenes" value="1" id="mod_ordenes" <?php echo strpos($taller['modulos'] ?? 'ordenes,pos', 'ordenes') !== false ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mod_ordenes">Órdenes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mod_pos" value="1" id="mod_pos" <?php echo strpos($taller['modulos'] ?? 'ordenes,pos', 'pos') !== false ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mod_pos">POS</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cambios</button>
                        <a href="talleres.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
