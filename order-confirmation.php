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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 min-h-screen">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-200/50 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="flex items-center space-x-2 group">
                        <div class="p-2 bg-blue-500 rounded-lg group-hover:bg-blue-600 transition-colors">
                            <i class="fas fa-print text-white text-lg"></i>
                        </div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Copisteria</h1>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="orders.php" class="flex items-center space-x-2 text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-list text-sm"></i>
                        <span>Mis pedidos</span>
                    </a>
                    <a href="account.php" class="text-gray-600 hover:text-gray-800 transition-colors">Mi cuenta</a>
                    <div class="flex items-center space-x-2 text-gray-600">
                        <i class="fas fa-user-circle text-lg"></i>
                        <span>Hola, <?= htmlspecialchars($user['first_name']) ?></span>
                    </div>
                    <a href="logout.php" class="text-red-600 hover:text-red-800 transition-colors">Salir</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Success Hero Section -->
        <div class="text-center mb-12 relative">
            <div class="absolute inset-0 -z-10">
                <div class="h-64 bg-gradient-to-r from-green-400 via-blue-500 to-purple-600 opacity-10 rounded-3xl blur-3xl"></div>
            </div>
            
            <div class="relative">
                <div class="mx-auto flex items-center justify-center h-32 w-32 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 mb-6 animate-bounce-slow shadow-2xl">
                    <i class="fas fa-check-circle text-white text-5xl"></i>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-gray-900 via-blue-900 to-indigo-900 bg-clip-text text-transparent mb-4">
                    ¡Pedido Confirmado!
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Tu pedido ha sido procesado correctamente y está en preparación
                </p>
                <div class="mt-6 flex items-center justify-center space-x-2 text-green-600">
                    <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium">Estado: En procesamiento</span>
                    <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-12">
            
            <!-- Left Column: Payment Instructions -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
                    <div class="text-center mb-6">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 mb-4 shadow-lg">
                            <i class="fas fa-university text-white text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Instrucciones de Pago</h3>
                        <p class="text-gray-600">Completa tu transferencia bancaria</p>
                    </div>
                    
                    <!-- Datos Bancarios -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 mb-6 border border-blue-100">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-bank text-blue-500 mr-3"></i>
                            Datos Bancarios
                        </h4>
                        
                        <div class="space-y-4">
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Titular</label>
                                <div class="text-lg font-bold text-gray-900">Copistería España S.L.</div>
                            </div>
                            
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Banco</label>
                                <div class="text-lg font-bold text-gray-900">Banco Santander</div>
                            </div>
                            
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <label class="block text-sm font-medium text-gray-600 mb-2">IBAN</label>
                                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-3 hover:border-blue-300 transition-colors">
                                    <div class="font-mono text-lg font-bold text-gray-900 tracking-wider break-all">
                                        ES91 2100 0418 4502 0005 1332
                                    </div>
                                    <button onclick="copyToClipboard('ES91 2100 0418 4502 0005 1332')" 
                                            class="mt-3 w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                                        <i class="fas fa-copy mr-2"></i>Copiar IBAN
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Datos Importantes -->
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-5">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>
                            Datos Importantes
                        </h4>
                        
                        <div class="space-y-4">
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Importe a transferir</label>
                                <div class="text-3xl font-bold text-green-600" id="transfer-amount">€<?= number_format($order_info['total_price'] ?? 0, 2) ?></div>
                            </div>
                            
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Concepto obligatorio</label>
                                <div class="bg-yellow-50 border-2 border-dashed border-yellow-300 rounded-lg p-3 hover:border-yellow-400 transition-colors">
                                    <div class="font-mono text-lg font-bold text-gray-900">
                                        Código de retrait: <span id="transfer-pickup-code" class="text-red-600"><?= htmlspecialchars($order_info['pickup_code'] ?? '') ?></span>
                                    </div>
                                    <button onclick="copyPickupCode()" 
                                            class="mt-3 w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                                        <i class="fas fa-copy mr-2"></i>Copiar concepto
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-info-circle text-red-500 mt-0.5"></i>
                                <p class="text-sm text-red-800">
                                    <strong>Obligatorio:</strong> Incluye el código de retrait en el concepto para identificar tu pago automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
                    
                    <!-- Order Header -->
                    <div class="border-b border-gray-200 pb-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 mb-1">
                                    Pedido <span id="order-number" class="text-blue-600">#<?= htmlspecialchars($order_info['order_number'] ?? '') ?></span>
                                </h2>
                                <p class="text-sm text-gray-500 flex items-center">
                                    <i class="fas fa-calendar mr-2"></i>
                                    <?= date('d/m/Y H:i', strtotime($order_info['created_at'] ?? 'now')) ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2 animate-pulse"></div>
                                    Pendiente
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Pickup Code Highlight -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 text-center mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2 flex items-center justify-center">
                            <i class="fas fa-qrcode mr-2"></i>
                            Código de Recogida
                        </h3>
                        <div class="text-4xl font-bold text-blue-600 font-mono mb-2" id="pickup-code">
                            <?= htmlspecialchars($order_info['pickup_code'] ?? '') ?>
                        </div>
                        <p class="text-xs text-gray-500">Presenta este código en la tienda</p>
                        <button onclick="copyPickupCode()" class="mt-3 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-copy mr-2"></i>Copiar código
                        </button>
                    </div>

                    <!-- Order Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-gray-900" id="order-files-count">-</div>
                            <div class="text-sm text-gray-600">Archivos</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-gray-900" id="order-pages-count">-</div>
                            <div class="text-sm text-gray-600">Páginas</div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600 flex items-center">
                                <i class="fas fa-credit-card mr-2"></i>
                                Método de pago
                            </span>
                            <span class="font-medium text-blue-600" id="payment-method">Transferencia bancaria</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600 flex items-center">
                                <i class="fas fa-clock mr-2"></i>
                                Estado del pago
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pendiente
                            </span>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="border-t border-gray-200 pt-4">
                        <div class="space-y-3">
                            <div class="flex justify-between text-lg">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold" id="order-subtotal">€<?= number_format($order_info['total_price'] ?? 0, 2) ?></span>
                            </div>
                            <div class="flex justify-between text-xl font-bold border-t border-gray-200 pt-3">
                                <span>Total:</span>
                                <span class="text-green-600" id="order-total">€<?= number_format($order_info['total_price'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Files Details Section -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border border-gray-100">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-folder-open mr-3 text-blue-500"></i>
                Archivos del Pedido
            </h3>
            <div id="order-files" class="space-y-4">
                <!-- Files will be loaded by JavaScript -->
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Cargando archivos...</p>
                </div>
            </div>
        </div>

        <!-- Contact & Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Contact Info -->
            <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-2xl p-8 text-center border border-gray-200">
                <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-headset text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">¿Necesitas Ayuda?</h3>
                <p class="text-gray-600 mb-6">Nuestro equipo está listo para asistirte</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="tel:+34900123456" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-phone mr-2"></i>
                        Llamar Ahora
                    </a>
                    <a href="mailto:info@copisteria.com" class="inline-flex items-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-envelope mr-2"></i>
                        Enviar Email
                    </a>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white rounded-2xl p-8 text-center shadow-xl border border-gray-100">
                <div class="mx-auto w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-rocket text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">¿Qué quieres hacer ahora?</h3>
                <p class="text-gray-600 mb-6">Gestiona tus pedidos o crea uno nuevo</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="orders.php" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-list mr-2"></i>
                        Ver Mis Pedidos
                    </a>
                    <a href="index.php" class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Nuevo Pedido
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('IBAN copiado al portapapeles', 'success');
            });
        }

        function copyPickupCode() {
            const pickupCode = document.getElementById('transfer-pickup-code').textContent || document.getElementById('pickup-code').textContent;
            const concept = `Código de retrait: ${pickupCode}`;
            navigator.clipboard.writeText(concept).then(() => {
                showNotification('Concepto copiado al portapapeles', 'success');
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-2xl transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-blue-500'
            } text-white border border-white/20`;
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

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
                    document.getElementById('transfer-pickup-code').textContent = orderData.pickup_code;
                }
                
                if (orderData.total_price) {
                    const formattedPrice = '€' + orderData.total_price.toFixed(2);
                    document.getElementById('order-total').textContent = formattedPrice;
                    document.getElementById('order-subtotal').textContent = formattedPrice;
                    document.getElementById('transfer-amount').textContent = formattedPrice;
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
            document.getElementById('order-files-count').textContent = totalFiles;
            document.getElementById('order-pages-count').textContent = totalPages;
            
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