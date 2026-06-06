<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin_id'])) {
    die('Acceso denegado. Debe iniciar sesión como administrador.');
}

if (!isset($_GET['taller_id']) || !is_numeric($_GET['taller_id']) || !isset($_GET['key'])) {
    die('Parámetros inválidos');
}

$taller_id = (int)$_GET['taller_id'];
$key = $_GET['key'];

$central_path = __DIR__ . '/includes/conexion_central.php';
if (!file_exists($central_path)) {
    die('Error: No se encuentra ' . $central_path);
}
require_once $central_path;

$result = $conn_central->query("SELECT * FROM talleres WHERE id = $taller_id AND license_key = '" . $conn_central->real_escape_string($key) . "'");
if (!$result) {
    die('Error en consulta MySQL: ' . $conn_central->error);
}
$taller = $result->fetch_assoc();

if (!$taller) {
    die('Taller no encontrado o key inválida');
}

$host = $taller['db_host'];
$user = $taller['db_user'];
$pass = $taller['db_pass'];
$dbname = $taller['db_name'];

mysqli_report(MYSQLI_REPORT_OFF);
$conn_setup = new mysqli($host, $user, $pass, $dbname);
if ($conn_setup->connect_error) {
    $conn_setup = new mysqli($host, $user, $pass);
    if ($conn_setup->connect_error) {
        die('Error conectando al servidor MySQL: ' . $conn_setup->connect_error);
    }
    $dbname_escaped = $conn_setup->real_escape_string($dbname);
    if (!$conn_setup->query("CREATE DATABASE IF NOT EXISTS `$dbname_escaped` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
        die('Error creando la base de datos. Asegurate de asignar el usuario MySQL a la BD desde el panel de DonWeb.<br>Usuario: <strong>c2821557_tecnost</strong><br>BD: <strong>' . htmlspecialchars($dbname_escaped) . '</strong><br>Error: ' . $conn_setup->error);
    }
    $conn_setup->select_db($dbname_escaped);
}

$sql_path = __DIR__ . '/sql/taller_template.sql';
if (!file_exists($sql_path)) {
    die('Error: No se encuentra el archivo sql/taller_template.sql en el servidor.');
}

$sql = file_get_contents($sql_path);

if (!$conn_setup->multi_query($sql)) {
    die('Error SQL al crear tablas: ' . $conn_setup->error);
}

do {
    if ($res = $conn_setup->store_result()) {
        $res->free();
    }
} while ($conn_setup->more_results() && $conn_setup->next_result());

$conn_setup->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup Completo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: system-ui, sans-serif; }
    </style>
</head>
<body>
<div class="card p-4" style="max-width:500px;text-align:center;border-radius:12px;border:none;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
    <div style="font-size:3rem;color:#16a34a;"><i class="bi bi-check-circle-fill"></i></div>
    <h3 class="mt-2">¡Base de datos configurada!</h3>
    <p>Taller: <strong><?php echo htmlspecialchars($taller['nombre']); ?></strong><br>
    Subdominio: <code><?php echo htmlspecialchars($taller['subdominio']); ?></code><br>
    DB: <code><?php echo htmlspecialchars($taller['db_name']); ?></code><br>
    License Key: <code><?php echo htmlspecialchars($taller['license_key']); ?></code></p>
    <p>Usuarios por defecto: <strong>recepcion</strong> / <strong>tecnico</strong> (contraseña: <code>password</code>)</p>
    <p class="text-muted small">Cambiá las contraseñas desde <code>gestion_usuarios.php</code>.</p>
    <a href="admin/talleres.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Volver al panel</a>
</div>
</body>
</html>
