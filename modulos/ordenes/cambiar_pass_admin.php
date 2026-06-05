<?php
require_once __DIR__ . '/includes/conexion_central.php';

$nueva = 'Dbjtecnostore';
$hash = password_hash($nueva, PASSWORD_DEFAULT);
$conn_central->query("UPDATE admin_usuarios SET password = '$hash' WHERE usuario = 'admin'");
echo "Contraseña de admin cambiada a: <strong>$nueva</strong><br>";
echo "Eliminá este archivo del servidor.";
