<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$order_id = $_GET['id'] ?? 0;
$token = $_GET['token'] ?? '';

if (!$order_id || !$token) {
    header('Location: orders');
    exit();
}

if (!$order_id) {
    error_log("Accès non autorisé - User: {$_SESSION['user_id']}, Order: {$order_id}");
    header('Location: orders?error=access_denied');
    exit();
}

$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$order_id]);
if (!$order) {
    header('Location: orders.php');
    exit();
}

$order_items = fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$order_id]);
$print_config = json_decode($order['print_config'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles Pedido #<?= $order['order_number'] ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <!-- Header -->
    <div class="bg-white shadow-sm border-b p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button onclick="window.close()" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <h1 class="text-xl font-bold">Pedido #<?= htmlspecialchars($order['order_number']) ?></h1>
                <div class="status-badge-large status-<?= strtolower($order['status']) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="downloadFiles(<?= $order_id ?>)" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                    <i class="fas fa-download mr-2"></i>Descargar Archivos
                </button>
                <button onclick="printOrderSheet()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-print mr-2"></i>Hoja de Trabajo
                </button>
            </div>
        </div>
    </div>

    <div class="p-6 max-w-7xl mx-auto">
        
        <!-- Info General -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Cliente -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-user text-blue-500 mr-2"></i>Información del Cliente
                </h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-600">Nombre:</span>
                        <div class="font-medium"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">Email:</span>
                        <div class="font-medium"><?= htmlspecialchars($order['email']) ?></div>
                    </div>
                    <?php if ($order['phone']): ?>
                    <div>
                        <span class="text-gray-600">Teléfono:</span>
                        <div class="font-medium"><?= htmlspecialchars($order['phone']) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="pt-2">
                        <a href="mailto:<?= htmlspecialchars($order['email']) ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-envelope mr-1"></i>Contactar Cliente
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pedido -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-shopping-cart text-green-500 mr-2"></i>Detalles del Pedido
                </h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-600">Código de recogida:</span>
                        <div class="font-mono font-bold text-lg bg-gray-100 px-3 py-1 rounded"><?= htmlspecialchars($order['pickup_code']) ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">Archivos:</span>
                        <div class="font-medium"><?= $order['total_files'] ?> archivos</div>
                    </div>
                    <div>
                        <span class="text-gray-600">Páginas totales:</span>
                        <div class="font-medium"><?= $order['total_pages'] ?> páginas</div>
                    </div>
                    <div>
                        <span class="text-gray-600">Método de pago:</span>
                        <div class="font-medium"><?= $order['payment_method'] === 'ON_SITE' ? 'Pago en tienda' : $order['payment_method'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Estado y Acciones -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    <i class="fas fa-cogs text-purple-500 mr-2"></i>Estado y Acciones
                </h3>
                
                <!-- Cambiar Estado -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cambiar Estado</label>
                    <select onchange="updateStatus(this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="PENDING" <?= $order['status'] === 'PENDING' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="CONFIRMED" <?= $order['status'] === 'CONFIRMED' ? 'selected' : '' ?>>Confirmado</option>
                        <option value="PROCESSING" <?= $order['status'] === 'PROCESSING' ? 'selected' : '' ?>>En Proceso</option>
                        <option value="READY" <?= $order['status'] === 'READY' ? 'selected' : '' ?>>Listo</option>
                        <option value="COMPLETED" <?= $order['status'] === 'COMPLETED' ? 'selected' : '' ?>>Completado</option>
                        <option value="CANCELLED" <?= $order['status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>

                <!-- Fechas importantes -->
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-600">Creado:</span>
                        <div class="font-medium"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                    <?php if ($order['completed_at']): ?>
                    <div>
                        <span class="text-gray-600">Completado:</span>
                        <div class="font-medium"><?= date('d/m/Y H:i', strtotime($order['completed_at'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Total -->
                <div class="mt-4 pt-4 border-t">
                    <div class="text-2xl font-bold text-green-600">€<?= number_format($order['total_price'], 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Archivos del Pedido -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-files-o text-blue-500 mr-2"></i>
                    Archivos del Pedido (<?= count($order_items) ?>)
                </h3>
                <div class="flex space-x-2">
                    <button onclick="downloadFiles(<?= $order_id ?>)" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 text-sm">
                        <i class="fas fa-download mr-2"></i>Descargar Todo
                    </button>
                    <button onclick="previewFiles()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-sm">
                        <i class="fas fa-eye mr-2"></i>Vista Previa
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Configuración</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Páginas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Copias</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($order_items as $index => $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= $index + 1 ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <i class="fas fa-file-pdf text-red-500 mr-3 text-lg"></i>
                                    <div>
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($item['file_original_name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= number_format($item['file_size'] / 1024, 1) ?> KB</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"><?= $item['paper_size'] ?></span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full"><?= $item['paper_weight'] ?></span>
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full"><?= $item['color_mode'] ?></span>
                                    <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full"><?= $item['sides'] ?></span>
                                    <?php if ($item['binding'] && $item['binding'] !== 'NONE'): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full"><?= $item['binding'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $item['page_count'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $item['copies'] ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">€<?= number_format($item['item_total'], 2) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="downloadSingleFile('<?= $item['file_name'] ?>', '<?= $item['file_original_name'] ?>')" 
                                            class="text-green-600 hover:text-green-900" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button onclick="previewFile('<?= $item['file_name'] ?>')" 
                                            class="text-blue-600 hover:text-blue-900" title="Vista previa">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configuración Detallada -->
        <?php if (!empty($print_config['folders'])): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-cogs text-purple-500 mr-2"></i>Configuración Original del Cliente
            </h3>
            
            <?php foreach ($print_config['folders'] as $folderIndex => $folder): ?>
            <div class="border border-gray-200 rounded-lg p-4 mb-4">
                <h4 class="font-medium text-gray-800 mb-3">
                    <?= htmlspecialchars($folder['name'] ?? "Carpeta " . ($folderIndex + 1)) ?>
                    <span class="text-sm text-gray-500 ml-2">(<?= $folder['copies'] ?? 1 ?> copias)</span>
                </h4>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-gray-600 text-xs">PAPEL</div>
                        <div class="font-medium"><?= $folder['configuration']['paperSize'] ?? 'A4' ?></div>
                        <div class="text-xs text-gray-500"><?= $folder['configuration']['paperWeight'] ?? '80g' ?></div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-gray-600 text-xs">COLOR</div>
                        <div class="font-medium"><?= $folder['configuration']['colorMode'] === 'bw' ? 'B/N' : 'COLOR' ?></div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-gray-600 text-xs">CARAS</div>
                        <div class="font-medium"><?= $folder['configuration']['sides'] === 'single' ? 'Una' : 'Doble' ?></div>
                    </div>
                   <div class="bg-gray-50 p-3 rounded">
    <div class="text-gray-600 text-xs">ACABADO</div>
    <div class="font-medium">
        <?php
        $finishing = $folder['configuration']['finishing'] ?? 'none';
        $spiralColor = $folder['configuration']['spiralColor'] ?? null;
        
        $finishingLabels = [
            'individual' => 'Individual', 
            'grouped' => 'Agrupado', 
            'none' => 'Sin acabado',
            'spiral' => 'Encuadernado', 
            'staple' => 'Grapado', 
            'laminated' => 'Plastificado'
        ];
        
        $label = $finishingLabels[$finishing] ?? $finishing;
        
        // Si c'est spiral, ajouter la couleur
        if ($finishing === 'spiral' && $spiralColor) {
            $colorText = $spiralColor === 'black' ? 'Negro' : 'Blanco';
            $label .= " ($colorText)";
        }
        
        echo $label;
        ?>
    </div>
</div>
                </div>
                
                <?php if (!empty($folder['comments'])): ?>
                <div class="mt-3 p-3 bg-yellow-50 border-l-4 border-yellow-400">
                    <div class="text-xs text-gray-600">COMENTARIOS DEL CLIENTE:</div>
                    <div class="font-medium text-gray-800"><?= nl2br(htmlspecialchars($folder['comments'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Historial de Estado -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-history text-indigo-500 mr-2"></i>Cambiar Estado
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <button onclick="quickStatusChange('CONFIRMED')" class="status-btn status-confirmed">
                    <i class="fas fa-check mr-2"></i>Confirmar
                </button>
                <button onclick="quickStatusChange('PROCESSING')" class="status-btn status-processing">
                    <i class="fas fa-cog mr-2"></i>En Proceso
                </button>
                <button onclick="quickStatusChange('READY')" class="status-btn status-ready">
                    <i class="fas fa-check-circle mr-2"></i>Listo
                </button>
                <button onclick="quickStatusChange('COMPLETED')" class="status-btn status-completed">
                    <i class="fas fa-check-double mr-2"></i>Completado
                </button>
                <button onclick="quickStatusChange('CANCELLED')" class="status-btn status-cancelled">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
            </div>
        </div>

    </div>

    <style>
        .status-badge-large {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-btn {
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .status-btn:hover { transform: translateY(-2px); }
        .status-pending { background-color: #fbbf24; color: white; }
        .status-confirmed { background-color: #3b82f6; color: white; }
        .status-processing { background-color: #8b5cf6; color: white; }
        .status-ready { background-color: #10b981; color: white; }
        .status-completed { background-color: #059669; color: white; }
        .status-cancelled { background-color: #ef4444; color: white; }
    </style>

    <script>
        function updateStatus(newStatus) {
            if (confirm('¿Cambiar estado del pedido?')) {
                changeOrderStatus(<?= $order_id ?>, newStatus);
            }
        }

        function quickStatusChange(status) {
            changeOrderStatus(<?= $order_id ?>, status);
        }

        async function changeOrderStatus(orderId, newStatus) {
            try {
          const response = await fetch(window.location.origin + '/copisteria/admin/api/update-order-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Estado actualizado correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Error: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        function downloadFiles(orderId) {
            window.location.href = 'download-files.php?order=' + orderId;
        }

        function downloadSingleFile(fileName, originalName) {
            window.location.href = `download-single-file.php?file=${fileName}&name=${originalName}`;
        }

        function previewFile(fileName) {
            window.open(`../uploads/documents/${fileName}`, '_blank');
        }

        function printOrderSheet() {
            window.print();
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
        function viewFiles() {
    window.open(`view-files.php?order_id=<?= $order_id ?>`, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
}
    </script>
    

</body>
</html>