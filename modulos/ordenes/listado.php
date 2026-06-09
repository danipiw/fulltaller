<?php
include 'includes/verificar_sesion.php';
include 'includes/estados_helper.php';

$clientesLista = $conn->query("SELECT * FROM clientes ORDER BY nombre ASC LIMIT 200");

$taller_nombre_l = 'FullTaller';
$tipo_impresion_l = 'separada';
$cfg_l = $conn->query("SELECT clave, valor FROM configuracion WHERE clave IN ('taller_nombre', 'tipo_impresion')");
if ($cfg_l) {
    while ($cfg_row_l = $cfg_l->fetch_assoc()) {
        if ($cfg_row_l['clave'] === 'taller_nombre') $taller_nombre_l = $cfg_row_l['valor'];
        if ($cfg_row_l['clave'] === 'tipo_impresion') $tipo_impresion_l = $cfg_row_l['valor'];
    }
}

$busqueda = '';
$filtro = '';
$estados_seleccionados = [];

$todos_estados = obtenerTodosEstados($conn);
$estados_cambiables_recepcion = obtenerEstadosRecepcion($conn);
$estados_cambiables_tecnico = obtenerEstadosTecnico($conn);
if ($ES_FULL || $ES_ADMIN) {
    $estados_cambiables = array_values(array_unique(array_merge($estados_cambiables_recepcion, $estados_cambiables_tecnico)));
} elseif ($ES_RECEPCION) {
    $estados_cambiables = $estados_cambiables_recepcion;
} else {
    $estados_cambiables = $estados_cambiables_tecnico;
}

$sqlBase = "
    SELECT 
        ordenes.*,
        clientes.nombre AS cliente_nombre,
        clientes.telefono
    FROM ordenes
    INNER JOIN clientes ON ordenes.cliente_id = clientes.id
";

$where = [];
$params = [];
$types = "";

if (isset($_GET['estados']) && is_array($_GET['estados'])) {
    $estados_seleccionados = $_GET['estados'];
    if (!empty($estados_seleccionados)) {
        $placeholders = implode(',', array_fill(0, count($estados_seleccionados), '?'));
        $where[] = "ordenes.estado IN ($placeholders)";
        foreach ($estados_seleccionados as $estado) {
            $params[] = $estado;
            $types .= "s";
        }
    } else {
        $where[] = "ordenes.estado != 'ENTREGADO'";
    }
} else {
    $where[] = "ordenes.estado != 'ENTREGADO'";
}

if (isset($_GET['filtro']) && isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $filtro = $_GET['filtro'];
    $busqueda = $_GET['buscar'];

    if ($filtro == 'id' && is_numeric($busqueda)) {
        $where[] = "ordenes.id = ?";
        $params[] = (int)$busqueda;
        $types .= "i";
    }
    elseif ($filtro == 'modelo') {
        $where[] = "ordenes.modelo LIKE ?";
        $params[] = "%" . $busqueda . "%";
        $types .= "s";
    }
    elseif ($filtro == 'imei') {
        $where[] = "ordenes.imei LIKE ?";
        $params[] = "%" . $busqueda . "%";
        $types .= "s";
    }
}

if (isset($_GET['filtro']) && $filtro == 'cliente' && !empty($_GET['buscar_cliente'])) {
    $where[] = "clientes.nombre = ?";
    $params[] = $_GET['buscar_cliente'];
    $types .= "s";
    $busqueda = $_GET['buscar_cliente'];
}

// Pagination
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

// Count query
$sql_count = "SELECT COUNT(*) as total FROM ordenes INNER JOIN clientes ON ordenes.cliente_id = clientes.id";
if (!empty($where)) {
    $sql_count .= " WHERE " . implode(" AND ", $where);
}
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_ordenes = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_ordenes / $por_pagina));

$sql = $sqlBase;
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY ordenes.id DESC LIMIT $por_pagina OFFSET $offset";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

function badgeClass($estado) {
    return badgeClassEstados($estado);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Órdenes - <?php echo $ES_TECNICO ? 'Técnico' : 'Recepción'; ?></title>
    <link rel="manifest" href="manifest.php">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="icon.php?size=192">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --jb-cyan: #00a8e8;
            --jb-azul: #0077b6;
            --jb-azul-oscuro: #023e8a;
            --jb-navy: #001845;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background-color: #f0f4f8; }

        /* Navbar */
        .nav-jb {
            background: linear-gradient(135deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 50%, var(--jb-azul) 100%);
            padding: 0.2rem 1.5rem 0.2rem 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,56,168,0.3);
        }
        .nav-jb .nav-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .nav-jb .nav-brand {
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-jb .nav-brand img { height: 36px; width: auto; }
        .nav-jb .nav-btn {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .nav-jb .nav-btn:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }
        .nav-jb .nav-btn.active {
            background-color: var(--jb-cyan);
            color: var(--jb-navy);
            font-weight: 600;
            border-color: var(--jb-cyan);
        }
        .nav-jb .nav-sep { color: rgba(255,255,255,0.3); margin: 0 0.25rem; }

        .rol-badge {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== NOTIFICACIONES ===== */
        .notificaciones-container { position: relative; margin-right: 10px; }
        .btn-notificaciones {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            font-size: 1.2rem;
        }
        .btn-notificaciones:hover { background: rgba(255,255,255,0.2); transform: scale(1.05); }
        .btn-notificaciones.pulse { animation: notifPulse 1.5s ease infinite; }
        @keyframes notifPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        .notif-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--jb-navy);
        }
        .dropdown-notificaciones {
            position: absolute;
            top: 50px;
            right: 0;
            width: 340px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 1050;
            display: none;
            overflow: hidden;
        }
        .dropdown-notificaciones.show { display: block; animation: fadeInDown 0.2s ease; }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: var(--jb-navy);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notif-lista { max-height: 350px; overflow-y: auto; }
        .notif-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .notif-item:hover { background: #f8fafc; }
        .notif-item.no-leida {
            background: #eff6ff;
            border-left: 3px solid var(--jb-azul);
        }
        .notif-item.no-leida:hover { background: #dbeafe; }
        .notif-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .notif-icon.recepcion { background: #dbeafe; color: #1d4ed8; }
        .notif-icon.tecnico { background: #dcfce7; color: #15803d; }
        .notif-content { flex: 1; min-width: 0; }
        .notif-titulo { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 2px; }
        .notif-mensaje { font-size: 0.8rem; color: #64748b; line-height: 1.3; }
        .notif-fecha { font-size: 0.7rem; color: #94a3b8; margin-top: 4px; }
        .notif-eliminar {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 2px 6px;
            cursor: pointer;
            border-radius: 4px;
            flex-shrink: 0;
            transition: all 0.15s;
            line-height: 1;
        }
        .notif-eliminar:hover { background: #fee2e2; color: #ef4444; }
        .notif-vacio { padding: 30px; text-align: center; color: #94a3b8; }
        .notif-vacio i { font-size: 2rem; display: block; margin-bottom: 10px; }

        /* ===== FILTROS DE ESTADO - DISTRIBUIDOS EN TODO EL ANCHO ===== */
        .filtros-estado {
            background-color: white;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-left: 3px solid var(--jb-cyan);
            border-right: 3px solid var(--jb-cyan);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
        }
        .filtros-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .estado-filtro-item {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            flex: 0 1 auto;
            min-width: 130px;
            max-width: 200px;
        }
        .estado-filtro-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .estado-filtro-item label {
            font-size: 0.78rem;
            margin-bottom: 0;
            cursor: pointer;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.15s;
            border: 1px solid transparent;
            font-weight: 500;
            white-space: nowrap;
            width: 100%;
            text-align: center;
        }
        .estado-filtro-item input:checked + label {
            font-weight: 700;
            box-shadow: 0 0 0 2px rgba(0,168,232,0.4), 0 2px 4px rgba(0,0,0,0.1);
            transform: scale(1.03);
        }
        .estado-filtro-item label:hover {
            filter: brightness(0.95);
            transform: translateY(-1px);
        }

        /* Colores por estado (dinámico) */
        <?php
        $estado_hex = [
            'INGRESADO' => ['bg'=>'#6c757d', 'fg'=>'#6c757d'],
            'EN REVISION' => ['bg'=>'#0dcaf0', 'fg'=>'#055160'],
            'EN ESPERA' => ['bg'=>'#ffc107', 'fg'=>'#664d03'],
            'APROBADO' => ['bg'=>'#20c997', 'fg'=>'#0a3622'],
            'PRESUPUESTO RECHAZADO' => ['bg'=>'#dc3545', 'fg'=>'#842029'],
            'REPARADO' => ['bg'=>'#198754', 'fg'=>'#0f5132'],
            'SIN REPARACION' => ['bg'=>'#212529', 'fg'=>'#212529'],
            'ENTREGADO' => ['bg'=>'#0d6efd', 'fg'=>'#084298'],
        ];
        $palette = ['#6c757d','#0dcaf0','#ffc107','#20c997','#dc3545','#212529','#0d6efd','#e83e8c','#fd7e14','#6f42c1','#17a2b8','#28a745'];
        foreach ($todos_estados as $est):
            $cls = 'est-' . str_replace(' ', '-', $est);
            if (isset($estado_hex[$est])) {
                $hex = $estado_hex[$est]['bg'];
                $fg = $estado_hex[$est]['fg'];
            } else {
                $idx = abs(crc32($est)) % count($palette);
                $hex = $palette[$idx];
                $fg = '#1e293b';
            }
            echo ".$cls label { background-color: {$hex}20; color: $fg; border-color: {$hex}40; }\n";
            echo ".$cls input:checked + label { background-color: $hex; color: white; }\n";
        endforeach;
        ?>

        /* ===== BOTONES ESTADO TÉCNICO ===== */
        .estado-botones {
            display: flex;
            gap: 4px;
            justify-content: center;
            flex-wrap: nowrap;
        }
        .btn-estado-tecnico {
            padding: 5px 10px;
            font-size: 0.72rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            white-space: nowrap;
        }
        .btn-estado-tecnico:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
        .btn-estado-tecnico:active { transform: scale(0.95); }
        .btn-estado-tecnico.inactivo { opacity: 0.35; filter: grayscale(0.6); }
        .btn-estado-tecnico.inactivo:hover { opacity: 0.6; filter: grayscale(0.3); }
        .btn-estado-revisado { background: linear-gradient(135deg, #0dcaf0, #0891b2); color: white; }
        .btn-estado-esperando { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-estado-aprobado { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; }
        .btn-estado-reparado { background: linear-gradient(135deg, #198754, #166534); color: white; }
        .btn-estado-sinreparacion { background: linear-gradient(135deg, #495057, #212529); color: white; }

        /* Dropdown estado recepción */
        .estado-dropdown { position: relative; }
        .estado-dropdown .badge {
            cursor: pointer;
            padding: 6px 10px;
            font-size: 0.75rem;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .estado-dropdown .badge:hover { opacity: 0.9; transform: translateY(-1px); }
        .estado-dropdown .dropdown-menu {
            min-width: 220px;
            padding: 4px;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: absolute;
            z-index: 1060;
            max-height: 300px;
            overflow-y: auto;
        }
        .estado-dropdown .dropdown-item {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 4px;
            margin-bottom: 2px;
            cursor: pointer;
            white-space: nowrap;
        }
        .estado-dropdown .dropdown-item:hover { background-color: #f0f4f8; }
        .estado-dropdown .dropdown-item.active { background-color: #e9ecef; font-weight: bold; }

        /* Botón ENTREGAR */
        .btn-entregar {
            padding: 5px 10px;
            font-size: 0.75rem;
            border-radius: 6px;
            border: none;
            background: linear-gradient(135deg, var(--jb-azul), var(--jb-azul-oscuro));
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 70px;
            font-weight: 600;
        }
        .btn-entregar:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,119,182,0.4); }
        .btn-entregar:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        /* WhatsApp button */
        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366, #128c7e);
            border: none;
            color: white;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-whatsapp:hover { background: linear-gradient(135deg, #128c7e, #0d6b5c); }

        /* ===== EXPRESS 24HS ===== */
        .fila-express {
            background: linear-gradient(90deg, rgba(220,53,69,0.08) 0%, rgba(220,53,69,0.03) 100%) !important;
            border-left: 3px solid #dc3545 !important;
        }
        .express-badge-inline {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: expressPulse 2s ease infinite;
        }
        @keyframes expressPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Sticky layout */
        .sticky-header {
            background-color: #f0f4f8;
            padding-bottom: 8px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .table-scroll-wrapper {
            border-radius: 8px;
            background: white;
        }
        .table-scroll-wrapper::-webkit-scrollbar { width: 8px; }
        .table-scroll-wrapper::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 4px; }
        .table-scroll-wrapper::-webkit-scrollbar-thumb { background: var(--jb-azul); border-radius: 4px; }

        /* TABLA - HOVER UNIFICADO */
        .table {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-radius: 8px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin-bottom: 0;
        }
        .table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #001845, #023e8a) !important;
        }
        .table thead th {
            color: #ffffff !important;
            font-weight: 600;
            border: none !important;
            padding: 10px 8px;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            background: transparent !important;
        }
        .table tbody tr:nth-child(even) { background-color: #f1f5f9; }
        .table tbody tr:hover,
        .table tbody tr:nth-child(even):hover { background-color: #dbeafe !important; }
        .table tbody td {
            border: none;
            padding: 8px;
            vertical-align: middle;
            color: #1e293b;
        }
        .col-clickable { cursor: pointer; }

        /* Botón chat */
        .btn-chat {
            background: linear-gradient(135deg, #495057, #212529);
            border: none;
            color: white;
        }
        .btn-chat:hover { background: linear-gradient(135deg, #212529, #000); color: white; }
        .acciones-btns {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .acciones-btns .btn { padding: 4px 8px; font-size: 12px; }

        /* Toast */
        .toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 1060; }

        /* ===== BOTÓN NUEVA ORDEN (mismo estilo que Guardar en nueva_orden.php) ===== */
        .btn-nueva-orden-nav {
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            font-size: 1.2rem;
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-nueva-orden-nav:hover {
            background-color: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }
        .btn-nueva-orden-nav i { font-size: 1.2rem; }

        /* ===== HAMBURGUESA ===== */
        .btn-hamburger {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.9);
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.4rem;
            flex-shrink: 0;
            margin-right: 4px;
        }
        .btn-hamburger:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,168,232,0.3);
        }

        /* ===== SIDEBAR ===== */
        .sidebar-jb {
            background: linear-gradient(180deg, var(--jb-navy) 0%, var(--jb-azul-oscuro) 100%) !important;
            color: white;
            border-right: 2px solid var(--jb-cyan);
        }
        .sidebar-jb .offcanvas-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-jb .offcanvas-title { color: var(--jb-cyan); font-weight: 700; }
        .sidebar-jb .btn-close {
            filter: brightness(0) invert(1);
        }
        .sidebar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.95rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .sidebar-menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(4px);
        }
        .sidebar-menu-item i {
            font-size: 1.3rem;
            width: 24px;
            text-align: center;
            color: var(--jb-cyan);
        }
        .sidebar-menu-item .badge-sidebar {
            margin-left: auto;
            background: var(--jb-cyan);
            color: var(--jb-navy);
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 700;
        }
        .sidebar-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 8px 0;
        }
        body.dark-mode .sidebar-jb {
            background: linear-gradient(180deg, #0a1628 0%, #0f1f3d 100%) !important;
        }

        /* ===== MODO NOCTURNO ===== */

        body.dark-mode {
            background-color: #0f1729 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .card { background-color: #1a2235 !important; border-left-color: var(--jb-cyan) !important; color: #e2e8f0; }
        body.dark-mode .card-header { background-color: #1a2235 !important; border-bottom-color: #2d3748 !important; color: #e2e8f0; }
        body.dark-mode .filtros-estado { background-color: #1a2235 !important; border-left-color: var(--jb-cyan) !important; border-right-color: var(--jb-cyan) !important; }
        body.dark-mode .dropdown-notificaciones { background-color: #1a2235 !important; border: 1px solid #2d3748; }
        body.dark-mode .notif-header { color: #e2e8f0; border-bottom-color: #2d3748; }
        body.dark-mode .notif-item { border-bottom-color: #2d3748; }
        body.dark-mode .notif-item:hover { background: #243047; }
        body.dark-mode .notif-item.no-leida { background: #1e3a5f; }
        body.dark-mode .notif-titulo { color: #e2e8f0; }
        body.dark-mode .notif-mensaje { color: #94a3b8; }
        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #1a2235 !important; border-color: #2d3748 !important; color: #e2e8f0 !important; }
        body.dark-mode .form-control::placeholder { color: #64748b; }
        body.dark-mode .table { background-color: #0f1729 !important; color: #e2e8f0 !important; }
        body.dark-mode .table thead { background: linear-gradient(135deg, #0d1b3e, #1a2744) !important; }
        body.dark-mode .table tbody tr { background-color: #1a2235 !important; color: #e2e8f0 !important; }
        body.dark-mode .table tbody tr:nth-child(even) { background-color: #162032 !important; }
        body.dark-mode .table tbody tr:hover, body.dark-mode .table tbody tr:nth-child(even):hover { background-color: #1e3a5f !important; }
        body.dark-mode .table tbody td { background-color: transparent !important; color: #e2e8f0 !important; }
        body.dark-mode .fila-express { background: linear-gradient(90deg, rgba(239,68,68,0.15) 0%, rgba(239,68,68,0.05) 100%) !important; }
        body.dark-mode h1 { color: #e2e8f0 !important; }
        body.dark-mode .nav-jb .nav-brand { color: white !important; }
        body.dark-mode .nav-jb .nav-btn { color: rgba(255,255,255,0.9) !important; }
        body.dark-mode .nav-jb .nav-btn.active { color: var(--jb-navy) !important; }
        body.dark-mode .nav-jb .nav-sep { color: rgba(255,255,255,0.3) !important; }
        body.dark-mode .toast { background-color: #1a2235 !important; border: 1px solid #2d3748 !important; }
        body.dark-mode .toast-body { color: #e2e8f0 !important; }
        body.dark-mode .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
        body.dark-mode .table-scroll-wrapper { background-color: #0f1729 !important; }
        body.dark-mode .sticky-header { background-color: #0f1729 !important; }
        body.dark-mode .btn-chat { background: linear-gradient(135deg, #4b5563, #1f2937); color: white; }
        body.dark-mode .btn-whatsapp { background: linear-gradient(135deg, #22c55e, #128c7e); color: white; }
        body.dark-mode .btn-dark { background: linear-gradient(135deg, #4b5563, #1f2937) !important; color: white !important; border: none; }
        body.dark-mode .btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8) !important; color: white !important; border: none; }
        body.dark-mode .btn-info { background: linear-gradient(135deg, #0891b2, #0e7490) !important; color: white !important; border: none; }
        body.dark-mode .btn-estado-revisado { background: linear-gradient(135deg, #0891b2, #0e7490) !important; }
        body.dark-mode .btn-estado-esperando { background: linear-gradient(135deg, #d97706, #b45309) !important; }
        body.dark-mode .btn-estado-aprobado { background: linear-gradient(135deg, #0284c7, #0369a1) !important; }
        body.dark-mode .btn-estado-reparado { background: linear-gradient(135deg, #16a34a, #15803d) !important; }
        body.dark-mode .btn-estado-sinreparacion { background: linear-gradient(135deg, #4b5563, #374151) !important; }
        /* Dark mode estados (dinámico) */
        <?php
        $dm_dark = ['#374151','#0e4a5a','#5a4a0e','#0e4a2e','#5a1a1a','#1f2937','#0e2a5a','#3b1a5a','#5a3a0e','#3a1a5a','#0e3a5a','#0a4a3a'];
        $dm_color = ['#9ca3af','#67e8f9','#fde047','#6ee7b7','#fca5a5','#d1d5db','#93c5fd','#d8b4fe','#fdba74','#c4b5fd','#67e8f9','#86efac'];
        $dm_checked_bg = ['#6b7280','#06b6d4','#eab308','#10b981','#ef4444','#4b5563','#3b82f6','#a855f7','#f97316','#8b5cf6','#06b6d4','#22c55e'];
        foreach ($todos_estados as $i=>$est):
            $cls = 'est-' . str_replace(' ', '-', $est);
            $di = $i % count($dm_dark);
            echo "body.dark-mode .$cls label { background-color: {$dm_dark[$di]}; color: {$dm_color[$di]}; border-color: {$dm_dark[$di]}; }\n";
            echo "body.dark-mode .$cls input:checked + label { background-color: {$dm_checked_bg[$di]}; color: white; }\n";
        endforeach;
        ?>

        /* ===== MOBILE CARDS ===== */
        .mobile-card {
            background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 10px; border-left: 3px solid var(--jb-cyan); border-right: 3px solid var(--jb-cyan);
        }
        .mobile-card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        }
        .mobile-card-header .orden-num { font-weight: 700; font-size: 1rem; color: var(--jb-navy); }
        .mobile-card-header .orden-num .express-badge-inline { font-size: 0.6rem; padding: 1px 5px; }
        .mobile-card-body { padding: 8px 12px; }
        .mobile-card-body .info-row {
            display: flex;
            align-items: flex-start;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .mobile-card-body .info-row:last-child { border-bottom: none; }
        .mobile-card-body .info-row .label { color: #64748b; font-weight: 500; white-space: nowrap; }
        .mobile-card-body .info-row .value { color: #1e293b; font-weight: 600; margin-left: 4px; }
        .mobile-card-actions {
            display: flex; gap: 4px; padding: 6px 10px; border-top: 1px solid #e2e8f0;
            flex-wrap: nowrap; justify-content: center;
        }
        .mobile-card-actions .btn { font-size: 0.7rem !important; padding: 4px 7px !important; border-radius: 5px !important; }
        body.dark-mode .mobile-card { background: #1a2235; border-left-color: #38bdf8; border-right-color: #38bdf8; }
        body.dark-mode .mobile-card-header { background: #0f1729; border-bottom-color: #2d3748; }
        body.dark-mode .mobile-card-header .orden-num { color: #e2e8f0; }
        body.dark-mode .mobile-card-body { color: #cbd5e1; }
        body.dark-mode .mobile-card-body .info-row { border-bottom-color: #1e293b; }
        body.dark-mode .mobile-card-body .info-row .label { color: #94a3b8; }
        body.dark-mode .mobile-card-body .info-row .value { color: #e2e8f0; }
        body.dark-mode .mobile-card-actions { border-top-color: #2d3748; }

        @media (max-width: 767px) {
            .filtros-container { gap: 4px; }
            .estado-filtro-item { min-width: auto; flex: 1 1 auto; }
            .estado-filtro-item label { font-size: 0.68rem; padding: 4px 8px; white-space: nowrap; }
            .nav-center { position: absolute; left: 50%; transform: translateX(-50%); }
            .nav-left, .nav-right { min-width: 40px; }
            .nav-right .notificaciones-container {
                position: absolute;
                top: 100%;
                right: 0.25rem;
                z-index: 1055;
            }
            .nav-right .notificaciones-container .btn-notificaciones {
                background: var(--jb-cyan);
                border-color: var(--jb-cyan);
                color: white;
                box-shadow: 0 2px 8px rgba(0,56,168,0.4);
            }
        }
        .nav-logo { height:53px; width:auto; }
        @media (max-width:767.98px) { .nav-logo { height:42px; } }
    </style>
<script>if('serviceWorker'in navigator){navigator.serviceWorker.register('../sw.js').catch(function(){})}</script>
</head>
<body>

<!-- Toast -->
<div class="toast-container">
    <div id="toastNotificacion" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMensaje">Estado actualizado correctamente</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start sidebar-jb" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">
            <?php if ($ES_ADMIN): ?>
            <i class="bi bi-shield-lock me-2"></i> Admin
            <?php elseif ($ES_FULL): ?>
            <i class="bi bi-person-check me-2"></i> Full
            <?php elseif ($ES_TECNICO): ?>
            <i class="bi bi-tools me-2"></i> Técnico
            <?php else: ?>
            <i class="bi bi-headset me-2"></i> Recepción
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3 d-flex flex-column">
        <!-- Main menu items -->
        <div>
            <!-- Home -->
            <a href="listado.php" class="sidebar-menu-item">
                <i class="bi bi-house-door"></i>
                <span>Home</span>
            </a>

            <hr class="sidebar-divider">

            <!-- Repuestos -->
            <a href="repuestos.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Repuestos</span>
            </a>

            <hr class="sidebar-divider">

            <!-- Clientes -->
            <a href="clientes.php" class="sidebar-menu-item">
                <i class="bi bi-people"></i>
                <span>Clientes</span>
            </a>

            <hr class="sidebar-divider">

            <!-- Movimientos -->
            <a href="movimientos.php" class="sidebar-menu-item">
                <i class="bi bi-activity"></i>
                <span>Movimientos</span>
            </a>

            <?php if ($ES_ADMIN): ?>
            <hr class="sidebar-divider">

            <!-- Gestión de Usuarios -->
            <a href="gestion_usuarios.php" class="sidebar-menu-item">
                <i class="bi bi-shield-lock"></i>
                <span>Gestión de Usuarios</span>
            </a>
            <?php endif; ?>
            <hr class="sidebar-divider">
            <a href="configuracion.php" class="sidebar-menu-item">
                <i class="bi bi-gear"></i>
                <span>Configuración</span>
            </a>
            <?php if ($ES_ADMIN): ?>
            <a href="../tienda/admin.php" class="sidebar-menu-item">
                <i class="bi bi-shop"></i>
                <span>Tienda</span>
            </a>
            <hr class="sidebar-divider">
            <a href="?cambiar_vista=recepcion" class="sidebar-menu-item <?php echo $VISTA_ADMIN === 'recepcion' ? 'active' : ''; ?>">
                <i class="bi bi-headset"></i>
                <span>Vista Recepción</span>
                <?php if ($VISTA_ADMIN === 'recepcion'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
            </a>
            <a href="?cambiar_vista=tecnico" class="sidebar-menu-item <?php echo $VISTA_ADMIN === 'tecnico' ? 'active' : ''; ?>">
                <i class="bi bi-tools"></i>
                <span>Vista Técnico</span>
                <?php if ($VISTA_ADMIN === 'tecnico'): ?><i class="bi bi-check2 ms-auto"></i><?php endif; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Spacer to push dark mode to bottom -->
        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <!-- Modo Nocturno -->
        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>

        <hr class="sidebar-divider">

        <!-- Cerrar Sesión -->
        <a href="../../logout.php" class="sidebar-menu-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- Navbar -->
<nav class="nav-jb" style="padding:0;">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0 0 0 0.25rem; position: relative;">
        <!-- IZQUIERDA: hamburguesa + Nueva Orden -->
        <div class="nav-left" style="display: flex; align-items: center; gap: 10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <?php if ($ES_RECEPCION): ?>
            <a href="nueva_orden.php" class="btn-nueva-orden-nav"><i class="bi bi-plus-circle-fill"></i> <span class="d-none d-md-inline">Nueva Orden</span></a>
            <?php endif; ?>
        </div>

        <!-- CENTRO: logo + texto -->
        <div class="nav-center" style="display: flex; align-items: center; justify-content: center; flex:1; text-align:center;">
            <span style="display:inline-flex; align-items:center; gap:10px; color:white;">
                <img src="logo.png" alt="FullTaller" class="nav-logo">
                <span class="d-none d-sm-inline" style="font-size:0.95rem; font-weight:500;">Sistema de gestion de ordenes</span>
            </span>
        </div>

        <!-- DERECHA: notificaciones + rol + Salir -->
        <div class="nav-right" style="display: flex; align-items: center; gap: 2px;">
            <div class="notificaciones-container">
                <button class="btn-notificaciones" id="btnNotificaciones" onclick="toggleNotificaciones()">
                    <i class="bi bi-bell-fill"></i>
                    <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                </button>
                <div class="dropdown-notificaciones" id="dropdownNotificaciones">
                    <div class="notif-header">
                        <span><i class="bi bi-bell-fill me-1"></i> Notificaciones</span>
                        <span style="font-size: 0.75rem; color: #94a3b8;" id="notifCount"></span>
                    </div>
                    <div class="notif-lista" id="notifLista">
                        <div class="notif-vacio">
                            <i class="bi bi-bell-slash"></i>
                            No hay notificaciones
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; align-items: stretch; gap: 2px;" class="rol-salir-wrapper">
                <span class="rol-badge" style="padding:2px 10px; font-size:0.8rem; text-align:center; width:100%; display:block;">
                    <?php if ($ES_ADMIN): ?>
                    <i class="bi bi-shield-lock"></i>
                    <?php else: ?>
                    <i class="bi bi-<?php echo $ES_FULL ? 'person-check' : ($ES_TECNICO ? 'tools' : 'headset'); ?>"></i>
                    <?php endif; ?>
                    <span style="font-weight:400;font-size:0.7rem;"><?php echo htmlspecialchars($NOMBRE_USUARIO); ?></span>
                </span>
            </div>
        </div>
    </div>
</nav>

<!-- CONTENIDO SIN MÁRGENES LATERALES -->
<div style="padding: 0 0.25rem; margin-bottom: 2rem;">
    <div class="sticky-header">
        <div class="mb-2">
            <h1 style="color: var(--jb-navy); margin: 0; font-size: 1.5rem;">Órdenes de Reparación</h1>
        </div>

        <!-- FILTROS DE ESTADO - DISTRIBUIDOS EN TODO EL ANCHO -->
        <form method="GET" id="formEstados">
            <div class="filtros-estado">
                <div class="filtros-container">
                    <?php 
                    foreach ($todos_estados as $estado): 
                        $checked = in_array($estado, $estados_seleccionados) ? 'checked' : '';
                        $class_estado = 'est-' . str_replace(' ', '-', $estado);
                    ?>
                    <div class="estado-filtro-item <?php echo $class_estado; ?>">
                        <input type="checkbox" name="estados[]" value="<?php echo htmlspecialchars($estado); ?>" id="est_<?php echo $class_estado; ?>" <?php echo $checked; ?> onchange="document.getElementById('formEstados').submit();">
                        <label for="est_<?php echo $class_estado; ?>"><?php echo htmlspecialchars($estado); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($filtro) && !empty($busqueda) && $filtro != 'estado'): ?>
                <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
                <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>">
            <?php endif; ?>
            <?php if ($filtro == 'cliente' && !empty($busqueda)): ?>
                <input type="hidden" name="filtro" value="cliente">
                <input type="hidden" name="buscar_cliente" value="<?php echo htmlspecialchars($busqueda); ?>">
            <?php endif; ?>
            <input type="hidden" name="pagina" value="1">
        </form>
    </div>

    <!-- BÚSQUEDA -->
    <form method="GET" class="mb-2">
        <div class="row g-2">
            <div class="col-md-2">
                <select name="filtro" id="filtro" class="form-select form-select-sm" onchange="cambiarFiltro()">
                    <option value="id" <?php if($filtro == 'id') echo 'selected'; ?>>N° Orden</option>
                    <option value="cliente" <?php if($filtro == 'cliente') echo 'selected'; ?>>Cliente</option>
                    <option value="modelo" <?php if($filtro == 'modelo') echo 'selected'; ?>>Modelo</option>
                    <option value="imei" <?php if($filtro == 'imei') echo 'selected'; ?>>IMEI</option>
                </select>
            </div>
            <div class="col-md-8">
                <input type="text" name="buscar" id="inputBuscar" class="form-control form-control-sm <?php echo ($filtro == 'cliente') ? 'd-none' : ''; ?>" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar...">
                <select name="buscar_cliente" id="selectCliente" class="form-select form-select-sm <?php echo ($filtro == 'cliente') ? '' : 'd-none'; ?>">
                    <option value="">Seleccionar cliente</option>
                    <?php $clientesLista->data_seek(0); while($cliente = $clientesLista->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars($cliente['nombre']); ?>" <?php if($busqueda == $cliente['nombre']) echo 'selected'; ?>><?php echo htmlspecialchars($cliente['nombre']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark btn-sm w-100"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </div>
        <?php foreach ($estados_seleccionados as $estado): ?>
            <input type="hidden" name="estados[]" value="<?php echo htmlspecialchars($estado); ?>">
        <?php endforeach; ?>
        <input type="hidden" name="pagina" value="1">
    </form>

    <div class="table-scroll-wrapper">
    <table class="table table-hover table-sm d-none d-md-table">
        <thead>
            <tr>
                <?php if ($ES_TECNICO && !$ES_FULL): ?>
                    <th style="width: 55px; text-align:center;">Orden</th>
                    <th style="width: 255px;">Equipo</th>
                    <th style="width: auto;">Falla</th>
                    <th style="width: 230px; text-align:center;">Estado</th>
                <?php else: ?>
                    <th style="width: 50px; text-align:center;">Orden</th>
                    <th style="width: 110px;">Cliente</th>
                    <th style="width: 225px;">Equipo</th>
                    <th style="width: 130px; text-align:center;">Estado</th>
                    <th style="width: 140px; text-align:center;">Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php while($orden = $resultado->fetch_assoc()) { 
            $estado_actual = $orden['estado'];
            $badge_class = badgeClass($estado_actual);
            $ya_entregado = ($estado_actual == 'ENTREGADO');
            $es_express = ($orden['express'] == 1);
            $fila_clase = $es_express ? 'fila-express' : '';
        ?>
            <tr data-id="<?php echo $orden['id']; ?>" class="<?php echo $fila_clase; ?>">
                <?php if ($ES_TECNICO && !$ES_FULL): ?>
                    <td style="text-align:center; font-weight:600;" class="col-clickable" onclick="irADetalle(<?php echo $orden['id']; ?>)">
                        <?php echo htmlspecialchars($orden['id']); ?>
                        <?php if ($es_express): ?>
                            <span class="express-badge-inline" title="Express 24hs"><i class="bi bi-lightning-charge-fill"></i>EXP</span>
                        <?php endif; ?>
                    </td>
                    <td style="max-width: 255px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="col-clickable" onclick="irADetalle(<?php echo $orden['id']; ?>)"><?php echo htmlspecialchars($orden["marca"] . " - " . $orden["modelo"]); ?></td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="col-clickable" onclick="irADetalle(<?php echo $orden['id']; ?>)"><?php echo htmlspecialchars($orden["falla"]); ?></td>
                    <td style="text-align:center;">
                        <?php if ($ya_entregado): ?>
                            <span class="badge bg-primary">ENTREGADO</span>
                        <?php else: ?>
                            <div class="estado-botones">
                                <button class="btn-estado-tecnico btn-estado-revisado <?php echo ($estado_actual != 'EN REVISION') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'EN REVISION')" title="En Revisión"><i class="bi bi-eye"></i> En Revisión</button>
                                <button class="btn-estado-tecnico btn-estado-esperando <?php echo ($estado_actual != 'EN ESPERA') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'EN ESPERA')" title="En Espera"><i class="bi bi-hourglass-split"></i> En Espera</button>
                                <button class="btn-estado-tecnico btn-estado-aprobado <?php echo ($estado_actual != 'APROBADO') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'APROBADO')" title="Aprobado"><i class="bi bi-check2-circle"></i> Aprobado</button>
                                <button class="btn-estado-tecnico btn-estado-reparado <?php echo ($estado_actual != 'REPARADO') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'REPARADO')" title="Reparado"><i class="bi bi-check-circle"></i> Reparado</button>
                                <button class="btn-estado-tecnico btn-estado-sinreparacion <?php echo ($estado_actual != 'SIN REPARACION') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'SIN REPARACION')" title="Sin Reparación"><i class="bi bi-x-circle"></i> Sin Reparación</button>
                            </div>
                        <?php endif; ?>
                    </td>
                <?php else: ?>
                    <td style="text-align:center; font-weight:600;" class="col-clickable" onclick="irADetalle(<?php echo $orden['id']; ?>)">
                        <?php echo htmlspecialchars($orden['id']); ?>
                        <?php if ($es_express): ?>
                            <span class="express-badge-inline" title="Express 24hs"><i class="bi bi-lightning-charge-fill"></i>EXP</span>
                        <?php endif; ?>
                    </td>
                    <td style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="col-clickable" onclick="irADetalle(<?php echo $orden['id']; ?>)"><?php echo htmlspecialchars($orden["cliente_nombre"]); ?></td>
                    <td style="max-width: 225px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="col-clickable" onclick="irADetalle(<?php echo $orden['id']; ?>)"><?php echo htmlspecialchars($orden["marca"] . " - " . $orden["modelo"]); ?></td>
                    <td style="text-align:center;">
                        <?php if ($ya_entregado): ?>
                            <span class="badge bg-primary">ENTREGADO</span>
                        <?php else: ?>
                            <div class="dropdown estado-dropdown">
                                <span class="badge <?php echo $badge_class; ?> dropdown-toggle" data-bs-toggle="dropdown" id="estadoBadge<?php echo $orden['id']; ?>"><?php echo htmlspecialchars($estado_actual); ?></span>
                                <ul class="dropdown-menu" aria-labelledby="estadoBadge<?php echo $orden['id']; ?>">
                                    <?php foreach ($estados_cambiables as $estado_opcion): 
                                        $active = ($estado_opcion == $estado_actual) ? 'active' : '';
                                    ?>
                                    <li><a class="dropdown-item <?php echo $active; ?>" href="#" onclick="cambiarEstado(<?php echo $orden['id']; ?>, '<?php echo $estado_opcion; ?>'); return false;">
                                        <?php if ($estado_opcion == $estado_actual): ?><i class="bi bi-check-circle-fill me-1"></i><?php else: ?><i class="bi bi-circle me-1" style="opacity: 0.3;"></i><?php endif; ?>
                                        <?php echo htmlspecialchars($estado_opcion); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <div class="acciones-btns">
                            <div class="dropdown" style="display:inline-block;">
                                <button class="btn btn-sm" data-bs-toggle="dropdown" data-bs-boundary="window" title="Imprimir" style="background:#1e293b;color:white;padding:4px 8px;font-size:12px;border-radius:6px;border:none;"><i class="bi bi-printer"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($tipo_impresion_l === 'unificada'): ?>
                                    <li><a class="dropdown-item" href="imprimir_unificada.php?id=<?php echo $orden['id']; ?>&print=1" target="_blank"><i class="bi bi-printer"></i> Imprimir Orden</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="imprimir_taller.php?id=<?php echo $orden['id']; ?>" target="_blank"><i class="bi bi-file-earmark-text"></i> Imprimir Taller</a></li>
                                    <li><a class="dropdown-item" href="imprimir_cliente.php?id=<?php echo $orden['id']; ?>" target="_blank"><i class="bi bi-person"></i> Imprimir Cliente</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="imprimirAmbas(<?php echo $orden['id']; ?>); return false;"><i class="bi bi-files"></i> Imprimir Ambas</a></li>
                                <?php endif; ?>
                                </ul>
                            </div>
                            <a class="btn btn-whatsapp btn-sm" href="whatsapp://send?phone=54<?php echo $orden['telefono']; ?>&text=<?php echo urlencode('Hola nos comunicamos de ' . $taller_nombre_l . ' por la orden N° ' . $orden['id']); ?>" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            <?php if (!$ya_entregado): ?>
                            <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); entregarOrden(<?php echo $orden['id']; ?>, this)" title="Marcar como ENTREGADO" style="padding:4px 8px;font-size:12px;border-radius:6px;"><i class="bi bi-phone-flip"></i></button>
                            <?php else: ?>
                            <span class="badge bg-primary" style="font-size:0.7rem; padding:5px 8px;">Entregado</span>
                            <?php endif; ?>
                        </div>
                    </td>
                <?php endif; ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_paginas > 1): ?>
    <nav class="d-none d-md-flex justify-content-center mt-3">
        <ul class="pagination pagination-sm">
            <?php
            $query_params = $_GET;
            for ($i = 1; $i <= $total_paginas; $i++):
                $query_params['pagina'] = $i;
                $url = '?' . http_build_query($query_params);
                $active = $i === $pagina ? 'active' : '';
            ?>
            <li class="page-item <?php echo $active; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars($url); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <div class="d-md-none text-center small text-muted mb-2">Pág. <?php echo $pagina; ?> de <?php echo $total_paginas; ?> (<?php echo $total_ordenes; ?> órdenes)</div>
    <?php endif; ?>

    <!-- MOBILE CARDS -->
    <div class="d-md-none">
    <?php $resultado->data_seek(0); while($orden = $resultado->fetch_assoc()) {
        $estado_actual = $orden['estado'];
        $badge_class = badgeClass($estado_actual);
        $ya_entregado = ($estado_actual == 'ENTREGADO');
        $es_express = ($orden['express'] == 1);
    ?>
    <div class="mobile-card" onclick="irADetalle(<?php echo $orden['id']; ?>)" style="cursor:pointer;">
        <div class="mobile-card-header" onclick="event.stopPropagation();" style="display:flex;align-items:center;">
            <span class="orden-num" style="flex-shrink:0;">
                #<?php echo $orden['id']; ?>
                <?php if ($es_express): ?><span class="express-badge-inline" title="Express 24hs"><i class="bi bi-lightning-charge-fill"></i>EXP</span><?php endif; ?>
            </span>
            <div style="flex:1;text-align:center;padding:0 4px;">
                <?php if ($ES_TECNICO && !$ES_FULL): ?>
                    <?php if ($ya_entregado): ?>
                        <span class="badge bg-primary">ENTREGADO</span>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($ya_entregado): ?>
                        <span class="badge bg-primary">ENTREGADO</span>
                    <?php else: ?>
                        <div class="dropdown estado-dropdown">
                            <span class="badge <?php echo $badge_class; ?> dropdown-toggle" data-bs-toggle="dropdown" style="font-size:0.75rem;"><?php echo htmlspecialchars($estado_actual); ?></span>
                            <ul class="dropdown-menu">
                                <?php foreach ($estados_cambiables as $estado_opcion):
                                    $active = ($estado_opcion == $estado_actual) ? 'active' : '';
                                ?>
                                <li><a class="dropdown-item <?php echo $active; ?>" href="#" onclick="cambiarEstado(<?php echo $orden['id']; ?>, '<?php echo $estado_opcion; ?>'); return false;">
                                    <?php if ($estado_opcion == $estado_actual): ?><i class="bi bi-check-circle-fill me-1"></i><?php else: ?><i class="bi bi-circle me-1" style="opacity:0.3;"></i><?php endif; ?>
                                    <?php echo htmlspecialchars($estado_opcion); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <span style="display:flex;gap:2px;flex-shrink:0;">
                <?php if ($ES_TECNICO && !$ES_FULL && $ya_entregado): ?>
                <span class="badge bg-primary" style="font-size:0.65rem;">Entregado</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="mobile-card-body" style="<?php echo (($ES_TECNICO && !$ya_entregado) || $ES_RECEPCION) ? 'display:flex;' : ''; ?>">
            <?php if ($ES_TECNICO && !$ES_FULL): ?>
                <?php if ($ya_entregado): ?>
                    <div class="info-row"><span class="label">Equipo:</span><span class="value"><?php echo htmlspecialchars($orden["marca"] . " - " . $orden["modelo"]); ?></span></div>
                    <div class="info-row"><span class="label">Falla:</span><span class="value"><?php echo htmlspecialchars($orden["falla"]); ?></span></div>
                <?php else: ?>
                    <div style="flex:1;min-width:0;">
                        <div class="info-row"><span class="label">Equipo:</span><span class="value"><?php echo htmlspecialchars($orden["marca"] . " - " . $orden["modelo"]); ?></span></div>
                        <div class="info-row"><span class="label">Falla:</span><span class="value"><?php echo htmlspecialchars($orden["falla"]); ?></span></div>
                    </div>
                    <div class="estado-botones" style="flex-shrink:0;display:grid;grid-template-columns:1fr 1fr;gap:2px;padding-left:4px;">
                        <button class="btn-estado-tecnico btn-estado-revisado <?php echo ($estado_actual != 'EN REVISION') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'EN REVISION')" style="font-size:0.65rem;padding:8px 4px;border-radius:4px;">En Revisión</button>
                        <button class="btn-estado-tecnico btn-estado-esperando <?php echo ($estado_actual != 'EN ESPERA') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'EN ESPERA')" style="font-size:0.65rem;padding:8px 4px;border-radius:4px;">En Espera</button>
                        <button class="btn-estado-tecnico btn-estado-aprobado <?php echo ($estado_actual != 'APROBADO') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'APROBADO')" style="font-size:0.65rem;padding:8px 4px;border-radius:4px;">Aprobado</button>
                        <button class="btn-estado-tecnico btn-estado-reparado <?php echo ($estado_actual != 'REPARADO') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'REPARADO')" style="font-size:0.65rem;padding:8px 4px;border-radius:4px;">Reparado</button>
                        <button class="btn-estado-tecnico btn-estado-sinreparacion <?php echo ($estado_actual != 'SIN REPARACION') ? 'inactivo' : ''; ?>" onclick="cambiarEstado(<?php echo $orden['id']; ?>, 'SIN REPARACION')" style="font-size:0.65rem;padding:8px 4px;border-radius:4px;">Sin Rep.</button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($ya_entregado): ?>
                    <div class="info-row"><span class="label">Cliente:</span><span class="value"><?php echo htmlspecialchars($orden["cliente_nombre"]); ?></span></div>
                    <div class="info-row"><span class="label">Equipo:</span><span class="value"><?php echo htmlspecialchars($orden["marca"] . " - " . $orden["modelo"]); ?></span></div>
                <?php else: ?>
                    <div style="flex:1;min-width:0;">
                        <div class="info-row"><span class="label">Cliente:</span><span class="value"><?php echo htmlspecialchars($orden["cliente_nombre"]); ?></span></div>
                        <div class="info-row"><span class="label">Equipo:</span><span class="value"><?php echo htmlspecialchars($orden["marca"] . " - " . $orden["modelo"]); ?></span></div>
                    </div>
                    <div style="flex-shrink:0;display:grid;grid-template-columns:1fr;gap:2px;padding-left:4px;">
                        <div class="dropdown">
                            <button class="btn btn-sm" data-bs-toggle="dropdown" data-bs-boundary="window" style="background:#1e293b;color:white;font-size:0.65rem;padding:8px 4px;border-radius:4px;border:none;width:100%;"><i class="bi bi-printer"></i> Imprimir</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($tipo_impresion_l === 'unificada'): ?>
                                <li><a class="dropdown-item" href="imprimir_unificada.php?id=<?php echo $orden['id']; ?>&print=1" target="_blank"><i class="bi bi-printer"></i> Imprimir</a></li>
                                <?php else: ?>
                                <li><a class="dropdown-item" href="imprimir_taller.php?id=<?php echo $orden['id']; ?>" target="_blank"><i class="bi bi-file-earmark-text"></i> Taller</a></li>
                                <li><a class="dropdown-item" href="imprimir_cliente.php?id=<?php echo $orden['id']; ?>" target="_blank"><i class="bi bi-person"></i> Cliente</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="imprimirAmbas(<?php echo $orden['id']; ?>); return false;"><i class="bi bi-files"></i> Ambas</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <button class="btn btn-sm" onclick="event.stopPropagation(); entregarOrden(<?php echo $orden['id']; ?>, this)" style="background:var(--jb-azul);color:white;font-size:0.65rem;padding:8px 4px;border-radius:4px;border:none;width:100%;"><i class="bi bi-phone-flip"></i> Entregar</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function irADetalle(id) { window.location.href = 'detalle.php?id=' + id; }

// ===== NOTIFICACIONES - SISTEMA ROBUSTO CON ROL EXPLÍCITO =====
const ROL_USUARIO = '<?php echo $ROL_ACTUAL; ?>';
const TIPO_IMPRESION = '<?php echo $tipo_impresion_l; ?>';
let notificacionesAbiertas = false;
let ultimaCantidadNotifs = 0;
let pollingNotifInterval = null;

function toggleNotificaciones() {
    const dropdown = document.getElementById('dropdownNotificaciones');
    notificacionesAbiertas = !notificacionesAbiertas;
    if (notificacionesAbiertas) {
        dropdown.classList.add('show');
        cargarNotificaciones(true);
    } else {
        dropdown.classList.remove('show');
    }
}

document.addEventListener('click', function(e) {
    const container = document.querySelector('.notificaciones-container');
    if (container && !container.contains(e.target) && notificacionesAbiertas) {
        document.getElementById('dropdownNotificaciones').classList.remove('show');
        notificacionesAbiertas = false;
    }
});

function cargarNotificaciones(marcarTodas) {
    const formData = new FormData();
    formData.append('rol', ROL_USUARIO);
    if (marcarTodas) {
        formData.append('marcar_todas', '1');
    }

    fetch('obtener_notificaciones.php', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            actualizarBadge(data.total_no_leidas);
            renderizarNotificaciones(data.notificaciones);
        } else {
            console.warn('Notificaciones:', data.error || 'Error desconocido');
        }
    })
    .catch(err => {
        console.error('Error cargando notificaciones:', err);
    });
}

function actualizarBadge(total) {
    const badge = document.getElementById('notifBadge');
    const btn = document.getElementById('btnNotificaciones');
    if (total > 0) {
        badge.textContent = total > 99 ? '99+' : total;
        badge.style.display = 'flex';
        btn.classList.add('pulse');
        if (total > ultimaCantidadNotifs && ultimaCantidadNotifs >= 0) {
            mostrarToast('Tienes ' + total + ' notificación' + (total > 1 ? 'es' : '') + ' nueva' + (total > 1 ? 's' : ''), 'info');
        }
    } else {
        badge.style.display = 'none';
        btn.classList.remove('pulse');
    }
    ultimaCantidadNotifs = total;
}

function renderizarNotificaciones(notifs) {
    const lista = document.getElementById('notifLista');
    const count = document.getElementById('notifCount');
    if (!notifs || notifs.length === 0) {
        lista.innerHTML = '<div class="notif-vacio"><i class="bi bi-bell-slash"></i>No hay notificaciones</div>';
        count.textContent = '';
        return;
    }
    count.textContent = notifs.length + ' notificaciones';
    lista.innerHTML = notifs.map(n => `
        <div class="notif-item${n.leida ? '' : ' no-leida'}" onclick="window.location.href='detalle.php?id=${n.orden_id}'">
            <div class="notif-icon ${n.desde_rol}"><i class="bi bi-${n.desde_rol === 'recepcion' ? 'headset' : 'tools'}"></i></div>
            <div class="notif-content">
                <div class="notif-titulo">${escapeHtml(n.titulo)}</div>
                <div class="notif-mensaje">${escapeHtml(n.mensaje)}</div>
                <div class="notif-fecha"><i class="bi bi-clock"></i> ${escapeHtml(n.fecha)}</div>
            </div>
            <button class="notif-eliminar" onclick="event.stopPropagation(); eliminarNotificacion(${n.id})" title="Eliminar">&times;</button>
        </div>
    `).join('');
}

function eliminarNotificacion(id) {
    const fd = new FormData();
    fd.append('id', id);
    fetch('eliminar_notificacion.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones(false);
        }
    })
    .catch(err => console.error('Error eliminando notificación:', err));
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function iniciarPollingNotificaciones() {
    // Polling solo para actualizar badge, sin marcar como leídas
    const formData = new FormData();
    formData.append('rol', ROL_USUARIO);
    fetch('obtener_notificaciones.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) actualizarBadge(data.total_no_leidas);
    })
    .catch(() => {});

    pollingNotifInterval = setInterval(() => {
        const fd = new FormData();
        fd.append('rol', ROL_USUARIO);
        fetch('obtener_notificaciones.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) actualizarBadge(data.total_no_leidas);
        })
        .catch(() => {});
    }, 8000);
}

// Toast
const toastEl = document.getElementById('toastNotificacion');
let toast = null;
if (toastEl) {
    toast = new bootstrap.Toast(toastEl, { delay: 2500 });
}
function mostrarToast(mensaje, tipo = 'success') {
    const toastBody = document.getElementById('toastMensaje');
    if (!toastBody || !toast) return;
    toastBody.textContent = mensaje;
    toastEl.className = 'toast align-items-center text-white border-0 bg-' + tipo;
    toast.show();
}

// Cambiar estado
function cambiarEstado(id, nuevoEstado) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('estado', nuevoEstado);

    fetch('cambiar_estado.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast('Estado cambiado a: ' + nuevoEstado);
            setTimeout(() => location.reload(), 800);
        } else {
            mostrarToast('Error: ' + (data.error || 'No se pudo cambiar'), 'danger');
        }
    })
    .catch(error => {
        mostrarToast('Error de conexión', 'danger');
        console.error(error);
    });
}

// Entregar orden
function imprimirAmbas(id) {
    if (TIPO_IMPRESION === 'unificada') {
        window.open('imprimir_unificada.php?id=' + id + '&print=1');
    } else {
        window.open('imprimir_taller.php?id=' + id + '&ambas=1');
    }
}
function entregarOrden(id, btn) {
    if (!confirm('¿Confirmar entrega del equipo? Esta acción finaliza la orden.')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
    const formData = new FormData();
    formData.append('id', id);
    formData.append('estado', 'ENTREGADO');

    fetch('cambiar_estado.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast('¡Orden #' + id + ' marcada como ENTREGADA!', 'primary');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarToast('Error: ' + (data.error || 'No se pudo entregar'), 'danger');
            btn.disabled = false;
            btn.innerHTML = 'Entregar';
        }
    })
    .catch(error => {
        mostrarToast('Error de conexión', 'danger');
        btn.disabled = false;
        btn.innerHTML = 'Entregar';
        console.error(error);
    });
}

function cambiarFiltro() {
    let filtro = document.getElementById('filtro').value;
    let input = document.getElementById('inputBuscar');
    let cliente = document.getElementById('selectCliente');
    input.classList.add('d-none');
    cliente.classList.add('d-none');
    if (filtro == 'cliente') { cliente.classList.remove('d-none'); }
    else { input.classList.remove('d-none'); }
}

cambiarFiltro();

document.addEventListener('DOMContentLoaded', function() {
    iniciarPollingNotificaciones();
});
</script>

<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('jb_dark_mode', isDark ? '1' : '0');
    updateDarkModeIcon();
}
function updateDarkModeIcon() {
    const sidebarIcon = document.getElementById('sidebarIconDark');
    const sidebarText = document.getElementById('sidebarTextDark');
    if (document.body.classList.contains('dark-mode')) {
        if (sidebarIcon) sidebarIcon.className = 'bi bi-sun-fill';
        if (sidebarText) sidebarText.textContent = 'Modo Claro';
    } else {
        if (sidebarIcon) sidebarIcon.className = 'bi bi-moon-stars-fill';
        if (sidebarText) sidebarText.textContent = 'Modo Nocturno';
    }
}
if (localStorage.getItem('jb_dark_mode') === '1') {
    document.body.classList.add('dark-mode');
}
updateDarkModeIcon();


</script>

<?php require 'includes/api_token_script.php'; ?>
</body>
</html>