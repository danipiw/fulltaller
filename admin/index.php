<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: talleres.php');
    exit;
}

$error = '';
if (isset($_GET['error'])) {
    $error = 'Credenciales incorrectas';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - FullTaller</title>
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
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-card h2 {
            color: #001845;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }
        .login-card p {
            color: #64748b;
            text-align: center;
            margin-bottom: 24px;
        }
        .form-floating { margin-bottom: 15px; }
        .form-floating input {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
        }
        .form-floating input:focus {
            border-color: #0077b6;
            box-shadow: 0 0 0 3px rgba(0,119,182,0.15);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0077b6, #023e8a);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,119,182,0.4);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2><i class="bi bi-shield-lock"></i> Admin</h2>
        <p>Panel de administración de talleres</p>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="autenticar_admin.php" method="POST">
            <div class="form-floating">
                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required>
                <label for="usuario">Usuario</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                <label for="password">Contraseña</label>
            </div>
            <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
        </form>
    </div>
</body>
</html>
