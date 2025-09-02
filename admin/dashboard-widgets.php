<?php
// Ajouter à la fin de dashboard.php avant </main>
?>

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