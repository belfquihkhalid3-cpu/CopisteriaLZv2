<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/user_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();

// Récupérer l'ID de la commande
$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Récupérer les détails de la commande
$order = fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $_SESSION['user_id']]);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Récupérer les items de la commande
$order_items = fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$order_id]);

// Récupérer les notifications liées à cette commande
$notifications = fetchAll("SELECT * FROM notifications WHERE order_id = ? ORDER BY created_at DESC", [$order_id]);

// Décoder la configuration d'impression
$print_config = json_decode($order['print_config'], true) ?: [];

// Calculer des statistiques
$total_files = count($order_items);
$unique_files = array_unique(array_column($order_items, 'file_original_name'));
$total_unique_files = count($unique_files);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado del Pedido #<?= htmlspecialchars($order['order_number']) ?> - Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen">

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
                    <a href="orders.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a Pedidos
                    </a>
                    <a href="account.php" class="text-gray-600 hover:text-gray-800">Mi cuenta</a>
                    <span class="text-gray-600">Hola, <?= htmlspecialchars($user['first_name']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600">Inicio</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="orders.php" class="text-gray-700 hover:text-blue-600">Mis Pedidos</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-gray-500">#<?= htmlspecialchars($order['order_number']) ?></span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Colonne principale -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Order Header -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                                Pedido #<?= htmlspecialchars($order['order_number']) ?>
                            </h1>
                            <p class="text-gray-600">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Creado el <?= date('d/m/Y', strtotime($order['created_at'])) ?> a las <?= date('H:i', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <?php
                            $status_classes = [
                                'DRAFT' => 'bg-gray-100 text-gray-800',
                                'PENDING' => 'bg-yellow-100 text-yellow-800',
                                'CONFIRMED' => 'bg-blue-100 text-blue-800',
                                'PROCESSING' => 'bg-purple-100 text-purple-800',
                                'PRINTING' => 'bg-indigo-100 text-indigo-800',
                                'READY' => 'bg-green-100 text-green-800',
                                'COMPLETED' => 'bg-green-200 text-green-900',
                                'CANCELLED' => 'bg-red-100 text-red-800'
                            ];
                            
                            $status_labels = [
                                'DRAFT' => 'Borrador',
                                'PENDING' => 'Pendiente',
                                'CONFIRMED' => 'Confirmado',
                                'PROCESSING' => 'En Proceso',
                                'PRINTING' => 'Imprimiendo',
                                'READY' => 'Listo',
                                'COMPLETED' => 'Completado',
                                'CANCELLED' => 'Cancelado'
                            ];
                            
                            $status_icons = [
                                'DRAFT' => 'fa-edit',
                                'PENDING' => 'fa-clock',
                                'CONFIRMED' => 'fa-check',
                                'PROCESSING' => 'fa-cog',
                                'PRINTING' => 'fa-print',
                                'READY' => 'fa-check-circle',
                                'COMPLETED' => 'fa-check-double',
                                'CANCELLED' => 'fa-times'
                            ];
                            
                            $current_status = $order['status'];
                            $status_class = $status_classes[$current_status] ?? 'bg-gray-100 text-gray-800';
                            $status_label = $status_labels[$current_status] ?? $current_status;
                            $status_icon = $status_icons[$current_status] ?? 'fa-question';
                            ?>
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?= $status_class ?>">
                                <i class="fas <?= $status_icon ?> mr-2"></i>
                                <?= $status_label ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($order['pickup_code']): ?>
                    <!-- Pickup Code (si status READY) -->
                    <?php if ($order['status'] === 'READY'): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">¡Tu pedido está listo!</h3>
                                <div class="text-sm text-green-700 mt-1">
                                    <p>Presenta este código en nuestra tienda para recoger tu pedido:</p>
                                    <div class="text-2xl font-mono font-bold text-green-900 mt-2 tracking-wider">
                                        <?= htmlspecialchars($order['pickup_code']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php elseif (in_array($order['status'], ['PENDING', 'CONFIRMED', 'PROCESSING', 'PRINTING'])): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Código de recogida</h3>
                                <div class="text-sm text-blue-700 mt-1">
                                    <p>Tu código de recogida: <span class="font-mono font-bold"><?= htmlspecialchars($order['pickup_code']) ?></span></p>
                                    <p class="text-xs mt-1">Lo necesitarás cuando tu pedido esté listo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Order Progress -->
               <!-- Order Progress -->
<!-- Order Progress -->
<div class="mb-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Progreso del Pedido</h3>
    
    <div class="relative">
        <!-- Progress Bar Background -->
        <div class="absolute top-5 left-0 w-full h-1 bg-gray-200 rounded-full"></div>
        
        <!-- Progress Bar Fill -->
        <?php
        $all_statuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'READY', 'COMPLETED'];
        $current_index = array_search($order['status'], $all_statuses);
        $progress_percentage = ($current_index / (count($all_statuses) - 1)) * 100;
        ?>
        <div class="absolute top-5 left-0 h-1 bg-blue-600 rounded-full transition-all duration-500" style="width: <?= $progress_percentage ?>%"></div>
        
        <!-- Steps -->
        <div class="relative flex justify-between">
            <?php foreach ($all_statuses as $index => $status): ?>
                <?php
                $is_current = ($status === $order['status']);
                $is_completed = ($index <= $current_index);
                
                $circle_class = $is_completed ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-400';
                $label_class = $is_current ? 'text-blue-600 font-semibold' : ($is_completed ? 'text-gray-700' : 'text-gray-400');
                ?>
                
                <div class="flex flex-col items-center">
                    <!-- Circle -->
                    <div class="w-10 h-10 rounded-full border-2 <?= $circle_class ?> flex items-center justify-center mb-3 transition-all duration-300 <?= $is_current ? 'ring-4 ring-blue-100 scale-110' : '' ?>">
                        <?php if ($is_completed && !$is_current): ?>
                            <i class="fas fa-check text-sm"></i>
                        <?php elseif ($is_current): ?>
                            <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                        <?php else: ?>
                            <span class="text-sm font-medium"><?= $index + 1 ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Label -->
                    <div class="text-center">
                        <div class="text-sm <?= $label_class ?> mb-1">
                            <?= $status_labels[$status] ?>
                        </div>
                        <?php if ($is_current): ?>
                            <div class="text-xs text-blue-500">
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                En proceso
                            </div>
                        <?php elseif ($is_completed): ?>
                            <div class="text-xs text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>
                                Completado
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Current Status Info -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 text-lg mr-3"></i>
                <div>
                    <div class="font-medium text-blue-900">
                        Estado actual: <?= $status_labels[$order['status']] ?>
                    </div>
                    <div class="text-sm text-blue-700 mt-1">
                        <?php
                        $status_messages = [
                            'PENDING' => 'Tu pedido está siendo revisado por nuestro equipo',
                            'CONFIRMED' => 'Pedido confirmado y en cola de producción',
                            'PROCESSING' => 'Estamos preparando tus documentos',
                            'READY' => '¡Tu pedido está listo para recoger!',
                            'COMPLETED' => 'Pedido entregado y finalizado'
                        ];
                        echo $status_messages[$order['status']] ?? 'Procesando tu solicitud';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                    <!-- Order Summary -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900"><?= $order['total_files'] ?></div>
                            <div class="text-xs text-gray-600">Archivos</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900"><?= $order['total_pages'] ?></div>
                            <div class="text-xs text-gray-600">Páginas</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                           <div class="text-2xl font-bold text-blue-600"><?= number_format($order['total_price'] * $order['total_pages'], 2) ?>€</div>
<div class="text-xs text-gray-600">Total</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900"><?= ucfirst(str_replace('_', ' ', strtolower($order['payment_method']))) ?></div>
                            <div class="text-xs text-gray-600">Pago</div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Archivos del Pedido
                        <span class="text-sm text-gray-500 ml-2">(<?= count($order_items) ?> items)</span>
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archivo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Configuración</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Páginas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copias</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($item['file_original_name']) ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?= number_format($item['file_size'] / 1024, 1) ?> KB
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <div class="space-y-1">
                                            <div><?= htmlspecialchars($item['paper_size']) ?> - <?= htmlspecialchars($item['paper_weight']) ?></div>
                                            <div>
                                                <?= $item['color_mode'] === 'BW' ? 'Blanco y Negro' : 'Color' ?> - 
                                                <?= $item['sides'] === 'SINGLE' ? 'Una cara' : 'Doble cara' ?>
                                            </div>
                                            <?php if ($item['binding'] && $item['binding'] !== 'NONE'): ?>
                                            <div class="text-xs">
                                                Encuadernado: <?= htmlspecialchars($item['binding']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= $item['page_count'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= $item['copies'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= number_format($item['item_total'], 2) ?>€
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Configuration Details -->
             <!-- Configuration Details -->
<?php if (!empty($print_config)): ?>
<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Configuración del Pedido</h3>
    
    <?php if (isset($print_config['folders']) && is_array($print_config['folders'])): ?>
        <?php foreach ($print_config['folders'] as $index => $folder): ?>
        <div class="border border-gray-200 rounded-lg p-4 mb-4">
            <h4 class="font-medium text-gray-800 mb-3">
                <?= htmlspecialchars($folder['name'] ?? "Carpeta " . ($index + 1)) ?>
                <span class="text-sm text-gray-500 ml-2">(<?= $folder['copies'] ?? 1 ?> copias)</span>
            </h4>
            
            <?php if (isset($folder['configuration'])): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Color:</span>
                    <span class="font-medium"><?= $folder['configuration']['colorMode'] === 'bw' ? 'Blanco y Negro' : 'Color' ?></span>
                </div>
                <div>
                    <span class="text-gray-600">Papel:</span>
                    <span class="font-medium"><?= htmlspecialchars($folder['configuration']['paperSize'] ?? 'A4') ?></span>
                </div>
                <div>
                    <span class="text-gray-600">Grosor:</span>
                    <span class="font-medium"><?= htmlspecialchars($folder['configuration']['paperWeight'] ?? '80g') ?></span>
                </div>
                <div>
                    <span class="text-gray-600">Caras:</span>
                    <span class="font-medium"><?= $folder['configuration']['sides'] === 'single' ? 'Una cara' : 'Doble cara' ?></span>
                </div>
                <div>
                    <span class="text-gray-600">Orientación:</span>
                    <span class="font-medium"><?= $folder['configuration']['orientation'] === 'portrait' ? 'Vertical' : 'Horizontal' ?></span>
                </div>
                <div>
                    <span class="text-gray-600">Acabado:</span>
                    <span class="font-medium">
                        <?php
                        $finishing = $folder['configuration']['finishing'] ?? 'none';
                        $finishingLabels = [
                            'individual' => 'Individual',
                            'grouped' => 'Agrupado', 
                            'none' => 'Sin acabado',
                            'spiral' => 'Encuadernado',
                            'staple' => 'Grapado',
                            'laminated' => 'Plastificado',
                            'perforated2' => 'Perforado 2',
                            'perforated4' => 'Perforado 4'
                        ];
                        echo $finishingLabels[$finishing] ?? ucfirst($finishing);
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($print_config['paymentMethod'])): ?>
    <div class="border-t pt-4 mt-4">
        <div class="text-sm">
            <span class="text-gray-600">Método de pago:</span>
            <span class="font-medium"><?= htmlspecialchars($print_config['paymentMethod']['title'] ?? 'No especificado') ?></span>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Acciones Rápidas</h3>
                    <div class="space-y-3">
                        <?php if ($order['status'] === 'READY'): ?>
                        <button class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-store mr-2"></i>
                            Marcar como Recogido
                        </button>
                        <?php endif; ?>
                        
                        <a href="orders.php" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-block text-center">
                            <i class="fas fa-list mr-2"></i>
                            Ver Todos los Pedidos
                        </a>
                        
                        <a href="index.php" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors inline-block text-center">
                            <i class="fas fa-plus mr-2"></i>
                            Nuevo Pedido
                        </a>
                        
                        <?php if (in_array($order['status'], ['PENDING', 'CONFIRMED'])): ?>
                        <button onclick="cancelOrder()" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Cancelar Pedido
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información de Pago</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Método:</span>
                            <span class="font-medium">
                                <?= $order['payment_method'] === 'ON_SITE' ? 'Pago en tienda' : ucfirst(str_replace('_', ' ', strtolower($order['payment_method']))) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estado:</span>
                            <?php
                            $payment_status_classes = [
                                'PENDING' => 'text-yellow-600',
                                'PAID' => 'text-green-600',
                                'FAILED' => 'text-red-600',
                                'REFUNDED' => 'text-blue-600'
                            ];
                            $payment_status_labels = [
                                'PENDING' => 'Pendiente',
                                'PAID' => 'Pagado',
                                'FAILED' => 'Fallido',
                                'REFUNDED' => 'Reembolsado'
                            ];
                            $payment_class = $payment_status_classes[$order['payment_status']] ?? 'text-gray-600';
                            $payment_label = $payment_status_labels[$order['payment_status']] ?? $order['payment_status'];
                            ?>
                            <span class="font-medium <?= $payment_class ?>">
                                <?= $payment_label ?>
                            </span>
                        </div>
                     <div class="flex justify-between border-t pt-3">
    <span class="text-gray-900 font-medium">Total:</span>
    <span class="font-bold text-lg"><?= number_format($order['total_price'] * $order['total_pages'], 2) ?>€</span>
</div>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if (!empty($notifications)): ?>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Historial
                        <span class="text-sm text-gray-500 ml-2">(<?= count($notifications) ?>)</span>
                    </h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php foreach ($notifications as $notif): ?>
                        <div class="flex items-start space-x-3 p-3 <?= $notif['is_read'] ? 'bg-gray-50' : 'bg-blue-50' ?> rounded-lg">
                            <div class="flex-shrink-0">
                                <i class="fas fa-bell text-blue-500 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($notif['title']) ?>
                                </div>
                                <div class="text-xs text-gray-600 mt-1">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </div>
                                <div class="text-xs text-gray-500 mt-2">
                                    <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contact Info -->
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">¿Necesitas Ayuda?</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Si tienes preguntas sobre tu pedido, contacta con nosotros
                    </p>
                    <div class="space-y-2">
                        <a href="tel:+34900123456" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-phone mr-2"></i>
                            +34 900 123 456
                        </a>
                        <br>
                        <a href="mailto:info@copisteria.com" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-envelope mr-2"></i>
                            info@copisteria.com
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script>
        function cancelOrder() {
            if (confirm('¿Estás seguro de que quieres cancelar este pedido?')) {
                // Aquí implementarías la cancelación via AJAX
                alert('Función de cancelación pendiente de implementar');
            }
        }

        // Auto-refresh pour les statuts en temps réel (optionnel)
        // setInterval(() => {
        //     location.reload();
        // }, 30000); // Refresh cada 30 segundos
    </script>

</body>
</html>