<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../includes/conexion_central.php';

$stmt_tk = $conn_central->prepare("SELECT ultimo_session_token FROM admin_usuarios WHERE id = ?");
$stmt_tk->bind_param("i", $_SESSION['admin_id']);
$stmt_tk->execute();
$r_tk = $stmt_tk->get_result();
$row_tk = $r_tk->fetch_assoc();
$stmt_tk->close();

if (!$row_tk || $row_tk['ultimo_session_token'] !== ($_SESSION['admin_session_token'] ?? '')) {
    session_destroy();
    header('Location: index.php?error=sesion');
    exit;
}
