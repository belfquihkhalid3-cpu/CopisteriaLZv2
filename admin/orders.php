<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

$admin = getAdminUser();

// Filtres
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Construction requête
$where_conditions = [];
$params = [];

if ($status_filter && $status_filter !== 'ALL') {
    $where_conditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = '(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($date_from) {
    $where_conditions[] = 'DATE(o.created_at) >= ?';
    $params[] = $date_from;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Compter total
$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id $where_clause";
$total_orders = fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_orders / $per_page);

// Récupérer commandes
$orders_sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               $where_clause 
               ORDER BY o.created_at DESC 
               LIMIT $per_page OFFSET $offset";
$orders = fetchAll($orders_sql, $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

  
    <!-- Include Sidebar (même que dashboard) -->
    <?php include 'includes/sidebar.php'; ?>
<!-- Ajouter après les filtres, avant la table -->

<!-- Bulk Actions Panel -->
<div id="bulk-actions" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 hidden">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="font-medium text-blue-800">
                <span id="selected-count">0</span> pedidos seleccionados
            </span>
        </div>
        <div class="flex space-x-2">
            <button onclick="bulkAction('mark_confirmed')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                Confirmar
            </button>
            <button onclick="bulkAction('mark_ready')" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                Marcar Listos
            </button>
            <button onclick="bulkAction('mark_completed')" class="bg-purple-500 text-white px-3 py-1 rounded text-sm hover:bg-purple-600">
                Completar
            </button>
            <button onclick="bulkAction('export_selected')" class="bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600">
                <i class="fas fa-download mr-1"></i>Exportar
            </button>
        </div>
    </div>
</div>

<!-- Ajouter checkbox dans l'en-tête de table -->
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
    <input type="checkbox" onchange="selectAllOrders(this.checked)" class="rounded">
</th>

<!-- Ajouter checkbox dans chaque ligne -->
<td class="px-6 py-4">
    <input type="checkbox" value="<?= $order['id'] ?>" class="order-checkbox rounded" 
           onchange="toggleOrderSelection(<?= $order['id'] ?>, this)">
</td>
    <div class="ml-64 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">Gestión de Pedidos</h1>
                <div class="flex items-center space-x-4">
                    <button onclick="exportOrders()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-download mr-2"></i>Exportar Excel
                    </button>
                    <button onclick="refreshOrders()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-sync mr-2"></i>Actualizar
                    </button>
                </div>
            </div>
        </header>

        <!-- Filters -->
        <div class="p-6">
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Número, cliente, email..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha desde</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">#<?= htmlspecialchars($order['order_number']) ?></div>
                                <div class="text-xs text-gray-500">Código: <?= htmlspecialchars($order['pickup_code']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($order['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select onchange="changeOrderStatus(<?= $order['id'] ?>, this.value)" 
                                        class="status-select text-xs px-2 py-1 rounded-full border-0 focus:ring-2 focus:ring-blue-500">
                                    <option value="PENDING" <?= $order['status'] === 'PENDING' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="CONFIRMED" <?= $order['status'] === 'CONFIRMED' ? 'selected' : '' ?>>Confirmado</option>
                                    <option value="PROCESSING" <?= $order['status'] === 'PROCESSING' ? 'selected' : '' ?>>En Proceso</option>
                                    <option value="READY" <?= $order['status'] === 'READY' ? 'selected' : '' ?>>Listo</option>
                                    <option value="COMPLETED" <?= $order['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completado</option>
                                    <option value="CANCELLED" <?= $order['status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div><?= $order['total_files'] ?> archivos</div>
                                <div class="text-xs text-gray-500"><?= $order['total_pages'] ?> páginas</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">€<?= number_format($order['total_price'], 2) ?></div>
                                <div class="text-xs text-gray-500"><?= ucfirst(strtolower(str_replace('_', ' ', $order['payment_method']))) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?= date('d/m/Y', strtotime($order['created_at'])) ?><br>
                                <span class="text-xs"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <button onclick="openOrderDetails(<?= $order['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Ver detalles">
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
                            <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= $search ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Anterior</a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= $search ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
    </div>


<!-- Ajouter checkbox dans l'en-tête de table -->
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
    <input type="checkbox" onchange="selectAllOrders(this.checked)" class="rounded">
</th>

<!-- Ajouter checkbox dans chaque ligne -->
<td class="px-6 py-4">
    <input type="checkbox" value="<?= $order['id'] ?>" class="order-checkbox rounded" 
           onchange="toggleOrderSelection(<?= $order['id'] ?>, this)">
</td>
    <script>
        // Changer statut commande
        async function changeOrderStatus(orderId, newStatus) {
            try {
                const response = await fetch('api/update-order-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Estado actualizado correctamente', 'success');
                } else {
                    showNotification('Error: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        // Voir détails commande
        function viewOrder(orderId) {
window.open('order-details.php?id=' + orderId + '&token=' + result.token, '_blank');
        }

        // Télécharger fichiers
        function downloadFiles(orderId) {
            window.location.href = 'download-files.php?order=' + orderId;
        }

        // Imprimer commande
        function printOrder(orderId) {
            window.open('print-order.php?id=' + orderId, '_blank');
        }

        // Exporter Excel
        function exportOrders() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export-orders.php?' + params.toString();
        }

        // Actualiser page
        function refreshOrders() {
            location.reload();
        }

        // Notifications
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
        // Ajouter après les autres fonctions JavaScript

// Sélection multiple
let selectedOrders = new Set();

function toggleOrderSelection(orderId, checkbox) {
    if (checkbox.checked) {
        selectedOrders.add(orderId);
    } else {
        selectedOrders.delete(orderId);
    }
    updateBulkActions();
}

function selectAllOrders(selectAll) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAll;
        const orderId = parseInt(cb.value);
        if (selectAll) {
            selectedOrders.add(orderId);
        } else {
            selectedOrders.delete(orderId);
        }
    });
    updateBulkActions();
}

function updateBulkActions() {
    const bulkPanel = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    if (selectedOrders.size > 0) {
        bulkPanel.classList.remove('hidden');
        selectedCount.textContent = selectedOrders.size;
    } else {
        bulkPanel.classList.add('hidden');
    }
}

// Actions en lot
async function bulkAction(action) {
    if (selectedOrders.size === 0) return;
    
    const actionNames = {
        'mark_confirmed': 'marcar como confirmados',
        'mark_ready': 'marcar como listos', 
        'mark_completed': 'marcar como completados',
        'export_selected': 'exportar archivos'
    };
    
    if (!confirm(`¿${actionNames[action]} ${selectedOrders.size} pedidos?`)) return;
    
    try {
        const response = await fetch('api/bulk-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                order_ids: Array.from(selectedOrders)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.download_url) {
                window.location.href = result.download_url;
            } else {
                showNotification(result.message, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            showNotification('Error: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// Búsqueda en tiempo real
let searchTimeout;
function liveSearch(value) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.set('search', value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }, 500);
}

// Auto-refresh cada 30 segundos
setInterval(() => {
    if (selectedOrders.size === 0) {
        location.reload();
    }
}, 30000);

async function openOrderDetails(orderId) {
    try {
        const response = await fetch('api/get-order-token.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order_id: orderId})
        });
        const result = await response.json();
        
        if (result.success) {
            window.open('pedido/' + orderId + '/' + result.token, '_blank');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
    </script>

</body>
</html>