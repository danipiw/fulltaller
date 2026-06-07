<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header('Location: ../../login.php?error=sesion');
    exit;
}

require_once __DIR__ . '/conexion.php';

$ROL_ACTUAL = $_SESSION['rol'];
$ES_FULL = ($ROL_ACTUAL === 'full');
$ES_ADMIN = ($ROL_ACTUAL === 'admin');
$NOMBRE_USUARIO = $_SESSION['nombre'] ?? 'Usuario';
$TALLER_NOMBRE = $GLOBALS['taller_nombre'] ?? 'Taller';
$TALLER_SUBDOMINIO = $GLOBALS['taller_subdominio'] ?? '';
$TALLER_ID = $GLOBALS['taller_id'] ?? 0;
