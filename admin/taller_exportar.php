<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID inválido');
}

$id = (int)$_GET['id'];
$exportar_pos = isset($_GET['pos']);

require_once __DIR__ . '/../includes/conexion_central.php';

$taller = $conn_central->query("SELECT * FROM talleres WHERE id = $id")->fetch_assoc();
if (!$taller) {
    die('Taller no encontrado');
}

$filename = $taller['subdominio'] . ($exportar_pos ? '_pos' : '') . '_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: text/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "-- Exportación de: " . $taller['nombre'] . ($exportar_pos ? ' (solo POS)' : '') . "\n";
echo "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "-- Subdominio: {$taller['subdominio']}\n\n";

$mysqli_export = new mysqli($taller['db_host'], $taller['db_user'], $taller['db_pass'], $taller['db_name']);
if ($mysqli_export->connect_error) {
    die('Error conectando a la BD del taller: ' . $mysqli_export->connect_error);
}

$tables = $mysqli_export->query("SHOW TABLES");
while ($row = $tables->fetch_row()) {
    $table = $row[0];

    if ($exportar_pos && strpos($table, 'pos_') !== 0) {
        continue;
    }

    $create = $mysqli_export->query("SHOW CREATE TABLE `$table`")->fetch_row();
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $create[1] . ";\n\n";

    $data = $mysqli_export->query("SELECT * FROM `$table`");
    $cols = $data->fetch_fields();

    $col_names = [];
    foreach ($cols as $col) {
        $col_names[] = "`{$col->name}`";
    }
    $col_list = implode(', ', $col_names);

    while ($row_data = $data->fetch_row()) {
        $vals = [];
        foreach ($row_data as $val) {
            if ($val === null) {
                $vals[] = 'NULL';
            } else {
                $vals[] = "'" . $mysqli_export->real_escape_string($val) . "'";
            }
        }
        echo "INSERT INTO `$table` ($col_list) VALUES (" . implode(', ', $vals) . ");\n";
    }
    echo "\n";
}

$mysqli_export->close();
