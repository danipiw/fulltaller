<?php
require_once __DIR__ . '/includes/verificar_sesion_admin.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taller_id'])) {
    $taller_id = (int)$_POST['taller_id'];
    $tabla = $_POST['tabla'];
    $taller = $conn_central->query("SELECT * FROM talleres WHERE id = $taller_id")->fetch_assoc();
    
    if (!$taller) {
        $error = 'Taller no encontrado';
    } elseif (!in_array($tabla, ['modulos', 'baterias', 'pin_carga'])) {
        $error = 'Tabla inválida';
    } elseif (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo';
    } else {
        $conn_t = new mysqli($taller['db_host'], $taller['db_user'], $taller['db_pass'], $taller['db_name']);
        if ($conn_t->connect_error) {
            $error = 'Error conectando a la BD del taller';
        } else {
            $fh = fopen($_FILES['csv']['tmp_name'], 'r');
            $primera_linea = fgets($fh);
            $delimiter = (strpos($primera_linea, ';') !== false) ? ';' : ',';
            rewind($fh);
            
            $contados = 0;
            $conn_t->query("TRUNCATE TABLE `$tabla`");
            
            $primera_fila_real = '';
            
            if ($tabla === 'pin_carga') {
                while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
                    $data = array_map('trim', $data);
                    if (count($data) >= 2 && $data[0] !== '' && !in_array(strtolower($data[0]), ['tipo', 'precio', 'valor'])) {
                        if ($primera_fila_real === '') $primera_fila_real = implode(', ', $data);
                        $tipo = $conn_t->real_escape_string($data[0]);
                        $precio_raw = explode(',', $data[1])[0];
                        $precio = $conn_t->real_escape_string(preg_replace('/[^0-9]/', '', $precio_raw));
                        $conn_t->query("INSERT INTO pin_carga (tipo, precio) VALUES ('$tipo', '$precio')");
                        $contados++;
                    }
                }
            } else {
                while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
                    $data = array_map('trim', $data);
                    if (count($data) >= 3 && $data[0] !== '' && !in_array(strtolower($data[0]), ['marca', 'modelo', 'precio', 'valor'])) {
                        if ($primera_fila_real === '') $primera_fila_real = implode(', ', $data);
                        $marca = $conn_t->real_escape_string($data[0]);
                        $modelo = $conn_t->real_escape_string($data[1]);
                        $precio_raw = explode(',', $data[2])[0];
                        $precio = $conn_t->real_escape_string(preg_replace('/[^0-9]/', '', $precio_raw));
                        $conn_t->query("INSERT INTO `$tabla` (marca, modelo, precio) VALUES ('$marca', '$modelo', '$precio')");
                        $contados++;
                    }
                }
            }
            fclose($fh);
            $conn_t->close();
            if ($contados === 0) {
                $error = "0 registros importados. Primer dato: " . htmlspecialchars($primera_fila_real ?: 'ninguno');
            } else {
                $mensaje = "Importados <strong>$contados</strong> registros en <strong>$tabla</strong>";
            }
        }
    }
}

$talleres = $conn_central->query("SELECT id, nombre, subdominio FROM talleres ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar CSV - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-navy: #001845; }
        body { background: #f1f5f9; font-family: system-ui, sans-serif; }
        .navbar-custom { background: var(--jb-navy); padding: 12px 24px; }
        .navbar-custom h4 { color: white; margin: 0; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <nav class="navbar-custom d-flex justify-content-between align-items-center">
        <h4><i class="bi bi-upload"></i> Importar Repuestos desde CSV</h4>
        <a href="talleres.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </nav>

    <div class="container p-4" style="max-width:600px;">
        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <p class="text-muted">CSV debe tener formato: <code>marca,modelo,precio</code> (sin cabecera o con cabecera).</p>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Taller</label>
                    <select class="form-select" name="taller_id" required>
                        <option value="">Seleccionar...</option>
                        <?php while ($t = $talleres->fetch_assoc()): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nombre']); ?> (<?php echo htmlspecialchars($t['subdominio']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tabla</label>
                    <select class="form-select" name="tabla" required>
                        <option value="modulos">Módulos</option>
                        <option value="baterias">Baterías</option>
                        <option value="pin_carga">Pin de Carga</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Archivo CSV</label>
                    <input type="file" class="form-control" name="csv" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload"></i> Importar</button>
            </form>
        </div>
    </div>
</body>
</html>
