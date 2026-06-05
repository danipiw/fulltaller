<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header('Location: ../../login.php?error=sesion');
    exit;
}

require_once __DIR__ . '/conexion.php';

$ROL_ACTUAL = $_SESSION['rol'];
$ES_FULL = ($ROL_ACTUAL === 'full');
$ES_RECEPCION = ($ROL_ACTUAL === 'recepcion' || $ES_FULL);
$ES_TECNICO = ($ROL_ACTUAL === 'tecnico' || $ES_FULL);
$ES_ADMIN = ($ROL_ACTUAL === 'admin');
$NOMBRE_USUARIO = $_SESSION['nombre'] ?? 'Usuario';

$VISTA_ADMIN = $_SESSION['admin_vista'] ?? 'recepcion';
if ($ES_ADMIN) {
    if (isset($_GET['cambiar_vista']) && in_array($_GET['cambiar_vista'], ['recepcion', 'tecnico', 'full'])) {
        $_SESSION['admin_vista'] = $_GET['cambiar_vista'];
        $VISTA_ADMIN = $_GET['cambiar_vista'];
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    $ES_RECEPCION = in_array($VISTA_ADMIN, ['recepcion', 'full']);
    $ES_TECNICO = in_array($VISTA_ADMIN, ['tecnico', 'full']);
    $ES_FULL = ($VISTA_ADMIN === 'full');
    $ROL_VISUAL = $VISTA_ADMIN;
} else {
    $ROL_VISUAL = $ROL_ACTUAL;
}

$TALLER_NOMBRE = $GLOBALS['taller_nombre'] ?? 'Taller';
$TALLER_SUBDOMINIO = $GLOBALS['taller_subdominio'] ?? '';
$TALLER_ID = $GLOBALS['taller_id'] ?? 0;
