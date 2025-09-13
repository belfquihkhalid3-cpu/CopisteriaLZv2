<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/user_functions.php';
require_once 'includes/csrf.php';
require_once 'includes/security_headers.php';

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

$user_stats = getUserStats($_SESSION['user_id']);
$recent_orders = fetchAll(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", 
    [$_SESSION['user_id']]
);

$notifications = getUserNotifications($_SESSION['user_id'], false, 10);

// Traitement des actions POST
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      require_once 'includes/csrf.php';
    
    // Vérifier token AVANT tout traitement
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Token CSRF invalide - Action bloquée');
    }
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?";
        $stmt = executeQuery($sql, [$first_name, $last_name, $phone, $address, $_SESSION['user_id']]);
        
        if ($stmt) {
            $message = 'Perfil actualizado correctamente';
            $message_type = 'success';
            $user = getCurrentUser(); // Recharger les données
        } else {
            $message = 'Error al actualizar el perfil';
            $message_type = 'error';
        }
    }
    
  if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = 'Las contraseñas no coinciden';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres';
        $message_type = 'error';
    } else {
        // Récupérer le mot de passe actuel depuis la BDD
        $user_password = fetchOne("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
        
        if ($user_password && password_verify($current_password, $user_password['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = executeQuery($sql, [$new_hash, $_SESSION['user_id']]);
            
            if ($stmt) {
                $message = 'Contraseña cambiada correctamente';
                $message_type = 'success';
            } else {
                $message = 'Error al cambiar la contraseña';
                $message_type = 'error';
            }
        } else {
            $message = 'Contraseña actual incorrecta';
            $message_type = 'error';
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Copisteria</title>
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
                    <span class="text-gray-600">Hola, <?= htmlspecialchars($user['first_name']) ?></span>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">Nuevo pedido</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Salir</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mi Cuenta</h1>
            <p class="text-gray-600">Gestiona tu perfil y revisa tus pedidos</p>
        </div>

        <!-- Message d'alerte -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Colonne principale -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Statistiques rapides -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Resumen de actividad</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600"><?= $user_stats['total_orders'] ?></div>
                            <div class="text-sm text-gray-600">Pedidos totales</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600"><?= $user_stats['completed_orders'] ?></div>
                            <div class="text-sm text-gray-600">Completados</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600"><?= $user_stats['active_orders'] ?></div>
                            <div class="text-sm text-gray-600">En proceso</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600"><?= number_format($user_stats['total_spent'], 2) ?>€</div>
                            <div class="text-sm text-gray-600">Gastado total</div>
                        </div>
                    </div>
                </div>

                <!-- Pedidos recientes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Pedidos recientes</h2>
                        <a href="orders.php" class="text-blue-600 hover:text-blue-800 text-sm">Ver todos</a>
                    </div>
                    
                    <?php if (!empty($recent_orders)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-medium text-gray-900">
                                                Pedido #<?= htmlspecialchars($order['order_number']) ?>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                <?= $order['total_files'] ?> archivo(s) • <?= $order['total_pages'] ?> páginas
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="status-<?= strtolower($order['status']) ?>">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </div>
                                            <div class="font-semibold text-gray-900 mt-1">
                                                €<?= number_format($order['total_price'], 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex space-x-3">
                                        <a href="order-status.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                            Ver detalles
                                        </a>
                                        <?php if ($order['status'] === 'READY'): ?>
                                            <span class="text-green-600 text-sm font-medium">
                                                <i class="fas fa-check-circle mr-1"></i>Listo para recoger
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-4"></i>
                            <p>No has realizado ningún pedido aún</p>
                            <a href="index.php" class="inline-block mt-4 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                Hacer primer pedido
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Editar perfil -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Información personal</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">El email no se puede cambiar</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <textarea name="address" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            Actualizar perfil
                        </button>
                    </form>
                </div>

                <!-- Cambiar contraseña -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Cambiar contraseña</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                            <input type="password" name="new_password" required minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nueva contraseña</label>
                            <input type="password" name="confirm_password" required minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <button type="submit" name="change_password" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-colors">
                            Cambiar contraseña
                        </button>
                    </form>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Información cuenta -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de cuenta</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-gray-600">Miembro desde:</span>
                            <div class="font-medium"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                        </div>
                        <div>
                            <span class="text-gray-600">Último acceso:</span>
                            <div class="font-medium">
                                <?= $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : 'Nunca' ?>
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-600">Estado:</span>
                            <div class="font-medium text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>Activa
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enlaces rápidos -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Enlaces rápidos</h3>
                    <div class="space-y-3">
                        <a href="index.php" class="flex items-center text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Nuevo pedido
                        </a>
                        <a href="orders.php" class="flex items-center text-gray-700 hover:text-gray-900">
                            <i class="fas fa-list mr-2"></i>
                            Todos mis pedidos
                        </a>
                        <a href="cart.php" class="flex items-center text-gray-700 hover:text-gray-900">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Ver carrito
                        </a>
                        <a href="#" class="flex items-center text-gray-700 hover:text-gray-900">
                            <i class="fas fa-download mr-2"></i>
                            Descargar facturas
                        </a>
                    </div>
                </div>

                <!-- Notificaciones recientes -->
                <?php if (!empty($notifications)): ?>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Notificaciones</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                            <div class="text-sm p-3 <?= $notification['is_read'] ? 'bg-gray-50' : 'bg-blue-50' ?> rounded">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($notification['title']) ?></div>
                                <div class="text-gray-600 mt-1"><?= htmlspecialchars($notification['message']) ?></div>
                                <div class="text-xs text-gray-500 mt-2">
                                    <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        // Auto-hide success messages
        setTimeout(function() {
            const successMessages = document.querySelectorAll('.bg-green-100');
            successMessages.forEach(function(msg) {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            });
        }, 3000);
    </script>
</body>
</html>