<?php
$ESTADOS_DEFAULT_RECEPCION = ['INGRESADO', 'EN ESPERA', 'APROBADO', 'PRESUPUESTO RECHAZADO'];
$ESTADOS_DEFAULT_TECNICO = ['EN REVISION', 'EN ESPERA', 'APROBADO', 'REPARADO', 'SIN REPARACION'];
$ESTADOS_DEFAULT_TODOS = ['INGRESADO', 'EN REVISION', 'EN ESPERA', 'APROBADO', 'PRESUPUESTO RECHAZADO', 'REPARADO', 'SIN REPARACION', 'ENTREGADO'];

function cargarEstadosConfig($conn) {
    static $cfg = [];
    if (!empty($cfg)) return $cfg;
    $r = $conn->query("SELECT clave, valor FROM configuracion WHERE clave IN ('estados_recepcion','estados_tecnico')");
    if ($r) {
        while ($f = $r->fetch_assoc()) {
            $cfg[$f['clave']] = $f['valor'];
        }
    }
    return $cfg;
}

function obtenerEstadosRecepcion($conn) {
    global $ESTADOS_DEFAULT_RECEPCION;
    $cfg = cargarEstadosConfig($conn);
    if (!empty($cfg['estados_recepcion'])) {
        $estados = array_map('trim', explode(',', $cfg['estados_recepcion']));
        return array_filter($estados);
    }
    return $ESTADOS_DEFAULT_RECEPCION;
}

function obtenerEstadosTecnico($conn) {
    global $ESTADOS_DEFAULT_TECNICO;
    $cfg = cargarEstadosConfig($conn);
    if (!empty($cfg['estados_tecnico'])) {
        $estados = array_map('trim', explode(',', $cfg['estados_tecnico']));
        return array_filter($estados);
    }
    return $ESTADOS_DEFAULT_TECNICO;
}

function obtenerTodosEstados($conn) {
    global $ESTADOS_DEFAULT_TODOS;
    $recepcion = obtenerEstadosRecepcion($conn);
    $tecnico = obtenerEstadosTecnico($conn);
    return array_values(array_unique(array_merge($recepcion, $tecnico, ['ENTREGADO'])));
}

function getEstadoColor($estado) {
    $mapa = [
        'INGRESADO' => ['bg' => '#6c757d', 'fg' => '#ffffff'],
        'EN REVISION' => ['bg' => '#0dcaf0', 'fg' => '#055160'],
        'EN ESPERA' => ['bg' => '#ffc107', 'fg' => '#664d03'],
        'APROBADO' => ['bg' => '#20c997', 'fg' => '#ffffff'],
        'PRESUPUESTO RECHAZADO' => ['bg' => '#dc3545', 'fg' => '#ffffff'],
        'REPARADO' => ['bg' => '#198754', 'fg' => '#ffffff'],
        'SIN REPARACION' => ['bg' => '#212529', 'fg' => '#ffffff'],
        'ENTREGADO' => ['bg' => '#0d6efd', 'fg' => '#ffffff'],
    ];
    if (isset($mapa[$estado])) return $mapa[$estado];
    $palette = [['bg'=>'#6c757d','fg'=>'#ffffff'],['bg'=>'#0dcaf0','fg'=>'#055160'],['bg'=>'#ffc107','fg'=>'#664d03'],['bg'=>'#20c997','fg'=>'#ffffff'],['bg'=>'#dc3545','fg'=>'#ffffff'],['bg'=>'#212529','fg'=>'#ffffff'],['bg'=>'#0d6efd','fg'=>'#ffffff']];
    $idx = abs(crc32($estado)) % count($palette);
    return $palette[$idx];
}

function badgeClassEstados($estado) {
    return 'est-badge-' . str_replace(' ', '-', $estado);
}
