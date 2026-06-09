<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$user_id = (int)$_SESSION['usuario_id'];

// Verificar que el usuario realmente existe en la BD
$check_user = $db->query("SELECT id FROM usuarios WHERE id = $user_id AND activo = 1");
if (!$check_user || $check_user->num_rows === 0) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$hoy = date('Y-m-d');

// Si ya tiene caja abierta hoy, redirigir al POS
$check = $db->query("SELECT id FROM pos_caja WHERE usuario_id = $user_id AND fecha_apertura = '$hoy' AND estado = 'abierta'");
if ($check && $check->num_rows > 0) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto_inicial = floatval($_POST['monto_inicial'] ?? 0);
    if ($monto_inicial < 0) {
        $error = 'El monto no puede ser negativo.';
    } else {
        $hora = date('H:i:s');
        $stmt = $db->prepare("INSERT INTO pos_caja (usuario_id, fecha_apertura, hora_apertura, monto_inicial, estado) VALUES (?, ?, ?, ?, 'abierta')");
        $stmt->bind_param("issd", $user_id, $hoy, $hora, $monto_inicial);
        if ($stmt->execute()) {
            $_SESSION['caja_id'] = $db->insert_id;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Error al abrir la caja: ' . $stmt->error;
        }
    }
}
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Caja - POS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="pos-wrapper" style="max-width:480px;margin:60px auto;">
    <div class="panel" style="text-align:center;">
        <h1>🤑 Inicio de Caja</h1>
        <p style="color:#64748b;margin-bottom:24px;">
            ¡Buenos días, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>!<br>
            Ingresá el dinero con el que arrancás el turno.
        </p>

        <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" style="max-width:300px;margin:0 auto;">
            <div class="form-group">
                <label>Monto inicial en caja ($)</label>
                <input type="number" name="monto_inicial" step="0.01" min="0" value="0.00" required autofocus
                    style="font-size:1.5rem;text-align:center;padding:16px;border:2px solid var(--jb-azul);border-radius:12px;width:100%;">
            </div>
            <button type="submit" class="btn-guardar" style="width:100%;padding:16px;font-size:1.1rem;margin-top:8px;">
                🚀 Abrir Caja
            </button>
        </form>

        <p style="margin-top:20px;font-size:0.85rem;color:#94a3b8;">
            <a href="logout.php" style="color:#ef4444;">Cerrar sesión</a>
        </p>
    </div>
</div>
</body>
</html>