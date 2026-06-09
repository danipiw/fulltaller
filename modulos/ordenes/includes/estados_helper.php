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

function badgeClassEstados($estado) {
    $mapa = [
        'INGRESADO' => 'bg-secondary',
        'EN REVISION' => 'bg-info',
        'EN ESPERA' => 'bg-warning text-dark',
        'APROBADO' => 'bg-success',
        'PRESUPUESTO RECHAZADO' => 'bg-danger',
        'REPARADO' => 'bg-success',
        'SIN REPARACION' => 'bg-dark',
        'ENTREGADO' => 'bg-primary',
        'CHEQUEO FINAL' => 'bg-info',
    ];
    if (isset($mapa[$estado])) return $mapa[$estado];
    $colores = ['bg-secondary','bg-info','bg-warning text-dark','bg-success','bg-danger','bg-dark','bg-primary'];
    $idx = abs(crc32($estado)) % count($colores);
    return $colores[$idx];
}
