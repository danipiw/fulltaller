<?php
session_start();
if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos'])) {
    header('Location: login.php');
    exit;
}
$user_modulos = array_map('trim', explode(',', $_SESSION['user_modulos'] ?? ''));
if (!in_array('tienda', $user_modulos)) {
    header('Location: ../dashboard.php?error=sin_acceso');
    exit;
}
$ES_ADMIN = in_array($_SESSION['rol'] ?? '', ['admin', 'full']);
$PUEDE_EDITAR_TIENDA = $ES_ADMIN;
require_once __DIR__ . '/conexion.php';
