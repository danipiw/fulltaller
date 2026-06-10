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

// Verificar sesión única por usuario (token en BD)
if (!empty($_SESSION['usuario_id'])) {
    $stmt_tk = $conn->prepare("SELECT ultimo_session_token FROM usuarios WHERE id = ?");
    if ($stmt_tk) {
        $stmt_tk->bind_param("i", $_SESSION['usuario_id']);
        $stmt_tk->execute();
        $r_tk = $stmt_tk->get_result();
        $row_tk = $r_tk->fetch_assoc();
        $stmt_tk->close();
        if (!$row_tk || $row_tk['ultimo_session_token'] !== ($_SESSION['session_token'] ?? '')) {
            session_destroy();
            header('Location: ../login.php?error=sesion');
            exit;
        }
    }
}
