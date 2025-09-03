<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$admin = getAdminUser();

// Période par défaut
$period = $_GET['period'] ?? 'month';
$custom_from = $_GET['from'] ?? '';
$custom_to = $_GET['to'] ?? '';

// Calculer dates selon période
switch ($period) {
    case 'today':
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        $date_to = date('Y-m-d');
        break;
    case 'month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-d');
        break;
    case 'year':
        $date_from = date('Y-01-01');
        $date_to = date('Y-m-d');
        break;
    case 'custom':
        $date_from = $custom_from ?: date('Y-m-01');
        $date_to = $custom_to ?: date('Y-m-d');
        break;
    default:
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-d');
}

// Statistiques principales
$main_stats = fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status IN ('PENDING', 'CONFIRMED', 'PROCESSING', 'READY') THEN 1 END) as active_orders,
        COUNT(CASE WHEN status = 'CANCELLED' THEN 1 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(total_pages), 0) as total_pages_printed,
        COALESCE(AVG(CASE WHEN status = 'COMPLETED' THEN total_price END), 0) as avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$date_from, $date_to]);

// Revenus par jour
$daily_revenue = fetchAll("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders_count,
        COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
", [$date_from, $date_to]);

// Top clients
$top_clients = fetchAll("
    SELECT 
        u.first_name, u.last_name, u.email,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'COMPLETED' THEN o.total_price ELSE 0 END), 0) as total_spent
    FROM users u
    JOIN orders o ON u.id = o.user_id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
", [$date_from, $date_to]);

// Statistiques par type de papier
$paper_stats = fetchAll("
    SELECT 
        oi.paper_size,
        oi.color_mode,
        COUNT(*) as usage_count,
        COALESCE(SUM(oi.page_count * oi.copies), 0) as total_pages
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.paper_size, oi.color_mode
    ORDER BY usage_count DESC
", [$date_from, $date_to]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
 
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">Reportes y Análisis</h1>
                <div class="flex items-center space-x-4">
                    <button onclick="exportReport()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Exportar Reporte
                    </button>
                    <button onclick="printReport()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-print mr-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </header>

        <div class="p-6">
            
            <!-- Filtros de Período -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Período de Análisis</h3>
                <form method="GET" class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    
                    <!-- Períodos predefinidos -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                        <select name="period" onchange="toggleCustomDates(this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hoy</option>
                            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Última semana</option>
                            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Este mes</option>
                            <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Este año</option>
                            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Personalizado</option>
                        </select>
                    </div>
                    
                    <!-- Fechas personalizadas -->
                    <div class="custom-dates <?= $period !== 'custom' ? 'opacity-50' : '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                        <input type="date" name="from" value="<?= $custom_from ?>" 
                               <?= $period !== 'custom' ? 'disabled' : '' ?>
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="custom-dates <?= $period !== 'custom' ? 'opacity-50' : '' ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                        <input type="date" name="to" value="<?= $custom_to ?>" 
                               <?= $period !== 'custom' ? 'disabled' : '' ?>
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-chart-line mr-2"></i>Generar
                        </button>
                    </div>
                    
                    <div class="flex items-end">
                        <a href="reports.php" class="w-full bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors text-center">
                            <i class="fas fa-refresh mr-2"></i>Reset
                        </a>
                    </div>
                    
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Total Pedidos</p>
                            <p class="text-3xl font-bold"><?= $main_stats['total_orders'] ?></p>
                        </div>
                        <div class="bg-blue-400 bg-opacity-30 p-3 rounded-full">
                            <i class="fas fa-shopping-cart text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-blue-100 text-sm">
                        Período: <?= date('d/m', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Ingresos</p>
                            <p class="text-3xl font-bold">€<?= number_format($main_stats['total_revenue'], 0) ?></p>
                        </div>
                        <div class="bg-green-400 bg-opacity-30 p-3 rounded-full">
                            <i class="fas fa-euro-sign text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-green-100 text-sm">
                        Promedio: €<?= number_format($main_stats['avg_order_value'], 2) ?>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Páginas Impresas</p>
                            <p class="text-3xl font-bold"><?= number_format($main_stats['total_pages_printed']) ?></p>
                        </div>
                        <div class="bg-purple-400 bg-opacity-30 p-3 rounded-full">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-purple-100 text-sm">
                        Completados: <?= $main_stats['completed_orders'] ?>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100 text-sm">Tasa Conversión</p>
                            <p class="text-3xl font-bold">
                                <?= $main_stats['total_orders'] > 0 ? round(($main_stats['completed_orders'] / $main_stats['total_orders']) * 100) : 0 ?>%
                            </p>
                        </div>
                        <div class="bg-orange-400 bg-opacity-30 p-3 rounded-full">
                            <i class="fas fa-percentage text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-orange-100 text-sm">
                        Cancelados: <?= $main_stats['cancelled_orders'] ?>
                    </div>
                </div>

            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                
                <!-- Revenue Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Evolución de Ingresos</h3>
                    <div class="h-80">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Paper Usage Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Uso de Papel por Tipo</h3>
                    <div class="h-80">
                        <canvas id="paperChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- Tables Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Top Clients -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Clientes
                    </h3>
                    
                    <div class="space-y-3">
                        <?php foreach (array_slice($top_clients, 0, 8) as $index => $client): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    <?= $index + 1 ?>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($client['email']) ?></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-green-600">€<?= number_format($client['total_spent'], 2) ?></div>
                                <div class="text-xs text-gray-500"><?= $client['total_orders'] ?> pedidos</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Paper Statistics -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>Estadísticas de Papel
                    </h3>
                    
                    <div class="space-y-3">
                        <?php foreach ($paper_stats as $stat): ?>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex space-x-1">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                        <?= $stat['paper_size'] ?>
                                    </span>
                                    <span class="px-2 py-1 <?= $stat['color_mode'] === 'BW' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800' ?> text-xs rounded-full font-medium">
                                        <?= $stat['color_mode'] === 'BW' ? 'B/N' : 'COLOR' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900"><?= number_format($stat['total_pages']) ?> pág.</div>
                                <div class="text-xs text-gray-500"><?= $stat['usage_count'] ?> trabajos</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Export Section -->
            <div class="mt-8 bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-download text-green-500 mr-2"></i>Exportar Datos
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="exportDetailed()" class="flex items-center justify-center px-6 py-4 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-file-excel mr-3 text-xl"></i>
                        <div>
                            <div class="font-medium">Excel Detallado</div>
                            <div class="text-xs opacity-75">Todos los pedidos + items</div>
                        </div>
                    </button>
                    
                    <button onclick="exportSummary()" class="flex items-center justify-center px-6 py-4 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-chart-bar mr-3 text-xl"></i>
                        <div>
                            <div class="font-medium">Resumen Ejecutivo</div>
                            <div class="text-xs opacity-75">KPIs y métricas</div>
                        </div>
                    </button>
                    
                    <button onclick="exportFinancial()" class="flex items-center justify-center px-6 py-4 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                        <i class="fas fa-euro-sign mr-3 text-xl"></i>
                        <div>
                            <div class="font-medium">Reporte Financiero</div>
                            <div class="text-xs opacity-75">Ingresos y facturación</div>
                        </div>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($daily_revenue as $day): ?>'<?= date('d/m', strtotime($day['date'])) ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Ingresos €',
                    data: [<?php foreach ($daily_revenue as $day): ?><?= $day['revenue'] ?>,<?php endforeach; ?>],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Pedidos',
                    data: [<?php foreach ($daily_revenue as $day): ?><?= $day['orders_count'] ?>,<?php endforeach; ?>],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Paper Chart
        const paperCtx = document.getElementById('paperChart').getContext('2d');
        new Chart(paperCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($paper_stats as $stat): ?>'<?= $stat['paper_size'] ?> <?= $stat['color_mode'] ?>',<?php endforeach; ?>],
                datasets: [{
                    data: [<?php foreach ($paper_stats as $stat): ?><?= $stat['total_pages'] ?>,<?php endforeach; ?>],
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Functions
        function toggleCustomDates(period) {
            const customInputs = document.querySelectorAll('.custom-dates input');
            const customDivs = document.querySelectorAll('.custom-dates');
            
            if (period === 'custom') {
                customInputs.forEach(input => input.disabled = false);
                customDivs.forEach(div => div.classList.remove('opacity-50'));
            } else {
                customInputs.forEach(input => input.disabled = true);
                customDivs.forEach(div => div.classList.add('opacity-50'));
            }
        }

        function exportReport() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export-report.php?' + params.toString();
        }

        function exportDetailed() {
            const params = new URLSearchParams(window.location.search);
            params.set('type', 'detailed');
            window.location.href = 'export-report.php?' + params.toString();
        }

        function exportSummary() {
            const params = new URLSearchParams(window.location.search);
            params.set('type', 'summary');
            window.location.href = 'export-report.php?' + params.toString();
        }

        function exportFinancial() {
            const params = new URLSearchParams(window.location.search);
            params.set('type', 'financial');
            window.location.href = 'export-report.php?' + params.toString();
        }

        function printReport() {
            window.print();
        }
    </script>

</body>
</html>