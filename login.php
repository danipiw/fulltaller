<?php
session_start();

if (isset($_SESSION['rol'])) {
    header('Location: modulos/ordenes/listado.php');
    exit;
}

$taller_nombre_login = 'Sistema de Gestión';
$host_actual = $_SERVER['HTTP_HOST'] ?? '';
$partes = explode('.', $host_actual);
$subdominio = '';
if (count($partes) >= 2) {
    $primer_parte = $partes[0];
    if ($primer_parte !== 'www' && $primer_parte !== 'admin') {
        $subdominio = $primer_parte;
    }
}
if (!empty($subdominio)) {
    include __DIR__ . '/includes/conexion_central.php';
    $sub_esc = $conn_central->real_escape_string($subdominio);
    $r = $conn_central->query("SELECT nombre FROM talleres WHERE subdominio = '$sub_esc' AND activo = 1 LIMIT 1");
    if ($row = $r->fetch_assoc()) {
        $taller_nombre_login = htmlspecialchars($row['nombre']);
    }
    $conn_central->close();
}

$error = '';
if (isset($_GET['error'])) {
    $error = match($_GET['error']) {
        'credenciales' => 'Usuario o contraseña incorrectos',
        'sesion' => 'Debes iniciar sesión primero',
        'sin_acceso' => 'No tienes acceso al módulo Órdenes. Contacte al administrador.',
        default => 'Error desconocido'
    };
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $taller_nombre_login; ?> - Inicio de Sesión</title>
    <link rel="manifest" href="modulos/manifest.php">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="modulos/ordenes/icon.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
        }

        body {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 16px;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo img {
            height: 80px;
            margin-bottom: 15px;
        }

        .login-logo h2 {
            color: var(--jb-navy);
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }

        .login-logo p {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .form-floating {
            margin-bottom: 15px;
        }

        .form-floating input {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
        }

        .form-floating input:focus {
            border-color: var(--jb-azul);
            box-shadow: 0 0 0 3px rgba(0,119,182,0.15);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,119,182,0.4);
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .version {
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        @media (max-width: 480px) {
            body { padding: 8px; }
            .login-card {
                padding: 28px 20px;
                border-radius: 12px;
            }
            .login-logo img { height: 70px; }
            .login-logo h2 { font-size: 1.3rem; }
            .form-floating input { font-size: 1.1rem; padding: 1rem 0.75rem; }
            .btn-login { padding: 14px; font-size: 1.1rem; }
        }
    </style>
<script>if('serviceWorker'in navigator){navigator.serviceWorker.register('modulos/sw.js').catch(function(){})}</script>
</head>
<body>

<div class="login-card">
    <div class="login-logo">
        <img src="modulos/ordenes/logo_login.png" alt="">
        <h2><?php echo $taller_nombre_login; ?></h2>
    </div>

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form action="includes/autenticar.php" method="POST" id="loginForm">
        <div class="form-floating">
            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required autocomplete="off">
            <label for="usuario">Usuario</label>
        </div>

        <div class="form-floating">
            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
            <label for="password">Contraseña</label>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Ingresar al Sistema
        </button>
    </form>

    <div class="version">
        v3.0 | Multi-Taller | <?php echo $taller_nombre_login; ?>
    </div>
</div>

</body>
</html>