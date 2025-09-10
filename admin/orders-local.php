<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';
require_once '../terminal/config.php';

$admin = getAdminUser();

// Filtres
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$terminal_filter = $_GET['terminal'] ?? '';
$date_from = $_GET['date_from'] ?? '';

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Construction requête
$where_conditions = ["o.source_type = 'TERMINAL'"];
$params = [];

if ($status_filter && $status_filter !== 'ALL') {
    $where_conditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = '(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($terminal_filter) {
    $where_conditions[] = 'o.terminal_id = ?';
    $params[] = $terminal_filter;
}

if ($date_from) {
    $where_conditions[] = 'DATE(o.created_at) >= ?';
    $params[] = $date_from;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Compter total
$count_sql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause";
$total_orders = fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_orders / $per_page);

// Récupérer commandes
$orders_sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
               CASE WHEN o.is_guest = 1 THEN 'Invitado' ELSE CONCAT(u.first_name, ' ', u.last_name) END as customer_name
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               $where_clause 
               ORDER BY o.created_at DESC 
               LIMIT $per_page OFFSET $offset";
$orders = fetchAll($orders_sql, $params);

// Obtenir liste des terminaux pour le filtre
global $terminals;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Locales - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-desktop mr-2 text-blue-500"></i>Pedidos Locales (Terminales)
                </h1>
                <div class="flex items-center space-x-4">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                        Total: <?= $total_orders ?> pedidos
                    </span>
                    <button onclick="exportTerminalOrders()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-download mr-2"></i>Exportar
                    </button>
                </div>
            </div>
        </header>

        <!-- Filters -->
        <div class="p-6">
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    
                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="ALL">Todos los estados</option>
                            <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pendientes</option>
                            <option value="CONFIRMED" <?= $status_filter === 'CONFIRMED' ? 'selected' : '' ?>>Confirmados</option>
                            <option value="PROCESSING" <?= $status_filter === 'PROCESSING' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="READY" <?= $status_filter === 'READY' ? 'selected' : '' ?>>Listos</option>
                            <option value="COMPLETED" <?= $status_filter === 'COMPLETED' ? 'selected' : '' ?>>Completados</option>
                        </select>
                    </div>

                    <!-- Terminal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Terminal</label>
                        <select name="terminal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los terminales</option>
                            <?php foreach ($terminals as $ip => $terminal): ?>
                                <option value="<?= $terminal['id'] ?>" <?= $terminal_filter === $terminal['id'] ? 'selected' : '' ?>>
                                    <?= $terminal['name'] ?> (<?= $terminal['location'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Buscar -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Número, cliente..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Fecha -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha desde</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Botones -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filtrar
                        </button>
                        <a href="orders-local.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                            <i class="fas fa-times mr-2"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terminal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-blue-600">#<?= htmlspecialchars($order['order_number']) ?></div>
                                <div class="text-sm text-gray-500">Código: <?= htmlspecialchars($order['pickup_code']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $terminal_name = 'Desconocido';
                                $terminal_location = '';
                                foreach ($terminals as $terminal) {
                                    if ($terminal['id'] === $order['terminal_id']) {
                                        $terminal_name = $terminal['name'];
                                        $terminal_location = $terminal['location'];
                                        break;
                                    }
                                }
                                ?>
                                <div class="flex items-center">
                                    <i class="fas fa-desktop text-blue-500 mr-2"></i>
                                    <div>
                                        <div class="font-medium text-gray-900"><?= $terminal_name ?></div>
                                        <div class="text-sm text-gray-500"><?= $terminal_location ?></div>
                                        <div class="text-xs text-gray-400">ID: <?= $order['terminal_id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($order['is_guest']): ?>
                                        <i class="fas fa-user-slash text-orange-500 mr-2"></i>
                                        <div>
                                            <div class="font-medium text-orange-600">Cliente Invitado</div>
                                            <div class="text-sm text-gray-500">Sin cuenta</div>
                                        </div>
                                    <?php else: ?>
                                        <i class="fas fa-user text-green-500 mr-2"></i>
                                        <div>
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($order['email']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php
                                    switch($order['status']) {
                                        case 'PENDING': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'CONFIRMED': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'PROCESSING': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'READY': echo 'bg-green-100 text-green-800'; break;
                                        case 'COMPLETED': echo 'bg-gray-100 text-gray-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div><?= $order['total_files'] ?> archivos</div>
                                <div class="text-xs text-gray-500"><?= $order['total_pages'] ?> páginas</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">€<?= number_format($order['total_price'], 2) ?></div>
                                <div class="text-xs text-gray-500">Pago en tienda</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?= date('d/m/Y', strtotime($order['created_at'])) ?><br>
                                <span class="text-xs"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <button onclick="viewOrder(<?= $order['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="downloadFiles(<?= $order['id'] ?>)" class="text-green-600 hover:text-green-900" title="Descargar archivos">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button onclick="printOrder(<?= $order['id'] ?>)" class="text-purple-600 hover:text-purple-900" title="Imprimir">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando <?= ($page - 1) * $per_page + 1 ?> - <?= min($page * $per_page, $total_orders) ?> de <?= $total_orders ?>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= $search ?>&terminal=<?= $terminal_filter ?>&date_from=<?= $date_from ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Anterior</a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= $search ?>&terminal=<?= $terminal_filter ?>&date_from=<?= $date_from ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Utiliser les mêmes fonctions que orders.php
        async function viewOrder(orderId) {
            try {
                const response = await fetch('api/get-order-token.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order_id: orderId})
                });
                const result = await response.json();
                
                if (result.success) {
                    window.open('order-details.php?id=' + orderId + '&token=' + result.token, '_blank');
                } else {
                    alert('Error al generar el token');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        }

        function downloadFiles(orderId) {
            window.location.href = 'download-files.php?order=' + orderId;
        }

        function printOrder(orderId) {
            window.open('print-order.php?id=' + orderId, '_blank');
        }

        function exportTerminalOrders() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'terminal');
            window.location.href = 'export-orders.php?' + params.toString();
        }
    </script>

</body>
</html>