<?php
session_start();
session_destroy();

// Detectar BASE_PATH
$BASE_PATH = '';
$script_path = $_SERVER['SCRIPT_NAME'];
foreach (['/ordenes', '/pos', '/inventario'] as $folder) {
    $pos = strpos($script_path, $folder);
    if ($pos !== false) {
        $BASE_PATH = substr($script_path, 0, $pos + strlen($folder));
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción Vencida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #001845, #023e8a, #0077b6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 16px;
        }
        .card-vencida {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .icono {
            font-size: 4rem;
            color: #dc2626;
            margin-bottom: 16px;
        }
        h2 { color: #1e293b; font-weight: 700; }
        p { color: #64748b; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="card-vencida">
        <div class="icono"><i class="bi bi-clock-history"></i></div>
        <h2>Suscripción Vencida</h2>
        <p>La suscripción de este taller ha expirado o se encuentra desactivada.<br>Contacte al administrador para renovar el servicio.</p>
        <a href="<?= $BASE_PATH ?>/login.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
    </div>
</body>
</html>
