<?php
session_start();
require_once __DIR__ . '/../includes/conexion_central.php';

if (!isset($_POST['usuario']) || !isset($_POST['password'])) {
    header('Location: index.php?error=1');
    exit;
}

$usuario = $_POST['usuario'];
$password = $_POST['password'];

$stmt = $conn_central->prepare("SELECT id, usuario, password, nombre FROM admin_usuarios WHERE usuario = ? LIMIT 1");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if ($admin && password_verify($password, $admin['password'])) {
    session_destroy();
    session_start();
    session_regenerate_id(true);

    $check_col = $conn_central->query("SHOW COLUMNS FROM admin_usuarios LIKE 'ultimo_session_token'");
    if (!$check_col || $check_col->num_rows == 0) {
        $conn_central->query("ALTER TABLE admin_usuarios ADD COLUMN ultimo_session_token VARCHAR(64) DEFAULT NULL AFTER password");
    }

    $session_token = bin2hex(random_bytes(32));
    $stmt_up = $conn_central->prepare("UPDATE admin_usuarios SET ultimo_session_token = ? WHERE id = ?");
    $stmt_up->bind_param("si", $session_token, $admin['id']);
    $stmt_up->execute();
    $stmt_up->close();

    $_SESSION['admin_session_token'] = $session_token;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_usuario'] = $admin['usuario'];
    $_SESSION['admin_nombre'] = $admin['nombre'];
    header('Location: talleres.php');
    exit;
}

header('Location: index.php?error=1');
exit;
