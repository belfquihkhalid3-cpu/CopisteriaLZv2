<div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white z-30">
    <!-- Logo -->
    <div class="flex items-center justify-center h-20 border-b border-gray-800">
        <div class="flex items-center space-x-3">
            <i class="fas fa-print text-blue-400 text-2xl"></i>
            <span class="text-xl font-bold">Admin Panel</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-8">
        <div class="px-4 space-y-2">
            <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition-colors">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="orders.php" class="flex items-center space-x-3 px-4 py-3 bg-blue-600 rounded-lg">
                <i class="fas fa-shopping-cart"></i>
                <span>Pedidos</span>
            </a>
            <!-- Ajouter dans la navigation après "Pedidos" -->
<a href="reports.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition-colors">
    <i class="fas fa-chart-bar"></i>
    <span>Reportes</span>
</a>
<!-- Ajouter après "Reportes" -->
<a href="settings.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition-colors">
    <i class="fas fa-cog"></i>
    <span>Configuración</span>
</a>
        </div>
    </nav>

    <!-- Admin Info -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-white"></i>
            </div>
            <div class="flex-1">
                <div class="font-medium"><?= htmlspecialchars($_SESSION['admin_name']) ?></div>
                <div class="text-xs text-gray-400">Administrador</div>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-white">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</div>