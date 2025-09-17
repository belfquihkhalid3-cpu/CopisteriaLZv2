<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once 'config.php';

// Récupérer l'ID de la commande depuis l'URL
$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Récupérer les détails de la commande
$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$order_id]);

if (!$order) {
    header('Location: index.php');
    exit();
}

// Récupérer les items de la commande
$order_items = fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$order_id]);

// Décoder la configuration d'impression
$print_config = json_decode($order['print_config'], true) ?: [];
$folders = $print_config['folders'] ?? [];
$terminal_info = $print_config['terminal_info'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-animation {
            animation: checkmark 0.6s ease-in-out;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .pulse-bg {
            animation: pulse-bg 2s infinite;
        }
        
        @keyframes pulse-bg {
            0%, 100% { background-color: rgb(34, 197, 94); }
            50% { background-color: rgb(22, 163, 74); }
        }
        
        .print-animation {
            animation: print-slide 1s ease-in-out infinite alternate;
        }
        
        @keyframes print-slide {
            0% { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .floating { display: none !important; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 no-print">
        <div class="max-w-full px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="index" target="_blank">
  <img src="../assets/img/1.jpeg" alt="Copisteria Logo" class="h-20 w-20 object-contain">
</a>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-red-500 bg-clip-text text-transparent">
                        Tinta Expres LZ
                    </h1>
                </div>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-desktop mr-2"></i>Terminal <?= htmlspecialchars($terminal_info['name'] ?? 'Principal') ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        
        <!-- Success Animation Section -->
        <div class="text-center mb-8 no-print">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-green-500 rounded-full success-animation pulse-bg mb-4">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">¡Pedido Confirmado!</h1>
            <p class="text-gray-600 text-lg">Tu pedido ha sido procesado exitosamente</p>
        </div>

        <!-- Order Details Card -->
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-green-500 to-blue-500 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold mb-1">Pedido #<?= htmlspecialchars($order['order_number']) ?></h2>
                        <p class="text-green-100">Terminal: <?= htmlspecialchars($terminal_info['name'] ?? 'Principal') ?></p>
                        <p class="text-green-100 text-sm">
                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-green-100">Código de recogida</div>
                        <div class="text-2xl font-bold"><?= htmlspecialchars($order['pickup_code']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Info -->
            <div class="p-6 space-y-6">
                
                <!-- Customer Info -->
                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">
                            <?= htmlspecialchars(($order['first_name'] . ' ' . $order['last_name']) ?: 'Cliente Invitado') ?>
                        </h3>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($order['email'] ?: 'invitado@terminal.local') ?></p>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-file-alt mr-2 text-orange-500"></i>Resumen del pedido
                    </h3>
                    
                    <div class="space-y-3">
                        <?php if (!empty($folders)): ?>
                            <?php foreach ($folders as $index => $folder): ?>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <h4 class="font-semibold text-gray-800">
                                            <i class="fas fa-folder text-orange-500 mr-2"></i>
                                            <?= htmlspecialchars($folder['name'] ?? "Carpeta " . ($index + 1)) ?>
                                        </h4>
                                        <span class="text-sm font-medium text-blue-600">
                                            <?= intval($folder['copies'] ?? 1) ?> <?= intval($folder['copies'] ?? 1) === 1 ? 'copia' : 'copias' ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($folder['files'])): ?>
                                        <div class="space-y-2">
                                            <?php foreach ($folder['files'] as $file): ?>
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600">
                                                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                                        <?= htmlspecialchars($file['name'] ?? 'Documento') ?>
                                                    </span>
                                                    <span class="text-gray-500">
                                                        <?= intval($file['pages'] ?? 1) ?> <?= intval($file['pages'] ?? 1) === 1 ? 'página' : 'páginas' ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($folder['configuration'])): ?>
                                        <div class="mt-3 pt-3 border-t border-gray-300">
                                            <div class="text-xs text-gray-500 space-y-1">
                                                <?php 
                                                $config = $folder['configuration'];
                                                if (!empty($config['color'])): ?>
                                                    <div>Color: <?= htmlspecialchars($config['color']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($config['sides'])): ?>
                                                    <div>Caras: <?= htmlspecialchars($config['sides']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($config['orientation'])): ?>
                                                    <div>Orientación: <?= htmlspecialchars($config['orientation']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($order_items as $item): ?>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">
                                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                            <?= htmlspecialchars($item['file_name'] ?? 'Documento') ?>
                                        </span>
                                        <span class="text-gray-500">
                                            <?= intval($item['quantity']) ?> <?= intval($item['quantity']) === 1 ? 'copia' : 'copias' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Totals -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Total páginas:</span>
                            <span><?= intval($order['total_pages']) ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Total archivos:</span>
                            <span><?= intval($order['total_files']) ?></span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-gray-800 border-t border-gray-200 pt-2">
                            <span>Total:</span>
                            <span>€<?= number_format($order['total_price'], 2) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <div class="flex items-center space-x-3">
                        <?php if ($order['payment_method'] === 'STORE_PAYMENT'): ?>
                            <i class="fas fa-store text-blue-600 text-xl"></i>
                            <div>
                                <h4 class="font-semibold text-gray-800">Pago en tienda</h4>
                                <p class="text-sm text-gray-600">Efectivo o tarjeta en el mostrador</p>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-university text-blue-600 text-xl"></i>
                            <div>
                                <h4 class="font-semibold text-gray-800">Transferencia bancaria</h4>
                                <p class="text-sm text-gray-600">Recibirás instrucciones por email</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        <div>
                            <h4 class="font-semibold text-gray-800">Estado: <?= htmlspecialchars($order['status']) ?></h4>
                            <p class="text-sm text-gray-600">
                                <?php 
                                switch($order['status']) {
                                    case 'PENDING':
                                        echo 'Tu pedido está pendiente de confirmación';
                                        break;
                                    case 'CONFIRMED':
                                        echo 'Tu pedido ha sido confirmado y está en cola';
                                        break;
                                    case 'PROCESSING':
                                        echo 'Tu pedido se está procesando';
                                        break;
                                    case 'READY':
                                        echo 'Tu pedido está listo para recoger';
                                        break;
                                    case 'COMPLETED':
                                        echo 'Tu pedido ha sido completado';
                                        break;
                                    default:
                                        echo 'Estado del pedido actualizado';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-info-circle text-green-600 mr-2"></i>
                        Próximos pasos
                    </h4>
                    <ol class="text-sm text-gray-600 space-y-1">
                        <li>1. Dirígete al mostrador de atención</li>
                        <li>2. Proporciona tu código de recogida: <strong><?= htmlspecialchars($order['pickup_code']) ?></strong></li>
                        <li>3. Realiza el pago y recoge tus documentos</li>
                    </ol>
                </div>

                <!-- Print Animation -->
                <div class="text-center py-6 no-print">
                    <div class="inline-block print-animation">
                        <i class="fas fa-print text-6xl text-gray-300"></i>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Preparando tu pedido...</p>
                </div>

                <!-- Comments -->
                <?php if (!empty($order['customer_notes'])): ?>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-2">
                            <i class="fas fa-comment text-gray-600 mr-2"></i>
                            Comentarios adicionales
                        </h4>
                        <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($order['customer_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="bg-gray-50 p-6 border-t border-gray-200 no-print">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button onclick="window.print()" class="flex items-center justify-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-print mr-2"></i>
                        Imprimir recibo
                    </button>
                    <button onclick="newOrder()" class="flex items-center justify-center px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Nuevo pedido
                    </button>
                </div>
                
                <div class="mt-4 text-center">
                    <button onclick="goHome()" class="text-gray-600 hover:text-gray-800 transition-colors">
                        <i class="fas fa-home mr-2"></i>Volver al inicio
                    </button>
                </div>
            </div>
        </div>

        <!-- WhatsApp Support -->
        <div class="fixed bottom-6 right-6 z-50 floating no-print">
            <a href="https://wa.me/34932520570?text=Hola%2C%20tengo%20una%20consulta%20sobre%20mi%20pedido%20<?= urlencode($order['order_number']) ?>" 
               target="_blank"
               class="group relative flex items-center justify-center w-16 h-16 bg-green-500 hover:bg-green-600 rounded-full shadow-lg hover:shadow-xl transition-all duration-300">
                <i class="fab fa-whatsapp text-white text-2xl"></i>
                <div class="absolute right-full mr-4 top-1/2 transform -translate-y-1/2 bg-gray-800 text-white text-sm px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">
                    ¿Ayuda con tu pedido?
                </div>
            </a>
        </div>
    </main>

    <script>
        function newOrder() {
            // Limpiar datos de sesión
            sessionStorage.removeItem('orderConfirmation');
            sessionStorage.removeItem('cartData');
            sessionStorage.removeItem('currentCart');
            
            // Redirigir al inicio para nuevo pedido
            window.location.href = 'index.php';
        }

        function goHome() {
            // Limpiar datos de sesión
            sessionStorage.removeItem('orderConfirmation');
            sessionStorage.removeItem('cartData');
            sessionStorage.removeItem('currentCart');
            
            // Redirigir al inicio
            window.location.href = 'index.php';
        }

        // Auto-limpiar sessionStorage después de mostrar la confirmación
        setTimeout(() => {
            sessionStorage.removeItem('orderConfirmation');
        }, 60000); // 1 minuto
    </script>
</body>
</html>