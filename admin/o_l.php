<?php
session_start();




require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

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
    $where_conditions[] = '(o.order_number LIKE ? OR o.pickup_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
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
               CASE 
                   WHEN o.is_guest = 1 AND o.customer_name IS NOT NULL THEN o.customer_name
                   WHEN o.is_guest = 1 THEN 'Cliente Invitado' 
                   ELSE CONCAT(u.first_name, ' ', u.last_name) 
               END as customer_name,
               CASE 
                   WHEN o.is_guest = 1 AND o.customer_phone IS NOT NULL THEN o.customer_phone
                   WHEN o.is_guest = 1 THEN 'Sin teléfono'
                   ELSE u.phone 
               END as customer_phone_display
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
    <style>
      
/* Badges de configuration */
.config-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Conteneur configuration compact */
.config-container {
    max-width: 200px;
    overflow: hidden;
}

.config-folder {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-left: 3px solid #3b82f6;
    transition: all 0.2s ease;
}

.config-folder:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Style pour le select de statut */
.status-select {
    
    font-size: 11px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-select:focus {
    outline: none;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Couleurs par statut */
.status-select option[value="PENDING"] {
    background: linear-gradient(135deg, #fef3c7, #fbbf24);
    color: #92400e;
}

.status-select option[value="CONFIRMED"] {
    background: linear-gradient(135deg, #dbeafe, #3b82f6);
    color: #1e40af;
}

.status-select option[value="PROCESSING"] {
    background: linear-gradient(135deg, #fed7aa, #f97316);
    color: #c2410c;
}

.status-select option[value="PRINTING"] {
    background: linear-gradient(135deg, #e0e7ff, #6366f1);
    color: #4338ca;
}

.status-select option[value="READY"] {
    background: linear-gradient(135deg, #d1fae5, #10b981);
    color: #059669;
}

.status-select option[value="COMPLETED"] {
    background: linear-gradient(135deg, #dcfce7, #22c55e);
    color: #16a34a;
}

.status-select option[value="CANCELLED"] {
    background: linear-gradient(135deg, #fecaca, #ef4444);
    color: #dc2626;
}

/* Style dynamique basé sur la valeur sélectionnée */
.status-select[data-status="PENDING"] {
    background: linear-gradient(135deg, #fef3c7, #fbbf24);
    color: #92400e;
    border-color: #f59e0b;
}

.status-select[data-status="CONFIRMED"] {
    background: linear-gradient(135deg, #dbeafe, #3b82f6);
    color: #1e40af;
    border-color: #3b82f6;
}

.status-select[data-status="PROCESSING"] {
    background: linear-gradient(135deg, #fed7aa, #f97316);
    color: #c2410c;
    border-color: #f97316;
}

.status-select[data-status="PRINTING"] {
    background: linear-gradient(135deg, #e0e7ff, #6366f1);
    color: #4338ca;
    border-color: #6366f1;
}

.status-select[data-status="READY"] {
    background: linear-gradient(135deg, #d1fae5, #10b981);
    color: #059669;
    border-color: #10b981;
}

.status-select[data-status="COMPLETED"] {
    background: linear-gradient(135deg, #dcfce7, #22c55e);
    color: #16a34a;
    border-color: #22c55e;
}

.status-select[data-status="CANCELLED"] {
    background: linear-gradient(135deg, #fecaca, #ef4444);
    color: #dc2626;
    border-color: #ef4444;
}

/* Animation de hover */
.status-select:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Style pour les notifications */
.notification {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Amélioration de l'apparence du tableau */
.orders-table tbody tr:hover {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

/* Badge pour les terminaux */
.terminal-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    background: linear-gradient(135deg, #e0f2fe, #0284c7);
    color: #0369a1;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
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
       placeholder="Buscar por pedido, código, cliente, email..." 
       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terminal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Configuración</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?= date('d/m/Y', strtotime($order['created_at'])) ?><br>
                                <span class="text-xs"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                            </td>
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
                <div class="font-medium text-blue-600"><?= htmlspecialchars($order['customer_name']) ?></div>
                <div class="text-sm text-green-500"><?= htmlspecialchars($order['customer_phone_display']) ?></div>
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
    <select onchange="changeOrderStatus(<?= $order['id'] ?>, this.value)" 
            class="status-select"
            data-status="<?= $order['status'] ?>">
        <option value="PENDING" <?= $order['status'] === 'PENDING' ? 'selected' : '' ?>>Pendiente</option>
        <option value="CONFIRMED" <?= $order['status'] === 'CONFIRMED' ? 'selected' : '' ?>>Confirmado</option>
        <option value="PROCESSING" <?= $order['status'] === 'PROCESSING' ? 'selected' : '' ?>>En Proceso</option>
        <option value="PRINTING" <?= $order['status'] === 'PRINTING' ? 'selected' : '' ?>>Imprimiendo</option>
        <option value="READY" <?= $order['status'] === 'READY' ? 'selected' : '' ?>>Listo</option>
        <option value="COMPLETED" <?= $order['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completado</option>
        <option value="CANCELLED" <?= $order['status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelado</option>
    </select>
</td>
                           
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">€<?= number_format($order['total_price'], 2) ?></div>
                                <div class="text-xs text-gray-500">Pago en tienda</div>
                            </td>
                           
                     
                            <td class="px-6 py-4 whitespace-nowrap">
    <?php 
    $print_config = json_decode($order['print_config'], true) ?: [];
    $folders = $print_config['folders'] ?? [];
    ?>
    
    <?php if (!empty($folders)): ?>
        <div class="space-y-2">
            <?php foreach ($folders as $index => $folder): ?>
                <?php $config = $folder['configuration'] ?? []; ?>
                <div class="bg-gray-50 p-2 rounded-lg text-xs">
                    <div class="font-medium text-gray-800 mb-1">
                        <?= htmlspecialchars($folder['name'] ?? "Carpeta " . ($index + 1)) ?>
                        <span class="text-blue-600">(x<?= $folder['copies'] ?? 1 ?>)</span>
                    </div>
                    
                    <div class="flex flex-wrap gap-1">
                        <!-- Color -->
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-<?= ($config['colorMode'] ?? 'bw') === 'bw' ? 'gray' : 'purple' ?>-100 text-<?= ($config['colorMode'] ?? 'bw') === 'bw' ? 'gray' : 'purple' ?>-800">
                            <i class="fas fa-<?= ($config['colorMode'] ?? 'bw') === 'bw' ? 'circle' : 'palette' ?> mr-1"></i>
                            <?= ($config['colorMode'] ?? 'bw') === 'bw' ? 'B/N' : 'Color' ?>
                        </span>
                        
                        <!-- Papel -->
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-file mr-1"></i>
                            <?= $config['paperSize'] ?? 'A4' ?>
                        </span>
                        
                        <!-- Gramaje -->
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-weight-hanging mr-1"></i>
                            <?= $config['paperWeight'] ?? '80g' ?>
                        </span>
                        
                        <!-- Caras -->
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            <i class="fas fa-clone mr-1"></i>
                            <?= ($config['sides'] ?? 'double') === 'single' ? '1 cara' : '2 caras' ?>
                        </span>
                        
                        <!-- Orientación -->
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            <i class="fas fa-compass mr-1"></i>
                            <?= ($config['orientation'] ?? 'portrait') === 'portrait' ? 'Vertical' : 'Horizontal' ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <span class="text-gray-400 text-xs">Sin configuración</span>
    <?php endif; ?>
</td>
       <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex space-x-2">
                                    <button onclick="viewOrder(<?= $order['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                  <a href="generate-invoice.php?order_id=<?= $order['id'] ?>" target="_blank" 
   class="inline-flex items-center justify-center w-8 h-8 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors" 
   title="Generar factura">
    <i class="fas fa-file-invoice text-sm"></i>
</a>
                                   <!-- Dans la colonne Actions -->
<button onclick="selectPrinterAndPrint(<?= $order['id'] ?>)" 
        class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
    <i class="fas fa-print mr-1"></i>Imprimir
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

// Utiliser EXACTEMENT la même configuration que print-manager.php
const LOCAL_PRINT_SERVER = 'http://localhost:5000'; // Configuration fixe qui fonctionne

// Fonction principale pour le bouton "Imprimir"
async function selectPrinterAndPrint(orderId) {
    try {
        showNotification('Detectando impresoras...', 'info');
        
        // MÊME MÉTHODE que print-manager.php
        const response = await fetch(`${LOCAL_PRINT_SERVER}/detect-printers`);
        const result = await response.json();
        
        if (result.success && result.printers.length > 0) {
            // Récupérer les détails de la commande
            const orderResponse = await fetch(`api/get-order-details.php?order_id=${orderId}`);
            const orderData = await orderResponse.json();
            
            if (orderData.success) {
                showPrinterSelectionModal(orderId, orderData, result.printers);
            } else {
                showNotification('Error: No se encontraron detalles del pedido', 'error');
            }
        } else {
            showNotification('Aucune imprimante détectée sur le PC local. Vérifiez que le service Python fonctionne.', 'error');
        }
    } catch (error) {
        showNotification('Erreur de connexion au serveur d\'impression local: ' + error.message, 'error');
        console.error('Error:', error);
    }
}

// Interface de sélection d'imprimante (MÊME STYLE que print-manager)
function showPrinterSelectionModal(orderId, orderData, printers) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[95vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center">
                            <i class="fas fa-print mr-3"></i>
                            Configuración de Impresión
                        </h2>
                        <p class="text-blue-100 mt-1">Pedido #${orderData.order_number}</p>
                    </div>
                    <button onclick="document.body.removeChild(this.closest('.fixed'))" 
                            class="text-white hover:bg-white/20 rounded-full p-2 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="overflow-y-auto max-h-[calc(95vh-200px)]">
                <!-- Impresión rápida -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Impresión Rápida - Todo a una impresora
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        ${printers.map(printer => `
                            <button onclick="printAllWithPrinter(${orderId}, '${printer.name}', ${JSON.stringify(orderData).replace(/"/g, '&quot;')})" 
                                    class="group relative p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white text-lg">
                                            <i class="fas fa-print"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">${printer.name}</h4>
                                            <p class="text-sm text-gray-500">${printer.type === 'COLOR' ? 'Impresora Color' : 'Blanco y Negro'}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 ${printer.status === 'Normal' ? 'bg-green-500' : 'bg-yellow-500'} rounded-full"></div>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-600 bg-gray-50 rounded-lg p-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ${orderData.folders.length} carpeta(s) - ${orderData.folders.reduce((sum, f) => sum + f.files.length, 0)} archivo(s)
                                </div>
                            </button>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Impresión por carpetas -->
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-layer-group text-purple-500 mr-2"></i>
                        Impresión Selectiva - Por carpetas y archivos
                    </h3>
                    
                    <div class="space-y-6">
                        ${orderData.folders.map((folder, folderIndex) => `
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                                <!-- Header de carpeta -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center text-white">
                                            <i class="fas fa-folder"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="font-bold text-gray-800">${folder.name}</h4>
                                            <p class="text-sm text-gray-600">${folder.files.length} archivo(s) • ${folder.copies} copia(s)</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-700">Seleccionar impresora:</p>
                                        <select id="printer-folder-${folderIndex}" 
                                                class="mt-1 border-2 border-gray-300 rounded-lg px-3 py-2 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all">
                                            <option value="">-- Elegir impresora --</option>
                                            ${printers.map(printer => `
                                                <option value="${printer.name}">${printer.name} (${printer.type})</option>
                                            `).join('')}
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Configuración de carpeta -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div class="bg-white rounded-lg p-3 border">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Papel</span>
                                        </div>
                                        <p class="text-sm text-gray-900 font-semibold">${folder.configuration.paperSize}</p>
                                        <p class="text-xs text-gray-500">${folder.configuration.paperWeight}</p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-3 border">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-palette text-purple-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Color</span>
                                        </div>
                                        <p class="text-sm text-gray-900 font-semibold">
                                            ${folder.configuration.colorMode === 'bw' ? 'Blanco y Negro' : 'Color'}
                                        </p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-3 border">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-copy text-green-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Impresión</span>
                                        </div>
                                        <p class="text-sm text-gray-900 font-semibold">
                                            ${folder.configuration.sides === 'single' ? 'Una cara' : 'Doble cara'}
                                        </p>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-3 border">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-rotate text-orange-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-700">Orientación</span>
                                        </div>
                                        <p class="text-sm text-gray-900 font-semibold">
                                            ${folder.configuration.orientation === 'portrait' ? 'Vertical' : 'Horizontal'}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Lista de archivos -->
                                <div class="bg-white rounded-lg border">
                                    <div class="px-4 py-3 border-b bg-gray-50 rounded-t-lg">
                                        <h5 class="font-medium text-gray-800 flex items-center">
                                            <i class="fas fa-list mr-2 text-gray-600"></i>
                                            Archivos en esta carpeta
                                        </h5>
                                    </div>
                                    <div class="p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            ${folder.files.map((file, fileIndex) => `
                                                <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center text-white text-sm">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <div class="ml-3 flex-1">
                                                        <p class="text-sm font-medium text-gray-800 truncate">${file.name}</p>
                                                        <p class="text-xs text-gray-500">${file.pages || 1} página(s)</p>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            ${folder.copies}x
                                                        </span>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Total: ${orderData.folders.reduce((sum, f) => sum + f.files.length, 0)} archivos en ${orderData.folders.length} carpeta(s)
                </div>
                <div class="flex space-x-3">
                    <button onclick="document.body.removeChild(this.closest('.fixed'))" 
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                        Cancelar
                    </button>
                    <button onclick="printByFolders(${orderId}, ${JSON.stringify(orderData).replace(/"/g, '&quot;')})" 
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-print mr-2"></i>
                        Imprimir Selectivo
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Fonction d'impression (MÊME LOGIQUE que print-manager)
async function printWithLocalPrinter(orderId, printerName, orderData) {
    document.querySelector('.fixed').remove();
    
    try {
        showNotification(`Enviando pedido a ${printerName}...`, 'info');
        
        const printData = {
            printer_name: printerName,
            folders: orderData.folders,
            order_id: orderId
        };
        
        // Utiliser la MÊME méthode que print-manager.php
        const printResponse = await fetch(`${LOCAL_PRINT_SERVER}/print-order`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(printData)
        });
        
        const printResult = await printResponse.json();
        
        if (printResult.success) {
            showNotification('✅ Documento enviado a impresora local', 'success');
            // Marquer comme imprimé
            updateOrderStatus(orderId, 'PRINTING');
        } else {
            showNotification('❌ Error: ' + printResult.error, 'error');
        }
    } catch (error) {
        showNotification('❌ Error de conexión: ' + error.message, 'error');
    }
}
// Imprimer tout vers une imprimante
async function printAllWithPrinter(orderId, printerName, orderData) {
    document.querySelector('.fixed').remove();
    
    const printData = {
        printer_name: printerName,
        folders: orderData.folders,
        order_id: orderId
    };
    
    try {
        const response = await fetch(`${LOCAL_PRINT_SERVER}/print-order`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(printData)
        });
        
        const result = await response.json();
        if (result.success) {
            showNotification(`✅ Pedido completo enviado a ${printerName}`, 'success');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// Imprimer par dossier vers différentes imprimantes
async function printByFolders(orderId, orderData) {
    const assignments = {};
    
    // Récupérer les sélections
    orderData.folders.forEach((folder, index) => {
        const select = document.getElementById(`printer-folder-${index}`);
        const printerName = select.value;
        
        if (printerName) {
            if (!assignments[printerName]) {
                assignments[printerName] = [];
            }
            assignments[printerName].push(folder);
        }
    });
    
    if (Object.keys(assignments).length === 0) {
        showNotification('Selecciona al menos una impresora', 'error');
        return;
    }
    
    document.querySelector('.fixed').remove();
    
    // Envoyer chaque groupe vers son imprimante
    for (const [printerName, folders] of Object.entries(assignments)) {
        const printData = {
            printer_name: printerName,
            folders: folders,
            order_id: orderId
        };
        
        try {
            showNotification(`Enviando ${folders.length} carpeta(s) a ${printerName}...`, 'info');
            
            const response = await fetch(`${LOCAL_PRINT_SERVER}/print-order`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(printData)
            });
            
            const result = await response.json();
            if (result.success) {
                showNotification(`✅ ${folders.length} carpeta(s) enviada(s) a ${printerName}`, 'success');
            }
        } catch (error) {
            showNotification(`Error enviando a ${printerName}`, 'error');
        }
    }
}
// Marquer commande comme imprimée
async function updateOrderStatus(orderId, newStatus) {
    try {
        await fetch('api/update-order-status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order_id: orderId, status: newStatus})
        });
        
        // Actualiser la page après 2 secondes
        setTimeout(() => location.reload(), 2000);
    } catch (error) {
        console.error('Status update error:', error);
    }
}

// Système de notifications
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}


    </script>

</body>
</html>