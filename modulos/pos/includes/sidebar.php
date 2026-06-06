<!-- Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start sidebar-jb" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <i class="bi bi-shield-lock me-2"></i> Admin
            <?php elseif ($_SESSION['rol'] === 'full'): ?>
            <i class="bi bi-person-check me-2"></i> Full Órdenes
            <?php elseif ($_SESSION['rol'] === 'cajero'): ?>
            <i class="bi bi-cash me-2"></i> Cajero
            <?php else: ?>
            <i class="bi bi-person me-2"></i> <?php echo htmlspecialchars(ucfirst($_SESSION['rol'] ?? 'Usuario')); ?>
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3 d-flex flex-column">
        <div>
            <a href="index.php" class="sidebar-menu-item">
                <i class="bi bi-house-door"></i>
                <span>Home</span>
            </a>

            <a href="productos.php" class="sidebar-menu-item">
                <i class="bi bi-box-seam"></i>
                <span>Productos</span>
            </a>

            <a href="ventas.php" class="sidebar-menu-item">
                <i class="bi bi-receipt"></i>
                <span>Registro de Ventas</span>
            </a>

            <a href="clientes.php" class="sidebar-menu-item">
                <i class="bi bi-people"></i>
                <span>Clientes</span>
            </a>

            <a href="comprobante.php" class="sidebar-menu-item">
                <i class="bi bi-file-earmark-text"></i>
                <span>Comprobante</span>
            </a>

            <hr class="sidebar-divider">

            <?php if (esAdminPOS()): ?>
            <a href="configuracion.php" class="sidebar-menu-item">
                <i class="bi bi-gear"></i>
                <span>Configuración</span>
            </a>
            <?php endif; ?>
        </div>

        <div style="flex:1;"></div>

        <hr class="sidebar-divider">

        <button class="sidebar-menu-item" onclick="toggleDarkMode(); bootstrap.Offcanvas.getInstance(document.getElementById('sidebarMenu')).hide();">
            <i class="bi bi-moon-stars-fill" id="sidebarIconDark"></i>
            <span id="sidebarTextDark">Modo Nocturno</span>
        </button>

        <hr class="sidebar-divider">

        <a href="logout.php" class="sidebar-menu-item">
            <i class="bi bi-box-arrow-right"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>