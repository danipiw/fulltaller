<?php
session_start();
include __DIR__ . '/../modulos/ordenes/includes/conexion.php';

$BASE = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

if (!isset($_POST['usuario']) || !isset($_POST['password'])) {
    header("Location: $BASE/login.php?error=credenciales");
    exit;
}

$usuario_ingresado = $conn->real_escape_string($_POST['usuario']);
$password_ingresado = $_POST['password'];

$resultado = $conn->query("SELECT id, usuario, password, nombre, rol, modulos FROM usuarios WHERE usuario = '$usuario_ingresado' AND activo = 1 LIMIT 1");

if ($resultado && $usuario = $resultado->fetch_assoc()) {
    if (password_verify($password_ingresado, $usuario['password'])) {
        session_destroy();
        session_start();
        session_regenerate_id(true);

        $check_col = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_session_token'");
        if (!$check_col || $check_col->num_rows == 0) {
            $conn->query("ALTER TABLE usuarios ADD COLUMN ultimo_session_token VARCHAR(64) DEFAULT NULL AFTER password");
        }

        $session_token = bin2hex(random_bytes(32));
        $stmt_up = $conn->prepare("UPDATE usuarios SET ultimo_session_token = ? WHERE id = ?");
        $stmt_up->bind_param("si", $session_token, $usuario['id']);
        $stmt_up->execute();
        $stmt_up->close();

        $_SESSION['session_token'] = $session_token;
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['taller_id'] = $GLOBALS['taller_id'] ?? 0;
        $_SESSION['taller_nombre'] = $GLOBALS['taller_nombre'] ?? '';
        $_SESSION['taller_subdominio'] = $GLOBALS['taller_subdominio'] ?? '';
        $_SESSION['taller_db_host'] = $GLOBALS['taller_db_host'] ?? 'localhost';
        $_SESSION['taller_db_user'] = $GLOBALS['taller_db_user'] ?? '';
        $_SESSION['taller_db_pass'] = $GLOBALS['taller_db_pass'] ?? '';
        $_SESSION['taller_db_name'] = $GLOBALS['taller_db_name'] ?? '';
        $_SESSION['user_modulos'] = $usuario['modulos'] ?? 'ordenes';
        $_SESSION['api_token'] = bin2hex(random_bytes(16));
        $_SESSION['login_host'] = $_SERVER['HTTP_HOST'] ?? '';
        header("Location: $BASE/modulos/ordenes/listado.php");
        exit;
    }
}

header("Location: $BASE/login.php?error=credenciales");
exit;
