<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/user_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();

// Récupérer les données de confirmation depuis la session
$order_info = null;
if (isset($_GET['order_id'])) {
    // Si on vient directement avec un order_id
    $order_id = intval($_GET['order_id']);
    $order_info = fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $_SESSION['user_id']]);
} else {
    // Données temporaires depuis sessionStorage (seront chargées par JavaScript)
    $order_info = [
        'order_number' => 'Loading...',
        'pickup_code' => 'Loading...',
        'total_price' => 0,
        'status' => 'PENDING'
    ];
}

if (!$order_info) {
    header('Location: orders.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Copisteria</title>
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
                    <a href="orders.php" class="text-blue-600 hover:text-blue-800">Mis pedidos</a>
                    <a href="account.php" class="text-gray-600 hover:text-gray-800">Mi cuenta</a>
                    <span class="text-gray-600">Hola, <?= htmlspecialchars($user['first_name']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Success Message -->
        <div class="text-center mb-12">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6">
                <i class="fas fa-check-circle text-green-600 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">¡Pedido Confirmado!</h1>
            <p class="text-lg text-gray-600">Tu pedido ha sido procesado correctamente</p>
        </div>

        <!-- Order Summary Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            
            <!-- Order Header -->
            <div class="border-b border-gray-200 pb-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Pedido <span id="order-number">#<?= htmlspecialchars($order_info['order_number'] ?? '') ?></span>
                        </h2>
                        <p class="text-gray-600">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span id="order-date"><?= date('d/m/Y H:i') ?></span>
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-2"></i>
                            <span id="order-status">Pendiente</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pickup Code -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-6 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-qrcode text-blue-400 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-blue-900">Código de Recogida</h3>
                        <p class="text-blue-700 mb-2">Presenta este código cuando recojas tu pedido</p>
                        <div class="text-3xl font-mono font-bold text-blue-900 tracking-wider" id="pickup-code">
                            <?= htmlspecialchars($order_info['pickup_code'] ?? '') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Detalles del Pedido</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Carpetas:</span>
                            <span class="font-medium" id="total-folders">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Archivos:</span>
                            <span class="font-medium" id="total-files">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Páginas totales:</span>
                            <span class="font-medium" id="total-pages">-</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Información de Pago</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Método de pago:</span>
                            <span class="font-medium" id="payment-method">Pago en tienda</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estado del pago:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pendiente
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pricing -->
            <div class="border-t border-gray-200 pt-6">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal:</span>
                        <span id="order-subtotal">0,00 €</span>
                    </div>
                    <div class="flex justify-between text-sm" id="discount-row" style="display: none;">
                        <span class="text-gray-600">Descuento (<span id="discount-code-display"></span>):</span>
                        <span class="text-green-600" id="discount-amount-display">-0,00 €</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                        <span>Total:</span>
                        <span id="order-total">0,00 €</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Files Details -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Archivos del Pedido</h3>
            <div id="order-files" class="space-y-4">
                <!-- Files will be loaded by JavaScript -->
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Próximos Pasos</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                        <i class="fas fa-print text-blue-600 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">1. Procesamiento</h4>
                    <p class="text-sm text-gray-600">Estamos preparando tu pedido para impresión</p>
                </div>

                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <i class="fas fa-bell text-green-600 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">2. Notificación</h4>
                    <p class="text-sm text-gray-600">Te avisaremos cuando esté listo para recoger</p>
                </div>

                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 mb-4">
                        <i class="fas fa-store text-purple-600 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">3. Recogida</h4>
                    <p class="text-sm text-gray-600">Presenta tu código en nuestra tienda</p>
                </div>

            </div>
        </div>

        <!-- Contact Info -->
        <div class="bg-gray-100 rounded-2xl p-8 text-center">
            <h3 class="text-xl font-bold text-gray-900 mb-4">¿Necesitas Ayuda?</h3>
            <p class="text-gray-600 mb-6">Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="tel:+34900123456" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-phone mr-2"></i>
                    Llamar
                </a>
                <a href="mailto:info@copisteria.com" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-envelope mr-2"></i>
                    Email
                </a>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-8">
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="orders.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-list mr-2"></i>
                    Ver Mis Pedidos
                </a>
                <a href="index.php" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-plus mr-2"></i>
                    Nuevo Pedido
                </a>
            </div>
        </div>

    </div>

    <script>
        // Charger les détails de la commande depuis sessionStorage
        document.addEventListener('DOMContentLoaded', function() {
            const orderConfirmation = sessionStorage.getItem('orderConfirmation');
            
            if (orderConfirmation) {
                const orderData = JSON.parse(orderConfirmation);
                console.log('Order confirmation data:', orderData);
                
                // Mettre à jour les informations de base
                if (orderData.order_number) {
                    document.getElementById('order-number').textContent = '#' + orderData.order_number;
                }
                
                if (orderData.pickup_code) {
                    document.getElementById('pickup-code').textContent = orderData.pickup_code;
                }
                
                if (orderData.total_price) {
                    document.getElementById('order-total').textContent = orderData.total_price.toFixed(2).replace('.', ',') + ' €';
                    document.getElementById('order-subtotal').textContent = orderData.total_price.toFixed(2).replace('.', ',') + ' €';
                }
                
                // Si il y a des données de dossiers dans sessionStorage
                const cartData = sessionStorage.getItem('currentCart');
                if (cartData) {
                    const cart = JSON.parse(cartData);
                    displayOrderDetails(cart.folders || []);
                }
                
                // Nettoyer sessionStorage après affichage
                sessionStorage.removeItem('orderConfirmation');
            }
        });
        
        function displayOrderDetails(folders) {
            let totalFiles = 0;
            let totalPages = 0;
            
            folders.forEach(folder => {
                totalFiles += folder.files ? folder.files.length : 0;
                folder.files?.forEach(file => {
                    totalPages += (file.pages || 1) * (folder.copies || 1);
                });
            });
            
            // Mettre à jour les totaux
            document.getElementById('total-folders').textContent = folders.length;
            document.getElementById('total-files').textContent = totalFiles;
            document.getElementById('total-pages').textContent = totalPages;
            
            // Afficher les fichiers
            const filesContainer = document.getElementById('order-files');
            filesContainer.innerHTML = '';
            
            folders.forEach((folder, folderIndex) => {
                const folderDiv = document.createElement('div');
                folderDiv.className = 'border border-gray-200 rounded-lg p-4';
                
                folderDiv.innerHTML = `
                    <h4 class="font-medium text-gray-900 mb-3">
                        ${folder.name || `Carpeta ${folderIndex + 1}`}
                        <span class="text-sm text-gray-500 ml-2">(${folder.copies} copias)</span>
                    </h4>
                    <div class="space-y-2">
                        ${folder.files?.map(file => `
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-file-pdf text-red-500"></i>
                                    <div>
                                        <div class="font-medium text-sm">${file.name}</div>
                                        <div class="text-xs text-gray-500">${formatFileSize(file.size)} • ${file.pages || 1} páginas</div>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600">
                                    ${file.pages || 1} × ${folder.copies} = ${(file.pages || 1) * folder.copies} páginas
                                </div>
                            </div>
                        `).join('') || '<div class="text-gray-500 text-sm">Sin archivos</div>'}
                    </div>
                `;
                
                filesContainer.appendChild(folderDiv);
            });
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>

</body>
</html>