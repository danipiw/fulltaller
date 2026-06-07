<?php
require_once 'includes/verificar_sesion.php';

// Date filter
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$desde_esc = $conn->real_escape_string($desde);
$hasta_esc = $conn->real_escape_string($hasta);

// KPI: POS ventas del período
$r_ventas = $conn->query("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as cantidad FROM pos_ventas WHERE anulada=0 AND DATE(created_at) BETWEEN '$desde_esc' AND '$hasta_esc'");
$kpi_ventas = $r_ventas->fetch_assoc();

// KPI: Ventas por método de pago
$r_metodos = $conn->query("SELECT metodo_pago, COALESCE(SUM(total),0) as total, COUNT(*) as cantidad FROM pos_ventas WHERE anulada=0 AND DATE(created_at) BETWEEN '$desde_esc' AND '$hasta_esc' GROUP BY metodo_pago");
$metodos = [];
$total_metodos = 0;
while ($m = $r_metodos->fetch_assoc()) { $metodos[] = $m; $total_metodos += $m['total']; }

// KPI: Órdenes presupuestos y señas
$r_ordenes = $conn->query("SELECT COALESCE(SUM(presupuesto),0) as presupuesto, COALESCE(SUM(sena),0) as sena, COUNT(*) as cantidad FROM ordenes WHERE DATE(fecha_ingreso) BETWEEN '$desde_esc' AND '$hasta_esc'");
$kpi_ordenes = $r_ordenes->fetch_assoc();

// Chart: Ventas por día (últimos 30 días)
$r_ventas_dia = $conn->query("SELECT DATE(created_at) as fecha, COALESCE(SUM(total),0) as total FROM pos_ventas WHERE anulada=0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY fecha");
$ventas_dia_labels = []; $ventas_dia_data = [];
while ($d = $r_ventas_dia->fetch_assoc()) { $ventas_dia_labels[] = $d['fecha']; $ventas_dia_data[] = $d['total']; }

// Chart: Ventas por mes (últimos 12 meses)
$r_ventas_mes = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as mes, COALESCE(SUM(total),0) as total FROM pos_ventas WHERE anulada=0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY mes");
$ventas_mes_labels = []; $ventas_mes_data = [];
while ($d = $r_ventas_mes->fetch_assoc()) { $ventas_mes_labels[] = $d['mes']; $ventas_mes_data[] = $d['total']; }

// Chart: Presupuesto vs Seña por mes (últimos 6 meses)
$r_ps_mes = $conn->query("SELECT DATE_FORMAT(fecha_ingreso, '%Y-%m') as mes, COALESCE(SUM(presupuesto),0) as presupuesto, COALESCE(SUM(sena),0) as sena FROM ordenes WHERE fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(fecha_ingreso, '%Y-%m') ORDER BY mes");
$ps_mes_labels = []; $ps_presupuesto = []; $ps_sena = [];
while ($d = $r_ps_mes->fetch_assoc()) { $ps_mes_labels[] = $d['mes']; $ps_presupuesto[] = $d['presupuesto']; $ps_sena[] = $d['sena']; }

// Tabla: Últimas ventas
$r_ultimas_v = $conn->query("SELECT v.id, v.total, v.metodo_pago, v.items, v.created_at, u.nombre as cajero FROM pos_ventas v LEFT JOIN usuarios u ON v.usuario_id = u.id WHERE v.anulada=0 ORDER BY v.created_at DESC LIMIT 10");
$ultimas_ventas = $r_ultimas_v->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzas - <?php echo htmlspecialchars($TALLER_NOMBRE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f4f8; font-family: 'Segoe UI',system-ui,sans-serif; }
        .nav-jb { background: linear-gradient(135deg, var(--jb-navy), var(--jb-azul-oscuro), var(--jb-azul)); padding: 0.2rem 1.5rem; box-shadow: 0 2px 10px rgba(0,56,168,0.3); }
        .nav-jb .nav-btn { color: rgba(255,255,255,0.85); text-decoration: none; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
        .nav-jb .nav-btn:hover { background: rgba(255,255,255,0.15); color: white; }

        .kpi-card { border-radius: 14px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .kpi-card .card-body { padding: 1.25rem; }
        .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .kpi-number { font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
        .kpi-label { font-size: 0.85rem; color: #64748b; }
        .bg-cyan-soft { background: rgba(0,168,232,0.1); color: var(--jb-cyan); }
        .bg-green-soft { background: rgba(16,185,129,0.1); color: #10b981; }
        .bg-purple-soft { background: rgba(139,92,246,0.1); color: #8b5cf6; }
        .bg-orange-soft { background: rgba(245,158,11,0.1); color: #f59e0b; }

        .chart-card { border-radius: 14px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .chart-card .card-header { background: transparent; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; font-weight: 600; }

        .filter-bar { background: white; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 1rem 1.5rem; }

        body.dark-mode { background: #0f1729; color: #e2e8f0; }
        body.dark-mode .kpi-card { background: #1a2235 !important; color: #e2e8f0; }
        body.dark-mode .kpi-label { color: #94a3b8; }
        body.dark-mode .chart-card { background: #1a2235 !important; }
        body.dark-mode .chart-card .card-header { border-color: #2d3748; color: #e2e8f0; }
        body.dark-mode .filter-bar { background: #1a2235; }
        body.dark-mode .form-control, body.dark-mode .form-select { background: #0f1729; color: #e2e8f0; border-color: #2d3748; }
        body.dark-mode .table { color: #e2e8f0; }
        body.dark-mode .table thead th { border-color: #2d3748; }
        body.dark-mode .table tbody td { border-color: #1e293b; }

        .chart-container { position: relative; height: 250px; width: 100%; }

        @media (max-width: 768px) {
            .kpi-number { font-size: 1.25rem; }
            .chart-container { height: 200px; }
        }
    </style>
</head>
<body>

<nav class="nav-jb d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <a href="../dashboard.php" class="nav-btn"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span style="color:white; font-weight:600; font-size:0.95rem;"><i class="bi bi-cash-stack"></i> Finanzas</span>
    </div>
</nav>

<div class="container-fluid py-3 px-3 px-xl-4">

    <!-- Filtro fechas -->
    <form method="GET" class="filter-bar d-flex align-items-center gap-3 flex-wrap mb-3">
        <span class="fw-semibold" style="font-size:0.9rem;"><i class="bi bi-funnel"></i> Período</span>
        <div class="d-flex align-items-center gap-2">
            <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo $desde; ?>">
            <span>—</span>
            <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo $hasta; ?>">
        </div>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filtrar</button>
        <a href="?" class="btn btn-sm btn-outline-secondary">Hoy</a>
        <a href="?desde=<?php echo date('Y-m-01'); ?>&hasta=<?php echo date('Y-m-t'); ?>" class="btn btn-sm btn-outline-secondary">Este Mes</a>
    </form>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-cyan-soft"><i class="bi bi-currency-dollar"></i></div>
                        <div>
                            <div class="kpi-number">$<?php echo number_format($kpi_ventas['total'], 0, ',', '.'); ?></div>
                            <div class="kpi-label"><?php echo $kpi_ventas['cantidad']; ?> ventas <span class="text-muted">en el período</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-purple-soft"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <div class="kpi-number">$<?php echo number_format($kpi_ordenes['presupuesto'], 0, ',', '.'); ?></div>
                            <div class="kpi-label"><?php echo $kpi_ordenes['cantidad']; ?> órdenes presupuestadas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-green-soft"><i class="bi bi-cash"></i></div>
                        <div>
                            <div class="kpi-number">$<?php echo number_format($kpi_ordenes['sena'], 0, ',', '.'); ?></div>
                            <div class="kpi-label">Señas cobradas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-orange-soft"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <?php $pendiente = $kpi_ordenes['presupuesto'] - $kpi_ordenes['sena']; ?>
                            <div class="kpi-number">$<?php echo number_format($pendiente, 0, ',', '.'); ?></div>
                            <div class="kpi-label">Saldo pendiente de órdenes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos fila 1 -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card chart-card">
                <div class="card-header"><i class="bi bi-graph-up"></i> Ventas por día (últimos 30 días)</div>
                <div class="card-body"><div class="chart-container"><canvas id="chartVentasDia"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card chart-card">
                <div class="card-header"><i class="bi bi-pie-chart"></i> Métodos de pago</div>
                <div class="card-body"><div class="chart-container"><canvas id="chartMetodos"></canvas></div></div>
            </div>
        </div>
    </div>

    <!-- Gráficos fila 2 -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card chart-card">
                <div class="card-header"><i class="bi bi-bar-chart"></i> Presupuesto vs Seña por mes</div>
                <div class="card-body"><div class="chart-container"><canvas id="chartPS"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card">
                <div class="card-header"><i class="bi bi-bar-chart-fill"></i> Evolución mensual de ventas</div>
                <div class="card-body"><div class="chart-container"><canvas id="chartVentasMes"></canvas></div></div>
            </div>
        </div>
    </div>

    <!-- Tabla últimos movimientos -->
    <div class="card chart-card">
        <div class="card-header"><i class="bi bi-table"></i> Últimas ventas</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:0.9rem;">
                    <thead>
                        <tr><th>#</th><th>Fecha</th><th>Cajero</th><th>Items</th><th>Método</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimas_ventas)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Sin ventas en el período</td></tr>
                        <?php else: ?>
                        <?php foreach ($ultimas_ventas as $v): ?>
                        <tr>
                            <td><?php echo $v['id']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($v['cajero'] ?: '-'); ?></td>
                            <td><?php echo $v['items']; ?></td>
                            <td><span class="badge bg-<?php echo $v['metodo_pago']==='efectivo'?'success':($v['metodo_pago']==='transferencia'?'info':'warning'); ?>"><?php echo $v['metodo_pago']; ?></span></td>
                            <td class="text-end fw-semibold">$<?php echo number_format($v['total'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Colores JB
const colores = ['#00a8e8','#0077b6','#023e8a','#48cae4','#90e0ef','#10b981','#f59e0b','#ef4444'];

// Ventas por día
new Chart(document.getElementById('chartVentasDia'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($ventas_dia_labels); ?>,
        datasets: [{
            label: 'Ventas',
            data: <?php echo json_encode($ventas_dia_data); ?>,
            borderColor: '#00a8e8',
            backgroundColor: 'rgba(0,168,232,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-AR') } } }
    }
});

// Métodos de pago
<?php
$metodo_labels = []; $metodo_data = []; $metodo_colors = [];
foreach ($metodos as $m) {
    $metodo_labels[] = $m['metodo_pago'];
    $metodo_data[] = $m['total'];
}
?>
new Chart(document.getElementById('chartMetodos'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($metodo_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($metodo_data); ?>,
            backgroundColor: ['#00a8e8','#10b981','#f59e0b','#8b5cf6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
        }
    }
});

// Presupuesto vs Seña
new Chart(document.getElementById('chartPS'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($ps_mes_labels); ?>,
        datasets: [
            { label: 'Presupuesto', data: <?php echo json_encode($ps_presupuesto); ?>, backgroundColor: '#00a8e8', borderRadius: 4 },
            { label: 'Seña', data: <?php echo json_encode($ps_sena); ?>, backgroundColor: '#10b981', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-AR') } },
            x: { grid: { display: false } }
        }
    }
});

// Ventas por mes
new Chart(document.getElementById('chartVentasMes'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($ventas_mes_labels); ?>,
        datasets: [{
            label: 'Ventas',
            data: <?php echo json_encode($ventas_mes_data); ?>,
            backgroundColor: '#0077b6',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-AR') } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
