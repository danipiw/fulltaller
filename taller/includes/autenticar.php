<?php
session_start();
include 'conexion.php';

$BASE = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

if (!isset($_POST['usuario']) || !isset($_POST['password'])) {
    header("Location: $BASE/login.php?error=credenciales");
    exit;
}

$usuario_ingresado = $conn->real_escape_string($_POST['usuario']);
$password_ingresado = $_POST['password'];

$resultado = $conn->query("SELECT id, usuario, password, nombre, rol FROM usuarios WHERE usuario = '$usuario_ingresado' AND activo = 1 LIMIT 1");

if ($resultado && $usuario = $resultado->fetch_assoc()) {
    if (password_verify($password_ingresado, $usuario['password'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['taller_id'] = $GLOBALS['taller_id'] ?? 0;
        $_SESSION['taller_nombre'] = $GLOBALS['taller_nombre'] ?? '';
        $_SESSION['taller_subdominio'] = $GLOBALS['taller_subdominio'] ?? '';
        header("Location: $BASE/modulos/ordenes/listado.php");
        exit;
    }
}

header("Location: $BASE/login.php?error=credenciales");
exit;
