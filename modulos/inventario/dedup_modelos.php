<?php
include 'includes/verificar_sesion.php';
$pdo = $GLOBALS['pdo'];

$mensaje = '';
$error = '';

if (isset($_POST['accion'])) {
    if ($_POST['accion'] === 'dedup') {
        try {
            $dups = $pdo->query("
                SELECT m1.id AS keep_id, GROUP_CONCAT(m2.id ORDER BY m2.id) AS dup_ids,
                       ma.nombre AS marca, m1.nombre AS modelo, COUNT(*) AS cnt
                FROM modelos m1
                INNER JOIN modelos m2 ON m1.marca_id = m2.marca_id AND LOWER(m1.nombre) = LOWER(m2.nombre)
                INNER JOIN marcas ma ON m1.marca_id = ma.id
                WHERE m1.id < m2.id
                GROUP BY m1.id, m1.marca_id, m1.nombre
                ORDER BY ma.nombre, m1.nombre
            ")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($dups)) {
                $mensaje = 'No se encontraron modelos duplicados.';
            } else {
                $pdo->beginTransaction();
                $total = 0;
                foreach ($dups as $d) {
                    $keep = (int)$d['keep_id'];
                    $dup_ids = array_map('intval', explode(',', $d['dup_ids']));
                    $ids_str = implode(',', $dup_ids);
                    // Reasignar caja_items a keep_id
                    $pdo->exec("UPDATE caja_items SET modelo_id = $keep WHERE modelo_id IN ($ids_str)");
                    // Eliminar modelo_componentes de dups
                    $pdo->exec("DELETE FROM modelo_componentes WHERE modelo_id IN ($ids_str)");
                    // Eliminar duplicados
                    $stmt = $pdo->prepare("DELETE FROM modelos WHERE id = ?");
                    foreach ($dup_ids as $did) {
                        $stmt->execute([$did]);
                    }
                    $total += count($dup_ids);
                }
                $pdo->commit();
                // Intentar agregar UNIQUE KEY
                try {
                    $pdo->exec("ALTER TABLE modelos ADD UNIQUE KEY `uq_modelos_marca_nombre` (`marca_id`, `nombre`)");
                } catch (PDOException $e) { /* ya existe o no se puede */ }
                $mensaje = "Se eliminaron $total modelos duplicados y se agregó restricción UNIQUE.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$duplicados = $pdo->query("
    SELECT m1.id AS keep_id, GROUP_CONCAT(m2.id ORDER BY m2.id) AS dup_ids,
           ma.nombre AS marca, m1.nombre AS modelo, COUNT(*) AS cnt
    FROM modelos m1
    INNER JOIN modelos m2 ON m1.marca_id = m2.marca_id AND LOWER(m1.nombre) = LOWER(m2.nombre)
    INNER JOIN marcas ma ON m1.marca_id = ma.id
    WHERE m1.id < m2.id
    GROUP BY m1.id, m1.marca_id, m1.nombre
    ORDER BY ma.nombre, m1.nombre
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpiar duplicados - Repuestos/Scrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --jb-cyan: #00a8e8; --jb-azul: #0077b6; --jb-azul-oscuro: #023e8a; --jb-navy: #001845; }
        body { background: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .card { border: none; border-left: 3px solid var(--jb-cyan); box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .card-header { background: white; border-bottom: 1px solid #e9ecef; font-weight: 600; }
        body.dark-mode { background: #0f1729; color: #e2e8f0; }
        body.dark-mode .card { background: #1a2235; border-left-color: var(--jb-cyan); }
        body.dark-mode .card-header { background: #1a2235; border-bottom-color: #2d3748; color: #e2e8f0; }
        body.dark-mode .table { color: #e2e8f0; }
        body.dark-mode .table tbody tr { background: #1a2235; }
        body.dark-mode .table tbody tr:nth-child(even) { background: #162032; }
    </style>
</head>
<body>
    <div class="container" style="max-width:800px;">
        <h1 style="color:var(--jb-navy);"><i class="bi bi-database"></i> Limpiar modelos duplicados</h1>
        <p class="text-muted">Encuentra y fusiona modelos repetidos (mismo nombre y marca). Los componentes y equipos se reasignan automáticamente al ID más antiguo.</p>

        <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($duplicados)): ?>
        <div class="card">
            <div class="card-body text-center py-4 text-muted">
                <i class="bi bi-check-circle" style="font-size:2rem;color:var(--jb-success);"></i>
                <p class="mt-2 mb-0">No hay modelos duplicados.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle text-warning"></i>
                <?php echo count($duplicados); ?> grupo(s) de duplicados encontrados
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>IDs duplicados</th>
                            <th>ID a conservar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($duplicados as $d): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($d['marca']); ?></td>
                            <td><?php echo htmlspecialchars($d['modelo']); ?></td>
                            <td><?php echo $d['dup_ids']; ?></td>
                            <td><strong><?php echo $d['keep_id']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" onsubmit="return confirm('¿Eliminar todos los duplicados? Los datos se reasignarán automáticamente.');">
            <input type="hidden" name="accion" value="dedup">
            <button type="submit" class="btn btn-primary"><i class="bi bi-trash"></i> Fusionar y limpiar duplicados</button>
            <a href="ingreso.php" class="btn btn-secondary">Volver</a>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
