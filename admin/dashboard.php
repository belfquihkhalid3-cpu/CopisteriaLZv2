<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

$admin = getAdminUser();

// --- La lógica PHP para obtener datos no cambia ---
$stats = fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status IN ('PENDING', 'CONFIRMED', 'PROCESSING', 'READY') THEN 1 END) as active_orders,
        COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_orders,
        COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_price ELSE 0 END), 0) as today_revenue
    FROM orders
");
$user_count = fetchOne("SELECT COUNT(*) as total FROM users WHERE is_admin = 0")['total'];
$recent_orders = fetchAll("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$status_stats = fetchAll("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status 
    ORDER BY count DESC
");

// Variable para detectar la página actual y aplicar el estilo activo
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        /* Estilo para el indicador de la barra de navegación activa */
    .nav-active { position: relative; }
.nav-active::before { 
    content: ''; 
    position: absolute; 
    left: 0; 
    top: 50%; 
    transform: translateY(-50%); 
    height: 60%; 
    width: 4px; 
    background-color: white; 
    border-radius: 0 4px 4px 0; 
}
    </style>
</head>
<body class="bg-gray-100">
<?php include 'includes/sidebar.php'; ?>
  

    <div class="ml-64 min-h-screen">
        
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        <?= date('d/m/Y H:i') ?>
                    </div>
                    <div class="relative">
                        <button class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-bell mr-2"></i>
                            Notificaciones
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Pedidos</p>
                            <p class="text-3xl font-bold text-gray-800"><?= $stats['total_orders'] ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <span class="text-blue-600 font-medium"><?= $stats['today_orders'] ?></span> hoy
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Pedidos Activos</p>
                            <p class="text-3xl font-bold text-gray-800"><?= $stats['active_orders'] ?></p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        Requieren atención
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Usuarios</p>
                            <p class="text-3xl font-bold text-gray-800"><?= $user_count ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        Usuarios registrados
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Ingresos Totales</p>
                            <p class="text-3xl font-bold text-gray-800">€<?= number_format($stats['total_revenue'], 2) ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-euro-sign text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <span class="text-purple-600 font-medium">€<?= number_format($stats['today_revenue'], 2) ?></span> hoy
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pedidos por Estado</h3>
                    <div class="h-80">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Pedidos Recientes</h3>
                        <a href="orders.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Ver todos
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        <?php foreach (array_slice($recent_orders, 0, 6) as $order): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="font-medium text-gray-900">
                                        #<?= htmlspecialchars($order['order_number']) ?>
                                    </div>
                                    <div class="status-badge status-<?= strtolower($order['status']) ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900">€<?= number_format($order['total_price'], 2) ?></div>
                                <div class="text-xs text-gray-500"><?= date('d/m H:i', strtotime($order['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>


<!-- Advanced Widgets Section -->
<div class="mt-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Análisis Avanzado</h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Performance Today -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Rendimiento Hoy</h3>
                <i class="fas fa-calendar-day text-blue-500"></i>
            </div>
            
            <?php
            $today_stats = fetchOne("
                SELECT 
                    COUNT(*) as orders_today,
                    COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as revenue_today,
                    COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_today
                FROM orders 
                WHERE DATE(created_at) = CURDATE()
            ");
            
            $yesterday_stats = fetchOne("
                SELECT 
                    COUNT(*) as orders_yesterday,
                    COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as revenue_yesterday
                FROM orders 
                WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            ");
            
            $orders_change = $yesterday_stats['orders_yesterday'] > 0 ? 
                round((($today_stats['orders_today'] - $yesterday_stats['orders_yesterday']) / $yesterday_stats['orders_yesterday']) * 100) : 0;
            $revenue_change = $yesterday_stats['revenue_yesterday'] > 0 ? 
                round((($today_stats['revenue_today'] - $yesterday_stats['revenue_yesterday']) / $yesterday_stats['revenue_yesterday']) * 100) : 0;
            ?>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Pedidos</span>
                    <div class="text-right">
                        <span class="font-bold text-2xl"><?= $today_stats['orders_today'] ?></span>
                        <div class="text-xs <?= $orders_change >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $orders_change >= 0 ? '↗' : '↘' ?> <?= abs($orders_change) ?>%
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Ingresos</span>
                    <div class="text-right">
                        <span class="font-bold text-2xl">€<?= number_format($today_stats['revenue_today'], 0) ?></span>
                        <div class="text-xs <?= $revenue_change >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $revenue_change >= 0 ? '↗' : '↘' ?> <?= abs($revenue_change) ?>%
                        </div>
                    </div>
                </div>
                
                <div class="pt-2 border-t">
                    <div class="text-xs text-gray-500">
                        Tasa conversión: <?= $today_stats['orders_today'] > 0 ? round(($today_stats['completed_today'] / $today_stats['orders_today']) * 100) : 0 ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Orders Alert -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Alertas Pendientes</h3>
                <i class="fas fa-exclamation-triangle text-orange-500"></i>
            </div>
            
            <?php
            $urgent_orders = fetchAll("
                SELECT order_number, created_at, total_price 
                FROM orders 
                WHERE status = 'PENDING' AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                ORDER BY created_at ASC LIMIT 5
            ");
            
            $old_processing = fetchOne("
                SELECT COUNT(*) as count 
                FROM orders 
                WHERE status = 'PROCESSING' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")['count'];
            ?>
            
            <div class="space-y-3">
                <?php if (!empty($urgent_orders)): ?>
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="text-sm font-medium text-red-800 mb-2">
                            <i class="fas fa-clock mr-1"></i>
                            Pedidos pendientes (+2h)
                        </div>
                        <?php foreach ($urgent_orders as $order): ?>
                        <div class="text-xs text-red-600 flex justify-between">
                            <span>#<?= $order['order_number'] ?></span>
                            <span><?= date('H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($old_processing > 0): ?>
                    <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="text-sm font-medium text-orange-800">
                            <i class="fas fa-cog mr-1"></i>
                            <?= $old_processing ?> pedidos en proceso (+24h)
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($urgent_orders) && $old_processing == 0): ?>
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-center">
                        <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                        <div class="text-sm text-green-800 font-medium">Todo al día</div>
                        <div class="text-xs text-green-600">No hay pedidos urgentes</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Estadísticas Rápidas</h3>
                <i class="fas fa-tachometer-alt text-purple-500"></i>
            </div>
            
            <?php
            $quick_stats = fetchOne("
                SELECT 
                    (SELECT COUNT(*) FROM users WHERE is_admin = 0) as total_users,
                    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
                    (SELECT COALESCE(AVG(total_price), 0) FROM orders WHERE status = 'COMPLETED' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as avg_week,
                    (SELECT COUNT(*) FROM orders WHERE status = 'READY') as ready_pickup
            ");
            ?>
            
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Usuarios totales</span>
                    <span class="font-bold"><?= number_format($quick_stats['total_users']) ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Nuevos hoy</span>
                    <span class="font-bold text-blue-600"><?= $quick_stats['new_users_today'] ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Ticket promedio 7d</span>
                    <span class="font-bold">€<?= number_format($quick_stats['avg_week'], 2) ?></span>
                </div>
                
                <div class="flex justify-between items-center pt-2 border-t">
                    <span class="text-sm text-gray-600">Listos para recoger</span>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold <?= $quick_stats['ready_pickup'] > 0 ? 'text-green-600' : 'text-gray-400' ?>">
                            <?= $quick_stats['ready_pickup'] ?>
                        </span>
                        <?php if ($quick_stats['ready_pickup'] > 0): ?>
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Real-time Updates -->
<div class="mt-6 bg-white rounded-xl shadow-sm p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-sm text-gray-600">
                Última actualización: <span id="last-update"><?= date('H:i:s') ?></span>
            </span>
        </div>
        <button onclick="location.reload()" class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-sync mr-1"></i>Actualizar ahora
        </button>
    </div>
</div>

<script>
// Update timestamp every minute
setInterval(() => {
    document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
}, 60000);

// Auto refresh dashboard every 5 minutes
setInterval(() => {
    location.reload();
}, 300000);
</script>
        </main>
    </div>

    <style>
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fbbf24; color: white; }
        .status-confirmed { background-color: #3b82f6; color: white; }
        .status-processing { background-color: #8b5cf6; color: white; }
        .status-ready { background-color: #10b981; color: white; }
        .status-completed { background-color: #059669; color: white; }
        .status-cancelled { background-color: #ef4444; color: white; }
    </style>

    <script>
        // Status Chart
        const ctx = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($status_stats as $stat): ?>'<?= $stat['status'] ?>',<?php endforeach; ?>],
                datasets: [{
                    data: [<?php foreach ($status_stats as $stat): ?><?= $stat['count'] ?>,<?php endforeach; ?>],
                    backgroundColor: [
                        '#fbbf24', '#3b82f6', '#8b5cf6', '#10b981', '#059669', '#ef4444'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Auto-refresh cada 30 segundos
        setTimeout(() => location.reload(), 30000);
    </script>

</body>
</html>