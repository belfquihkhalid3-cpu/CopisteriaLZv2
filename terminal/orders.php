<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/user_functions.php';
require_once '../includes/security_headers.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
if (!$user) {
    header('Location: logout.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Construction de la requête avec filtres
$where_conditions = ['user_id = ?'];
$params = [$_SESSION['user_id']];

if ($status_filter && $status_filter !== 'ALL') {
    $where_conditions[] = 'status = ?';
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = 'DATE(created_at) >= ?';
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = 'DATE(created_at) <= ?';
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Compter le total pour la pagination
$count_sql = "SELECT COUNT(*) as total FROM orders $where_clause";
$total_result = fetchOne($count_sql, $params);
$total_orders = $total_result['total'];
$total_pages = ceil($total_orders / $per_page);

// Récupérer les commandes
$orders_sql = "SELECT * FROM orders 
               $where_clause 
               ORDER BY created_at DESC 
               LIMIT $per_page OFFSET $offset";
$orders = fetchAll($orders_sql, $params);

// Récupérer les statistiques
$stats_sql = "SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status IN ('PENDING', 'CONFIRMED', 'PROCESSING', 'PRINTING') THEN 1 END) as active_orders,
                COUNT(CASE WHEN status = 'CANCELLED' THEN 1 END) as cancelled_orders,
                COALESCE(SUM(total_price), 0) as total_spent
              FROM orders WHERE user_id = ?";
$stats = fetchOne($stats_sql, [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-print text-blue-500 text-xl"></i>
                        <h1 class="text-xl font-bold text-gray-800">Copisteria</h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="account.php" class="text-gray-600 hover:text-gray-800">Mi cuenta</a>
                    <span class="text-gray-600">Hola, <?= htmlspecialchars($user['first_name']) ?></span>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Nuevo pedido</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header de página -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Mis Pedidos</h1>
                    <p class="text-gray-600">Revisa el estado de tus impresiones</p>
                </div>
                <a href="index.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Nuevo Pedido
                </a>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                <div class="text-2xl font-bold text-blue-600"><?= $stats['total_orders'] ?></div>
                <div class="text-sm text-gray-600">Total</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                <div class="text-2xl font-bold text-green-600"><?= $stats['completed_orders'] ?></div>
                <div class="text-sm text-gray-600">Completados</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                <div class="text-2xl font-bold text-yellow-600"><?= $stats['active_orders'] ?></div>
                <div class="text-sm text-gray-600">En proceso</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                <div class="text-2xl font-bold text-red-600"><?= $stats['cancelled_orders'] ?></div>
                <div class="text-sm text-gray-600">Cancelados</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                <div class="text-2xl font-bold text-purple-600"><?= number_format($stats['total_spent'], 2) ?>€</div>
                <div class="text-sm text-gray-600">Gastado</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtrar pedidos</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="ALL" <?= $status_filter === 'ALL' || !$status_filter ? 'selected' : '' ?>>Todos</option>
                        <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="CONFIRMED" <?= $status_filter === 'CONFIRMED' ? 'selected' : '' ?>>Confirmados</option>
                        <option value="PROCESSING" <?= $status_filter === 'PROCESSING' ? 'selected' : '' ?>>En proceso</option>
                        <option value="PRINTING" <?= $status_filter === 'PRINTING' ? 'selected' : '' ?>>Imprimiendo</option>
                        <option value="READY" <?= $status_filter === 'READY' ? 'selected' : '' ?>>Listos</option>
                        <option value="COMPLETED" <?= $status_filter === 'COMPLETED' ? 'selected' : '' ?>>Completados</option>
                        <option value="CANCELLED" <?= $status_filter === 'CANCELLED' ? 'selected' : '' ?>>Cancelados</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="orders.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        <i class="fas fa-times mr-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Lista de pedidos -->
        <div class="bg-white rounded-lg shadow-sm">
            <?php if (!empty($orders)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pedido
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Archivos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">
                                            #<?= htmlspecialchars($order['order_number']) ?>
                                        </div>
                                        <?php if ($order['pickup_code']): ?>
                                            <div class="text-sm text-gray-600">
                                                Código: <span class="pickup-code"><?= htmlspecialchars($order['pickup_code']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= date('d/m/Y', strtotime($order['created_at'])) ?><br>
                                        <span class="text-xs text-gray-500"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-<?= strtolower($order['status']) ?>">
                                            <?php
                                            $status_labels = [
                                                'PENDING' => 'Pendiente',
                                                'CONFIRMED' => 'Confirmado',
                                                'PROCESSING' => 'En proceso',
                                                'PRINTING' => 'Imprimiendo',
                                                'READY' => 'Listo',
                                                'COMPLETED' => 'Completado',
                                                'CANCELLED' => 'Cancelado'
                                            ];
                                            echo $status_labels[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= $order['total_files'] ?> archivo(s)<br>
                                        <span class="text-xs text-gray-500"><?= $order['total_pages'] ?> páginas</span>
                                    </td>
                                  <td class="px-6 py-4 whitespace-nowrap">
    <div class="font-medium text-gray-900">€<?= number_format($order['total_price'] * $order['total_pages'], 2) ?></div>
    <div class="text-xs text-gray-500">
        <?= ucfirst(strtolower(str_replace('_', ' ', $order['payment_method']))) ?>
    </div>
</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="order-status.php?id=<?= $order['id'] ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye mr-1"></i>Ver
                                            </a>
                                             <button onclick="viewConfirmation(<?= $order['id'] ?>)" class="text-green-600 hover:text-green-900" title="Ver confirmación">
            <i class="fas fa-receipt"></i>
        </button>
                                            <?php if (in_array($order['status'], ['COMPLETED'])): ?>
                                                <a href="download-receipt.php?order=<?= $order['id'] ?>" 
                                                   class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-download mr-1"></i>Recibo
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($order['status'] === 'READY'): ?>
                                                <span class="text-green-600 font-medium">
                                                    <i class="fas fa-check-circle mr-1"></i>Para recoger
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Siguiente
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando
                                        <span class="font-medium"><?= ($page - 1) * $per_page + 1 ?></span>
                                        a
                                        <span class="font-medium"><?= min($page * $per_page, $total_orders) ?></span>
                                        de
                                        <span class="font-medium"><?= $total_orders ?></span>
                                        resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($total_pages, $page + 2);
                                        
                                        for ($i = $start; $i <= $end; $i++):
                                        ?>
                                            <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>">
                                                <?= $i ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty state -->
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No hay pedidos</h3>
                    <?php if ($status_filter || $date_from || $date_to): ?>
                        <p class="text-gray-600 mb-4">No se encontraron pedidos con los filtros aplicados</p>
                        <a href="orders.php" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-times mr-1"></i>Limpiar filtros
                        </a>
                    <?php else: ?>
                        <p class="text-gray-600 mb-4">Aún no has realizado ningún pedido</p>
                        <a href="index.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Hacer primer pedido
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<script>
    function viewConfirmation(orderId) {
    const url = `../terminal/order-confirmation.php?id=${orderId}`;
    window.open(url, '_blank', 'width=1000,height=800,scrollbars=yes,resizable=yes');
}
    </script>
</body>
</html>