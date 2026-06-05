<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['rol'])) {
    header("Location: $BASE_PATH/../login.php?error=sesion");
    exit;
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
