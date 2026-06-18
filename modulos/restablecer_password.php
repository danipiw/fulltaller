<?php
session_start();

$error = '';
$exito = '';
$token_valido = false;
$token = '';
$user_id = 0;

// Detectar subdominio
$host_actual = $_SERVER['HTTP_HOST'] ?? '';
$partes = explode('.', $host_actual);
$subdominio = '';
if (count($partes) >= 2) {
    $primer_parte = $partes[0];
    if ($primer_parte !== 'www' && $primer_parte !== 'admin') {
        $subdominio = $primer_parte;
    }
}

// Validar token
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    if (empty($subdominio)) {
        $error = 'No se pudo identificar el taller';
    } else {
        require_once __DIR__ . '/../includes/conexion_central.php';
        $s = $conn_central->real_escape_string($subdominio);
        $r = $conn_central->query("SELECT db_host, db_user, db_pass, db_name FROM talleres WHERE subdominio = '$s' AND activo = 1 LIMIT 1");
        $taller = $r->fetch_assoc();
        $conn_central->close();

        if ($taller) {
            $db_host = $taller['db_host'] ?: 'localhost';
            $conn_w = @new mysqli($db_host, $taller['db_user'], $taller['db_pass'], $taller['db_name']);

            if (!$conn_w->connect_error) {
                $t = $conn_w->real_escape_string($token);
                $rt = $conn_w->query("SELECT usuario_id, expires_at FROM password_reset_tokens WHERE token = '$t' AND used = 0 AND expires_at > NOW() LIMIT 1");

                if ($rt && $row = $rt->fetch_assoc()) {
                    $token_valido = true;
                    $user_id = (int)$row['usuario_id'];
                } else {
                    $error = 'Este enlace es inválido o ya expiró. Solicita uno nuevo.';
                }
                $conn_w->close();
            } else {
                $error = 'Error interno. Intentalo de nuevo más tarde.';
            }
        } else {
            $error = 'Taller no encontrado';
        }
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['token'])) {
    $token = trim($_POST['token']);
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($password) || strlen($password) < 4) {
        $error = 'La contraseña debe tener al menos 4 caracteres';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden';
    } elseif (empty($subdominio)) {
        $error = 'No se pudo identificar el taller';
    } else {
        require_once __DIR__ . '/../includes/conexion_central.php';
        $s = $conn_central->real_escape_string($subdominio);
        $r = $conn_central->query("SELECT db_host, db_user, db_pass, db_name FROM talleres WHERE subdominio = '$s' AND activo = 1 LIMIT 1");
        $taller = $r->fetch_assoc();
        $conn_central->close();

        if ($taller) {
            $db_host = $taller['db_host'] ?: 'localhost';
            $conn_w = @new mysqli($db_host, $taller['db_user'], $taller['db_pass'], $taller['db_name']);

            if (!$conn_w->connect_error) {
                $t = $conn_w->real_escape_string($token);
                $rt = $conn_w->query("SELECT usuario_id FROM password_reset_tokens WHERE token = '$t' AND used = 0 AND expires_at > NOW() LIMIT 1");

                if ($rt && $row = $rt->fetch_assoc()) {
                    $uid = (int)$row['usuario_id'];
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $conn_w->query("UPDATE usuarios SET password = '$hash' WHERE id = $uid");
                    $conn_w->query("UPDATE password_reset_tokens SET used = 1 WHERE token = '$t'");

                    $exito = 'Contraseña actualizada correctamente. Ya podés iniciar sesión con tu nueva contraseña.';
                } else {
                    $error = 'Este enlace es inválido o ya expiró. Solicita uno nuevo.';
                }
                $conn_w->close();
            } else {
                $error = 'Error interno. Intentalo de nuevo más tarde.';
            }
        } else {
            $error = 'Taller no encontrado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif; padding: 16px;
        }
        .card-reset {
            background: white; border-radius: 16px; padding: 40px;
            width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .card-reset h2 { color: var(--jb-navy); font-weight: 700; font-size: 1.3rem; margin-bottom: 8px; }
        .card-reset p { color: #64748b; font-size: 0.9rem; margin-bottom: 24px; }
        .form-floating { margin-bottom: 15px; }
        .form-floating input {
            border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem;
        }
        .form-floating input:focus {
            border-color: var(--jb-azul); box-shadow: 0 0 0 3px rgba(0,119,182,0.15);
        }
        .btn-reset {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none; border-radius: 10px; color: white; font-weight: 600;
            font-size: 1rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-reset:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,119,182,0.4); }
        .alert-success-custom {
            background: #dcfce7; border: 1px solid #bbf7d0; color: #166534;
            padding: 14px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 20px;
        }
        .alert-error {
            background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;
            padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;
        }
        .link-volver { text-align: center; margin-top: 20px; }
        .link-volver a { color: #64748b; font-size: 0.85rem; text-decoration: none; }
        .link-volver a:hover { color: var(--jb-azul); }
        @media (max-width: 480px) {
            body { padding: 8px; }
            .card-reset { padding: 28px 20px; border-radius: 12px; }
        }
    </style>
</head>
<body>
<div class="card-reset">
    <?php if ($exito): ?>
        <div style="text-align:center;margin-bottom:20px;"><span style="font-size:2.5rem;">✅</span></div>
        <h2 style="text-align:center;">Contraseña actualizada</h2>
        <div class="alert-success-custom"><?php echo htmlspecialchars($exito); ?></div>
        <div class="link-volver">
            <a href="<?php echo !empty($subdominio) ? 'login.php' : '../login.php'; ?>" class="btn-rec" style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#0077b6,#023e8a);color:white;text-decoration:none;border-radius:10px;font-weight:600;"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a>
        </div>
    <?php elseif ($token_valido): ?>
        <div style="text-align:center;margin-bottom:20px;"><span style="font-size:2.5rem;">🔑</span></div>
        <h2 style="text-align:center;">Nueva contraseña</h2>
        <p style="text-align:center;">Elegí una nueva contraseña para tu cuenta.</p>

        <?php if ($error): ?>
        <div class="alert-error"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Nueva contraseña" required minlength="4">
                <label for="password">Nueva contraseña</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password2" name="password2" placeholder="Repetir contraseña" required minlength="4">
                <label for="password2">Repetir contraseña</label>
            </div>
            <button type="submit" class="btn-reset"><i class="bi bi-check-lg"></i> Cambiar contraseña</button>
        </form>

        <div class="link-volver">
            <a href="<?php echo !empty($subdominio) ? 'login.php' : '../login.php'; ?>"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
        </div>
    <?php else: ?>
        <div style="text-align:center;margin-bottom:20px;"><span style="font-size:2.5rem;">⛔</span></div>
        <h2 style="text-align:center;">Enlace inválido</h2>
        <?php if ($error): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
        <div class="alert-error">Este enlace no es válido o ya expiró.</div>
        <?php endif; ?>
        <div class="link-volver">
            <a href="solicitar_recuperacion.php" style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#0077b6,#023e8a);color:white;text-decoration:none;border-radius:10px;font-weight:600;"><i class="bi bi-envelope"></i> Solicitar nuevo enlace</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
