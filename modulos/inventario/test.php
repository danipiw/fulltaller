<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
echo "1. Session: " . (isset($_SESSION['usuario_id']) ? 'OK' : 'NO') . "\n";
echo "2. PDO class: " . (class_exists('PDO') ? 'OK' : 'NO') . "\n";
echo "3. DB pass: " . ($_SESSION['taller_db_pass'] ?? 'VACIO') . "\n";
echo "4. DB name: " . ($_SESSION['taller_db_name'] ?? 'VACIO') . "\n";
echo "5. SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'VACIO') . "\n";
require_once 'includes/conexion.php';
echo "6. Conexion OK\n";
echo "7. PDO in GLOBALS: " . (isset($GLOBALS['pdo']) ? 'OK' : 'NO') . "\n";
