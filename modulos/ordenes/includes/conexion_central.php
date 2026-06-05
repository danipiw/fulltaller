<?php

$central_host = 'localhost';
$central_user = 'c2821557_tecnost';
$central_pass = 'rifiniTA85';
$central_db = 'c2821557_central';

// config_local.php solo se usa en entorno local (NUNCA subir al hosting)
if (file_exists(__DIR__ . '/config_local.php') && !empty(getenv('COMPUTERNAME'))) {
    $config = include __DIR__ . '/config_local.php';
    if (isset($config['central_host'])) $central_host = $config['central_host'];
    if (isset($config['central_user'])) $central_user = $config['central_user'];
    if (isset($config['central_pass'])) $central_pass = $config['central_pass'];
    if (isset($config['central_db'])) $central_db = $config['central_db'];
}

define('CENTRAL_HOST', $central_host);
define('CENTRAL_USER', $central_user);
define('CENTRAL_PASS', $central_pass);
define('CENTRAL_DB', $central_db);

$conn_central = new mysqli(CENTRAL_HOST, CENTRAL_USER, CENTRAL_PASS, CENTRAL_DB);

if ($conn_central->connect_error) {
    die("Error de conexión central: " . $conn_central->connect_error);
}
