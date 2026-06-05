<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol'], $_SESSION['user_modulos']) || strpos($_SESSION['user_modulos'], 'pos') === false || !esAdminPOS()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    
    if (isset($_POST['guardar'])) {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $rol = $_POST['rol'];
        $modulos = !empty($_POST['modulos']) ? implode(',', $_POST['modulos']) : 'ordenes';
        $password = $_POST['password'];
        
        if ($id) {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=?, modulos=?, password=? WHERE id=?');
                $stmt->bind_param('sssssi', $nombre, $usuario, $rol, $modulos, $hash, $id);
            } else {
                $stmt = $db->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=?, modulos=? WHERE id=?');
                $stmt->bind_param('ssssi', $nombre, $usuario, $rol, $modulos, $id);
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO usuarios (nombre, usuario, password, rol, modulos) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $nombre, $usuario, $hash, $rol, $modulos);
        }
        $stmt->execute();
        $stmt->close();
    }
    
    if (isset($_POST['eliminar']) && $_POST['id'] != $_SESSION['usuario_id']) {
        $stmt = $db->prepare('UPDATE usuarios SET activo=0 WHERE id=?');
        $stmt->bind_param('i', $_POST['id']);
        $stmt->execute();
        $stmt->close();
    }
    
    $db->close();
    header('Location: usuarios.php');
    exit;
}

$db = getDB();
$usuarios_lista = $db->query('SELECT id, nombre, usuario, rol, modulos, activo, created_at FROM usuarios WHERE activo=1 ORDER BY nombre');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - POS FullTaller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php require 'includes/sidebar.php'; ?>

<!-- ===== NAVBAR ===== -->
<nav class="nav-jb">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 0 0 0.25rem;">
        <div class="nav-left" style="display:flex;align-items:center;gap:10px;">
            <button class="btn-hamburger" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" title="Menú">
                <i class="bi bi-list"></i>
            </button>
            <a href="index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
                <img src="logo.png?v=<?php echo filemtime('logo.png'); ?>" alt="FullTaller" class="nav-logo" onerror="this.style.display='none'">
                <span style="color:white;font-size:0.95rem;font-weight:500;">Punto de venta</span>
            </a>
        </div>
        <div class="nav-center d-none d-md-flex" style="align-items:center;">
            <a href="index.php" class="nav-btn">🛒 Vender</a>
            <span class="nav-sep">|</span>
            <a href="productos.php" class="nav-btn">📦 Productos</a>
            <span class="nav-sep">|</span>
            <a href="usuarios.php" class="nav-btn active">👥 Usuarios</a>
            <span class="nav-sep">|</span>
            <a href="ventas.php" class="nav-btn">📊 Historial</a>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📥 Ingreso</a>
            <span class="nav-sep">|</span>
            <a href="index.php" class="nav-btn">📤 Egreso</a>
            <span class="nav-sep">|</span>
            <a href="corte_caja.php" class="nav-btn">🔒 Corte</a>
        </div>
        <div class="nav-right">
            <span class="rol-badge" style="margin-right:6px;">
                <?php echo esAdminPOS() ? '👑' : '👤'; ?>
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
            <a href="logout.php" class="nav-btn">➤ Salir</a>
        </div>
    </div>
</nav>

<div class="pos-wrapper">
    <div class="page-header">
        <h1>👥 Usuarios</h1>
    </div>

        <div class="panel">
            <h2><?php echo isset($_GET['edit']) ? 'Editar' : 'Nuevo'; ?> Usuario</h2>
            
            <?php
            $editUser = null;
            $edit_id = $_GET['edit'] ?? 0;
            if ($edit_id) {
                $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ?');
                $stmt->bind_param('i', $edit_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $editUser = $res->fetch_assoc();
                $stmt->close();
            }
            ?>
            
            <form method="POST" class="form-producto">
                <input type="hidden" name="id" value="<?php echo $editUser['id'] ?? ''; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre Completo:</label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($editUser['nombre'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" name="usuario" required value="<?php echo htmlspecialchars($editUser['usuario'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rol:</label>
                        <select name="rol">
                            <option value="cajero" <?php echo ($editUser['rol'] ?? '') === 'cajero' ? 'selected' : ''; ?>>👤 Cajero</option>
                            <option value="recepcion" <?php echo ($editUser['rol'] ?? '') === 'recepcion' ? 'selected' : ''; ?>>📋 Recepción</option>
                            <option value="tecnico" <?php echo ($editUser['rol'] ?? '') === 'tecnico' ? 'selected' : ''; ?>>🔧 Técnico</option>
                            <option value="admin" <?php echo ($editUser['rol'] ?? '') === 'admin' ? 'selected' : ''; ?>>👑 Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contraseña <?php echo $editUser ? '(dejar vacío para no cambiar)' : ''; ?>:</label>
                        <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label>Módulos:</label>
                        <div style="display:flex;gap:16px;padding:8px 0;">
                            <label><input type="checkbox" name="modulos[]" value="ordenes" <?php echo (strpos($editUser['modulos'] ?? 'ordenes', 'ordenes') !== false) ? 'checked' : ''; ?>> Órdenes</label>
                            <label><input type="checkbox" name="modulos[]" value="pos" <?php echo (strpos($editUser['modulos'] ?? '', 'pos') !== false) ? 'checked' : ''; ?>> POS</label>
                            <label><input type="checkbox" name="modulos[]" value="inventario" <?php echo (strpos($editUser['modulos'] ?? '', 'inventario') !== false) ? 'checked' : ''; ?>> Repuestos / Scrap</label>
                            <label><input type="checkbox" name="modulos[]" value="finanzas" <?php echo (strpos($editUser['modulos'] ?? '', 'finanzas') !== false) ? 'checked' : ''; ?>> Finanzas</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="guardar" class="btn-guardar">💾 Guardar</button>
                <?php if ($editUser): ?>
                    <a href="usuarios.php" class="btn-cancelar">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <h2>Lista de Usuarios</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $usuarios_lista->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                        <td><code><?php echo htmlspecialchars($u['usuario']); ?></code></td>
                        <td>
                            <?php
                            $badges = ['admin' => '👑 Admin', 'full' => '👑 Full Órdenes', 'recepcion' => '📋 Recepción', 'tecnico' => '🔧 Técnico', 'cajero' => '👤 Cajero'];
                            echo '<span class="badge badge-' . $u['rol'] . '">' . ($badges[$u['rol']] ?? htmlspecialchars($u['rol'])) . '</span>';
                            ?>
                            <br><small style="color:#94a3b8;font-size:0.75rem;"><?php echo htmlspecialchars($u['modulos'] ?? ''); ?></small>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                        <td class="actions">
                            <a href="?edit=<?php echo $u['id']; ?>" class="btn-edit">✏️</a>
                            <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar usuario?')">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" name="eliminar" class="btn-delete">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>
<?php $db->close(); ?>