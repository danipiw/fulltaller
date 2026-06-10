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
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_usuario'] = $admin['usuario'];
    $_SESSION['admin_nombre'] = $admin['nombre'];
    header('Location: talleres.php');
    exit;
}

header('Location: index.php?error=1');
exit;
