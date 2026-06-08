<?php
// Reuse ordenes DB connection logic
$ordenes_conexion = __DIR__ . '/../../ordenes/includes/conexion.php';
if (file_exists($ordenes_conexion)) {
    require_once $ordenes_conexion;
} else {
    die('Error: módulo ordenes no encontrado.');
}
