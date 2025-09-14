<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white shadow-2xl z-30">
    
    <!-- Header avec animation -->
    <div class="flex items-center justify-center h-20 border-b border-slate-700/50 bg-slate-900/50 backdrop-blur">
        <div class="flex items-center space-x-3 transform hover:scale-105 transition-transform duration-200">
            <div class="relative">
                <i class="fas fa-print text-blue-400 text-2xl"></i>
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
            </div>
            <div>
                <span class="text-xl font-bold bg-gradient-to-r from-white to-blue-200 bg-clip-text text-transparent">Admin Panel</span>
                <div class="text-xs text-slate-400 -mt-1">Copistería Pro</div>
            </div>
        </div>
    </div>

    <!-- Navigation principale -->
    <nav class="flex-1 mt-8 px-4">
        <div class="space-y-2">
            
            <!-- Dashboard -->
            <a href="dashboard.php" class="nav-item group <?= ($current_page == 'dashboard.php') ? 'nav-active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="nav-content">
                    <span class="nav-title">Dashboard</span>
                    <span class="nav-subtitle">Vista general</span>
                </div>
                <div class="nav-badge">
                    <i class="fas fa-chevron-right text-xs"></i>
                </div>
            </a>

            <!-- Pedidos -->
            <a href="orders.php" class="nav-item group <?= ($current_page == 'orders.php' || $current_page == 'order-details.php' || $current_page == 'order_details.php') ? 'nav-active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="nav-content">
                    <span class="nav-title">Pedidos Online</span>
                    <span class="nav-subtitle">Gestión de órdenes</span>
                </div>
                <div class="nav-badge">
                    <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full">O</span>
                </div>
            </a>
            <!-- Ajouter après le lien "Pedidos" existant -->
<a href="orders-local.php" class="nav-item group <?= ($current_page == 'orders-local.php') ? 'nav-active' : '' ?>">
    <div class="nav-icon-wrapper">
        <i class="fas fa-desktop"></i>
    </div>
    <div class="nav-content">
        <span class="nav-title">Auto Services</span>
        <span class="nav-subtitle">Terminales en tienda</span>
    </div>
    <div class="nav-badge">
        <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">A</span>
    </div>
</a>

            <!-- Usuarios -->
            <a href="users.php" class="nav-item group <?= ($current_page == 'users.php') ? 'nav-active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-users"></i>
                </div>
                <div class="nav-content">
                    <span class="nav-title">Usuarios</span>
                    <span class="nav-subtitle">Clientes registrados</span>
                </div>
                <div class="nav-badge">
                    <i class="fas fa-chevron-right text-xs"></i>
                </div>
            </a>

            <!-- Configuración -->
            <a href="settings.php" class="nav-item group <?= ($current_page == 'settings.php') ? 'nav-active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="nav-content">
                    <span class="nav-title">Configuración</span>
                    <span class="nav-subtitle">Precios y sistema</span>
                </div>
                <div class="nav-badge">
                    <i class="fas fa-chevron-right text-xs"></i>
                </div>
            </a>

        </div>
        
        <!-- Separateur -->
        <div class="border-t border-slate-700/50 my-6"></div>
        
        <!-- Liens secondaires -->
        <div class="space-y-2">
            <a href="reports.php" class="nav-item group <?= ($current_page == 'reports.php') ? 'nav-active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="nav-content">
                    <span class="nav-title">Reportes</span>
                    <span class="nav-subtitle">Estadísticas</span>
                </div>
            </a>
            
            <a href="backup.php" class="nav-item group">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-database"></i>
                </div>
                <div class="nav-content">
                    <span class="nav-title">Backup</span>
                    <span class="nav-subtitle">Copias de seguridad</span>
                </div>
            </a>
        </div>
    </nav>

    <!-- Info admin en bas -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700/50 bg-slate-900/30 backdrop-blur">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <span class="font-bold text-white text-lg"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></span>
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-slate-900"></div>
            </div>
            <div class="flex-1">
                <div class="font-semibold text-white"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrador') ?></div>
                <div class="text-xs text-slate-400">En línea</div>
            </div>
            <div class="flex space-x-2">
                <a href="profile.php" class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-700/50 transition-colors">
                    <i class="fas fa-user-cog"></i>
                </a>
                <a href="logout.php" class="text-slate-400 hover:text-red-400 p-2 rounded-lg hover:bg-slate-700/50 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</aside>

<style>
/* Styles pour la sidebar moderne */
.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: #cbd5e1;
}

.nav-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s ease;
}

.nav-item:hover::before {
    left: 100%;
}

.nav-item:hover {
    background: rgba(255,255,255,0.1);
    transform: translateX(4px);
    color: white;
}

.nav-active {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 8px 25px rgba(59,130,246,0.3);
    transform: translateX(4px);
}

.nav-active::after {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 24px;
    background: white;
    border-radius: 0 8px 8px 0;
}

.nav-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    margin-right: 12px;
    transition: all 0.3s ease;
}

.nav-active .nav-icon-wrapper {
    background: rgba(255,255,255,0.2);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.nav-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.nav-title {
    font-weight: 600;
    font-size: 14px;
    line-height: 1.2;
}

.nav-subtitle {
    font-size: 11px;
    opacity: 0.7;
    line-height: 1.2;
}

.nav-badge {
    margin-left: auto;
    opacity: 0.6;
    transition: opacity 0.3s ease;
}

.nav-item:hover .nav-badge {
    opacity: 1;
}

/* Animation du loader au hover */
.nav-item:hover .nav-icon-wrapper {
    transform: scale(1.1);
    background: rgba(255,255,255,0.2);
}
</style>