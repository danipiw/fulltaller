<?php
session_start();

$mensaje = '';
$error = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Ingresá tu correo electrónico';
    } elseif (empty($subdominio)) {
        $error = 'No se pudo identificar el taller';
    } else {
        // Conectar a base central para obtener datos del taller
        require_once __DIR__ . '/../includes/conexion_central.php';

        $s = $conn_central->real_escape_string($subdominio);
        $r = $conn_central->query("SELECT id, db_host, db_user, db_pass, db_name FROM talleres WHERE subdominio = '$s' AND activo = 1 LIMIT 1");
        $taller = $r->fetch_assoc();

        if ($taller) {
            $db_host = $taller['db_host'] ?: 'localhost';
            $conn_w = @new mysqli($db_host, $taller['db_user'], $taller['db_pass'], $taller['db_name']);

            if (!$conn_w->connect_error) {
                // Auto-migrar columna correo
                $ck = $conn_w->query("SHOW COLUMNS FROM usuarios LIKE 'correo'");
                if (!$ck || $ck->num_rows == 0) {
                    $conn_w->query("ALTER TABLE usuarios ADD COLUMN correo VARCHAR(255) DEFAULT NULL AFTER nombre, ADD UNIQUE KEY uk_usuarios_correo (correo)");
                }

                // Auto-crear tabla de tokens
                $conn_w->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_token (token),
                    KEY idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Buscar usuario por correo
                $e = $conn_w->real_escape_string($email);
                $ru = $conn_w->query("SELECT id, nombre, usuario FROM usuarios WHERE correo = '$e' AND activo = 1 LIMIT 1");

                if ($user = $ru->fetch_assoc()) {
                    // Generar token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $stmt = $conn_w->prepare("INSERT INTO password_reset_tokens (usuario_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user['id'], $token, $expires);
                    $stmt->execute();
                    $stmt->close();

                    // Enviar email
                    require_once __DIR__ . '/../includes/enviar_email.php';

                    $reset_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$host_actual/modulos/restablecer_password.php?token=$token";
                    $taller_nombre = $GLOBALS['taller_nombre'] ?? htmlspecialchars($taller['nombre'] ?? 'Taller');

                    $html = "
                    <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:24px;'>
                        <div style='background:linear-gradient(135deg,#001845,#023e8a,#0077b6);padding:24px;border-radius:12px 12px 0 0;text-align:center;'>
                            <h2 style='color:white;margin:0;font-size:1.3rem;'>$taller_nombre</h2>
                        </div>
                        <div style='background:#fff;padding:24px;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 12px 12px;'>
                            <p style='color:#334155;font-size:1rem;'>Hola <strong>" . htmlspecialchars($user['nombre']) . "</strong>,</p>
                            <p style='color:#475569;'>Recibimos una solicitud para restablecer la contraseña de tu usuario <strong>" . htmlspecialchars($user['usuario']) . "</strong>.</p>
                            <p style='text-align:center;margin:28px 0;'>
                                <a href='$reset_url' style='display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#0077b6,#023e8a);color:white;text-decoration:none;border-radius:8px;font-weight:600;'>Restablecer contraseña</a>
                            </p>
                            <p style='color:#94a3b8;font-size:0.85rem;'>Este enlace expira en 1 hora. Si no solicitaste este cambio, ignorá este mensaje.</p>
                            <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                            <p style='color:#94a3b8;font-size:0.8rem;text-align:center;'>$taller_nombre &mdash; Sistema de Gestión de Talleres</p>
                        </div>
                    </div>";

                    if (enviarEmail($email, 'Restablecer tu contraseña', $html)) {
                        $mensaje = 'Si ese correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña. Revisá tu bandeja de entrada (y la carpeta de spam).';
                    } else {
                        // Token creado pero email falló - mostrar mensaje genérico igual (no revelar)
                        error_log("Fallo envío email recuperación para $email en $subdominio");
                        $mensaje = 'Si ese correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña. Revisá tu bandeja de entrada (y la carpeta de spam).';
                    }
                } else {
                    // No revelar si el correo existe o no
                    $mensaje = 'Si ese correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña. Revisá tu bandeja de entrada (y la carpeta de spam).';
                }
                $conn_w->close();
            } else {
                $error = 'Error interno. Intentalo de nuevo más tarde.';
            }
            $conn_central->close();
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
    <title>Recuperar contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif; padding: 16px;
        }
        .card-rec {
            background: white; border-radius: 16px; padding: 40px;
            width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .card-rec h2 { color: var(--jb-navy); font-weight: 700; font-size: 1.3rem; margin-bottom: 8px; }
        .card-rec p { color: #64748b; font-size: 0.9rem; margin-bottom: 24px; }
        .form-floating { margin-bottom: 15px; }
        .form-floating input {
            border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem;
        }
        .form-floating input:focus {
            border-color: var(--jb-azul); box-shadow: 0 0 0 3px rgba(0,119,182,0.15);
        }
        .btn-rec {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            border: none; border-radius: 10px; color: white; font-weight: 600;
            font-size: 1rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-rec:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,119,182,0.4); }
        .alert-info-custom {
            background: #e0f2fe; border: 1px solid #bae6fd; color: #0c4a6e;
            padding: 14px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
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
            .card-rec { padding: 28px 20px; border-radius: 12px; }
        }
    </style>
</head>
<body>
<div class="card-rec">
    <div style="text-align:center;margin-bottom:20px;">
        <span style="font-size:2.5rem;">🔐</span>
    </div>
    <h2 style="text-align:center;">Recuperar contraseña</h2>
    <p style="text-align:center;">Ingresá tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

    <?php if ($mensaje): ?>
    <div class="alert-info-custom"><i class="bi bi-envelope-check"></i> <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-error"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-floating">
            <input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" required autocomplete="email">
            <label for="email">Correo electrónico</label>
        </div>
        <button type="submit" class="btn-rec"><i class="bi bi-send"></i> Enviar enlace</button>
    </form>

    <div class="link-volver">
        <a href="<?php echo !empty($subdominio) ? 'login.php' : '../login.php'; ?>"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
    </div>
</div>
</body>
</html>
