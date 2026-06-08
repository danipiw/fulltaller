<?php
session_start();
if (!isset($_SESSION['rol'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }
if (!empty($_SESSION['login_host']) && $_SESSION['login_host'] !== ($_SERVER['HTTP_HOST'] ?? '')) { session_destroy(); header('Content-Type: application/json'); echo json_encode([]); exit; }
include 'includes/conexion.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'tipos') {
    $q = $conn->query("SELECT DISTINCT tipo FROM repuestos WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo");
    $data = [];
    while ($r = $q->fetch_assoc()) $data[] = $r['tipo'];
    echo json_encode($data);
    exit;
}

if ($action === 'marcas') {
    $tipo = $_GET['tipo'] ?? '';
    if ($tipo) {
        $stmt = $conn->prepare("SELECT DISTINCT marca FROM repuestos WHERE tipo = ? AND marca IS NOT NULL AND marca != '' ORDER BY marca");
        $stmt->bind_param('s', $tipo);
    } else {
        $stmt = $conn->prepare("SELECT DISTINCT marca FROM repuestos WHERE marca IS NOT NULL AND marca != '' ORDER BY marca");
    }
    $stmt->execute();
    $data = [];
    $q = $stmt->get_result();
    while ($r = $q->fetch_assoc()) $data[] = $r['marca'];
    echo json_encode($data);
    exit;
}

if ($action === 'modelos') {
    $tipo = $_GET['tipo'] ?? '';
    $marca = $_GET['marca'] ?? '';
    $sql = "SELECT id, tipo, marca, modelo, precio FROM repuestos WHERE 1=1";
    $params = [];
    $types = '';
    if ($tipo) { $sql .= " AND tipo = ?"; $params[] = $tipo; $types .= 's'; }
    if ($marca) { $sql .= " AND marca = ?"; $params[] = $marca; $types .= 's'; }
    $sql .= " ORDER BY modelo";
    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $data = [];
    $q = $stmt->get_result();
    while ($r = $q->fetch_assoc()) $data[] = $r;
    echo json_encode($data);
    exit;
}

if ($action === 'buscar') {
    $tipo = $_GET['tipo'] ?? '';
    $marca = $_GET['marca'] ?? '';
    $modelo = $_GET['modelo'] ?? '';
    $sql = "SELECT id, tipo, marca, modelo, precio FROM repuestos WHERE 1=1";
    $params = [];
    $types = '';
    if ($tipo) { $sql .= " AND tipo = ?"; $params[] = $tipo; $types .= 's'; }
    if ($marca) { $sql .= " AND marca = ?"; $params[] = $marca; $types .= 's'; }
    if ($modelo) { $sql .= " AND modelo LIKE ?"; $params[] = "%$modelo%"; $types .= 's'; }
    $sql .= " ORDER BY tipo, marca, modelo";
    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $data = [];
    $q = $stmt->get_result();
    while ($r = $q->fetch_assoc()) $data[] = $r;
    echo json_encode($data);
    exit;
}

echo json_encode([]);
