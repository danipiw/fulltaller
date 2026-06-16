<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['rol'])) {
    header("Location: $BASE_PATH/../login.php?error=sesion");
    exit;
}

// Verificar sesión única por usuario (token en BD)
if (!empty($_SESSION['usuario_id'])) {
    try {
        $stmt_tk = $pdo->prepare("SELECT ultimo_session_token FROM usuarios WHERE id = ?");
        $stmt_tk->execute([(int)$_SESSION['usuario_id']]);
        $row_tk = $stmt_tk->fetch(PDO::FETCH_ASSOC);
        if (!$row_tk || $row_tk['ultimo_session_token'] !== ($_SESSION['session_token'] ?? '')) {
            session_destroy();
            header("Location: $BASE_PATH/../login.php?error=sesion");
            exit;
        }
    } catch (PDOException $e) {
        // Si la columna no existe aún, ignoramos
    }
}

$user_modulos = explode(',', $_SESSION['user_modulos'] ?? '');
$user_modulos = array_map('trim', $user_modulos);
if (!in_array('inventario', $user_modulos)) {
    session_destroy();
    header("Location: $BASE_PATH/../login.php?error=sin_acceso");
    exit;
}

$ROL_ACTUAL = $_SESSION['rol'];
$ES_FULL = ($ROL_ACTUAL === 'full');
$ES_ADMIN = ($ROL_ACTUAL === 'admin');
$ES_TECNICO = ($ROL_ACTUAL === 'tecnico' || $ES_FULL);
$ES_RECEPCION = ($ROL_ACTUAL === 'recepcion' || $ES_FULL);
$NOMBRE_USUARIO = $_SESSION['nombre'] ?? 'Usuario';
$TALLER_NOMBRE = $GLOBALS['taller_nombre'] ?? 'Taller';
$TALLER_SUBDOMINIO = $GLOBALS['taller_subdominio'] ?? '';
$TALLER_ID = $GLOBALS['taller_id'] ?? 0;
