<?php
require_once __DIR__ . '/includes/conexion_central.php';

$nueva_pass = 'tecnostore2026';
$hash = password_hash($nueva_pass, PASSWORD_DEFAULT);

$conn_central->query("UPDATE admin_usuarios SET password = '$hash' WHERE usuario = 'admin'");

echo "Contraseña de admin cambiada a: <strong>$nueva_pass</strong><br>";
echo "<a href='admin/index.php'>Ir al panel</a>";
