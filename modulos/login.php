<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$BASE_PATH = '';
$script_path = $_SERVER['SCRIPT_NAME'];
foreach (['/ordenes', '/pos', '/inventario'] as $folder) {
    $pos = strpos($script_path, $folder);
    if ($pos !== false) { $BASE_PATH = substr($script_path, 0, $pos + strlen($folder)); break; }
}

if (isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    header('Location: dashboard.php');
    exit;
}

$host_actual = $_SERVER['HTTP_HOST'] ?? '';
$partes = explode('.', $host_actual);
$subdominio = '';
if (count($partes) >= 2) {
    $primer_parte = $partes[0];
    if ($primer_parte !== 'www' && $primer_parte !== 'admin') {
        $subdominio = $primer_parte;
    }
}

$taller_nombre_login = 'Sistema de Gesti&oacute;n';
$taller_db_host = 'localhost';
$taller_db_user = '';
$taller_db_pass = '';
$taller_db_name = '';
$taller_id = 0;
$debug_sql_error = null;
$deb2_row = null;

if (!empty($subdominio)) {
    $_include_path = __DIR__ . '/ordenes/includes/conexion_central.php';
    if (!file_exists($_include_path)) {
        die("FATAL: No se encuentra " . $_include_path);
    }
    include $_include_path;
    if (!isset($conn_central) || !$conn_central instanceof mysqli) {
        die("FATAL: conexion_central.php no definió \$conn_central");
    }
    $s = $conn_central->real_escape_string($subdominio);
    $sql_taller = "SELECT id, nombre, db_host, db_user, db_pass, db_name FROM talleres WHERE subdominio = '$s' AND activo = 1 LIMIT 1";
    $r = $conn_central->query($sql_taller);
    if (!$r) {
        $debug_sql_error = $conn_central->error;
    }
    if ($r && $row = $r->fetch_assoc()) {
        $taller_nombre_login = htmlspecialchars($row['nombre']);
        $taller_id = (int)$row['id'];
        $taller_db_host = $row['db_host'] ?: 'localhost';
        $taller_db_user = $row['db_user'];
        $taller_db_pass = $row['db_pass'];
        $taller_db_name = $row['db_name'];
    }
    $r2 = $conn_central->query("SELECT id, activo FROM talleres WHERE subdominio = '$s' LIMIT 1");
    $deb2_row = null;
    if ($r2) {
        $deb2_row = $r2->fetch_assoc();
    }
    if (isset($conn_central) && $conn_central instanceof mysqli) {
        $conn_central->close();
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Complete todos los campos';
    } elseif (empty($taller_db_user) && $taller_id === 0) {
        $error = 'Error: el taller &laquo;' . htmlspecialchars($subdominio) . '&raquo; no se encontr&oacute; en la base central (tabla talleres, activo=1)';
    } elseif (empty($taller_db_user)) {
        $error = 'Error: el taller &laquo;' . htmlspecialchars($subdominio) . '&raquo; existe pero no tiene db_user configurado en la base central';
    } else {
        $conn = @new mysqli($taller_db_host, $taller_db_user, $taller_db_pass, $taller_db_name);
        if (!$conn->connect_error) {
            $u = $conn->real_escape_string($usuario);
            $r = $conn->query("SELECT id, usuario, password, nombre, rol, modulos FROM usuarios WHERE usuario = '$u' AND activo = 1 LIMIT 1");
            if ($user = $r->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['rol'] = $user['rol'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['user_modulos'] = $user['modulos'] ?? 'ordenes';
                    $_SESSION['taller_id'] = $taller_id;
                    $_SESSION['taller_nombre'] = $taller_nombre_login;
                    $_SESSION['taller_subdominio'] = $subdominio;
                    $_SESSION['taller_db_host'] = $taller_db_host;
                    $_SESSION['taller_db_user'] = $taller_db_user;
                    $_SESSION['taller_db_pass'] = $taller_db_pass;
                    $_SESSION['taller_db_name'] = $taller_db_name;
                    $_SESSION['api_token'] = bin2hex(random_bytes(16));
                    $_SESSION['login_host'] = $_SERVER['HTTP_HOST'] ?? '';
                    $conn->close();
                    header('Location: dashboard.php');
                    exit;
                }
            }
            $conn->close();
            $error = 'Usuario o contrase&ntilde;a incorrectos';
        } else {
            $error = 'Error de conexi&oacute;n al taller';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $taller_nombre_login; ?> - Inicio de Sesi&oacute;n</title>
    <link rel="manifest" href="manifest.php">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="ordenes/icon.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif; padding: 16px;
        }
        .login-card {
            background: white; border-radius: 16px; padding: 40px;
            width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo img { height: 80px; margin-bottom: 15px; }
        .login-logo h2 { color: var(--jb-navy); font-weight: 700; font-size: 1.5rem; margin: 0; }
        .login-logo p { color: #64748b; font-size: 0.9rem; margin-top: 5px; }
        .form-floating { margin-bottom: 15px; }
        .form-floating input {
            border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem;
        }
        .form-floating input:focus {
            border-color: var(--jb-azul); box-shadow: 0 0 0 3px rgba(0,119,182,0.15);
        }
        .btn-login {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none; border-radius: 10px; color: white; font-weight: 600;
            font-size: 1rem; cursor: pointer; transition: all 0.2s; margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,119,182,0.4); }
        .alert-error {
            background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;
            padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;
            display: flex; align-items: center; gap: 8px;
        }
        .version { text-align: center; margin-top: 20px; color: #94a3b8; font-size: 0.75rem; }
        @media (max-width: 480px) {
            body { padding: 8px; }
            .login-card { padding: 28px 20px; border-radius: 12px; }
            .login-logo img { height: 70px; }
            .login-logo h2 { font-size: 1.3rem; }
            .form-floating input { font-size: 1.1rem; padding: 1rem 0.75rem; }
            .btn-login { padding: 14px; font-size: 1.1rem; }
        }
    </style>
<script>if('serviceWorker'in navigator){navigator.serviceWorker.register('sw.js').catch(function(){})}</script>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <img src="ordenes/logo_login.png?v=<?php echo filemtime('ordenes/logo_login.png'); ?>" alt="FullTaller" onerror="this.style.display='none'">
        <h2><?php echo $taller_nombre_login; ?></h2>
    </div>

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo $error; ?>
    </div>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($subdominio)): ?>
    <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:8px 12px;margin-bottom:16px;font-size:0.8rem;color:#713f12;">
        <strong>Debug:</strong>
        subdominio=<code><?php echo htmlspecialchars($subdominio); ?></code>
        | taller_id=<?php echo $taller_id; ?>
        | db_user=<code><?php echo htmlspecialchars($taller_db_user ?: '(vacio)'); ?></code>
        | db_name=<code><?php echo htmlspecialchars($taller_db_name ?: '(vacio)'); ?></code>
        <?php if (isset($debug_sql_error)): ?>
        <br><strong>Error SQL:</strong> <?php echo htmlspecialchars($debug_sql_error); ?>
        <?php endif; ?>
        <?php if ($deb2_row): ?>
        <br><strong>Sin filtro activo:</strong> id=<?php echo $deb2_row['id']; ?>
        | activo=<?php echo htmlspecialchars($deb2_row['activo'] ?? 'NULL'); ?>
        <?php else: ?>
        <br><strong>Sin filtro activo:</strong> No encontrado
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <div class="form-floating">
            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required autocomplete="off">
            <label for="usuario">Usuario</label>
        </div>
        <div class="form-floating">
            <input type="password" class="form-control" id="password" name="password" placeholder="Contrase&ntilde;a" required>
            <label for="password">Contrase&ntilde;a</label>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Ingresar
        </button>
    </form>

    <div class="version">FullTaller v3.0 | Multi-Taller</div>
</div>
</body>
</html>
