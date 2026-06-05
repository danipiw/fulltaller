<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Acceso denegado');
}

require_once __DIR__ . '/includes/conexion_central.php';

$t = $conn_central->query("SELECT * FROM talleres WHERE subdominio = 'jbelectronica' LIMIT 1")->fetch_assoc();
if (!$t) die('Taller no encontrado');

$conn_t = new mysqli($t['db_host'], $t['db_user'], $t['db_pass'], $t['db_name']);
if ($conn_t->connect_error) die('Error conectando: ' . $conn_t->connect_error);

$archivos = [
    __DIR__ . '/sql/export_modulos.sql' => 'módulos',
    __DIR__ . '/sql/export_baterias.sql' => 'baterías',
    __DIR__ . '/sql/export_pin_carga.sql' => 'pin de carga',
];

echo "<h3>Importando repuestos a FullTaller</h3><ul>";

foreach ($archivos as $path => $nombre) {
    if (!file_exists($path)) {
        echo "<li style='color:orange;'>✗ $nombre — archivo no encontrado</li>";
        continue;
    }
    $sql = file_get_contents($path);
    if ($conn_t->multi_query($sql)) {
        do { if ($r = $conn_t->store_result()) $r->free(); } 
        while ($conn_t->more_results() && $conn_t->next_result());
        echo "<li style='color:green;'>✓ $nombre importado</li>";
    } else {
        echo "<li style='color:red;'>✗ $nombre — error: " . $conn_t->error . "</li>";
    }
}

echo "</ul><p><a href='repuestos.php'>Ver repuestos</a></p>";
