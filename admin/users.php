<?php
session_start();
require_once 'auth.php';
require_once '../includes/csrf.php';
require_once '../includes/security_headers.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$admin = getAdminUser();

// --- POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       require_once 'includes/csrf.php';
    
    // Vérifier token AVANT tout traitement
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Token CSRF invalide - Action bloquée');
    }
    if (isset($_POST['toggle_active'])) {
        $user_id_to_toggle = $_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        executeQuery("UPDATE users SET is_active = ? WHERE id = ?", [$new_status, $user_id_to_toggle]);
        $_SESSION['success_message'] = "Estado del usuario actualizado.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    if (isset($_POST['delete_user'])) {
        $user_id_to_delete = $_POST['user_id'];
        executeQuery("DELETE FROM users WHERE id = ?", [$user_id_to_delete]);
        $_SESSION['success_message'] = "Usuario eliminado correctamente.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// --- Filtros y paginación ---
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_term = $_GET['search'] ?? '';
$search_status = $_GET['status'] ?? '';

$base_query = "FROM users u LEFT JOIN (
    SELECT user_id, 
           COUNT(*) as total_orders,
           COALESCE(SUM(total_price), 0) as total_spent,
           COALESCE(SUM(total_pages), 0) as total_pages_printed,
           MAX(created_at) as last_order_date
    FROM orders 
    WHERE status != 'CANCELLED'
    GROUP BY user_id
) os ON u.id = os.user_id";

$where_clauses = ["u.is_admin = 0"];
$params = [];

if (!empty($search_term)) {
    $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search_term . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}
if ($search_status !== '') {
    $where_clauses[] = "u.is_active = ?";
    $params[] = $search_status;
}

$where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

$total_result = fetchOne("SELECT COUNT(u.id) as total " . $base_query . $where_sql, $params);
$total_results = $total_result ? $total_result['total'] : 0;
$total_pages = ceil($total_results / $limit);

$users_result = fetchAll("SELECT u.*, 
        COALESCE(os.total_orders, 0) as total_orders,
        COALESCE(os.total_spent, 0) as total_spent,
        COALESCE(os.total_pages_printed, 0) as total_pages_printed,
        os.last_order_date " . $base_query . $where_sql . " ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset", $params);
$users = $users_result ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-72 min-h-screen p-8">
        
        <!-- Header con gradiente -->
        <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-purple-600 to-blue-800 p-8 text-white">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-4xl font-bold mb-2">Gestión de Usuarios</h1>
                        <p class="text-blue-100 text-lg">Administra todos los usuarios de la plataforma</p>
                        <div class="flex items-center space-x-6 mt-4">
                            <div class="bg-white/20 rounded-lg px-4 py-2 backdrop-blur">
                                <span class="text-sm">Total: <strong><?= $total_results ?></strong></span>
                            </div>
                            <div class="bg-green-500/30 rounded-lg px-4 py-2">
                                <span class="text-sm">Activos: <strong><?= array_sum(array_column($users, 'is_active')) ?></strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="w-24 h-24 bg-white/10 rounded-full flex items-center justify-center backdrop-blur border border-white/20">
                            <i class="fas fa-users text-4xl text-white/80"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros modernos -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-slate-200/50">
            <form action="users.php" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    
                    <!-- Búsqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            <i class="fas fa-search mr-2 text-blue-500"></i>Búsqueda
                        </label>
                        <input type="text" name="search" 
                               class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-0 transition-all duration-200 bg-slate-50/50"
                               placeholder="Nombre, apellido, email..." 
                               value="<?= htmlspecialchars($search_term) ?>">
                    </div>
                    
                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            <i class="fas fa-toggle-on mr-2 text-green-500"></i>Estado
                        </label>
                        <select name="status" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:border-blue-500 bg-slate-50/50">
                            <option value="">Todos</option>
                            <option value="1" <?= ($search_status === '1') ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= ($search_status === '0') ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>
                    
                    <!-- Botones -->
                    <div class="flex items-end space-x-3">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105">
                            <i class="fas fa-filter mr-2"></i>Filtrar
                        </button>
                        <a href="users.php" class="bg-slate-500 hover:bg-slate-600 text-white px-4 py-3 rounded-xl transition-colors">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Grid de usuarios -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
            <?php foreach ($users as $user): ?>
            <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-slate-100 overflow-hidden">
                
                <!-- Header de la card -->
                <div class="bg-gradient-to-r from-slate-800 to-slate-700 p-6 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
                    <div class="relative z-10 flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold text-white">
                                <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'S', 0, 1)) ?>
                            </span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg"><?= htmlspecialchars(($user['first_name'] ?? 'Sin nombre') . ' ' . ($user['last_name'] ?? '')) ?></h3>
                            <p class="text-slate-300 text-sm"><?= htmlspecialchars($user['email'] ?? 'Sin email') ?></p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $user['is_active'] ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' ?>">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    <?= $user['is_active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cuerpo de la card -->
                <div class="p-6">
                    <!-- Estadísticas -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600"><?= $user['total_orders'] ?? 0 ?></div>
                            <div class="text-xs text-slate-600">Pedidos</div>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">€<?= number_format($user['total_spent'] ?? 0, 0) ?></div>
                            <div class="text-xs text-slate-600">Gastado</div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="space-y-3 text-sm mb-6">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Registro:</span>
                            <span class="font-medium"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Último pedido:</span>
                            <span class="font-medium">
                                <?= isset($user['last_order_date']) && $user['last_order_date'] ? date('d/m/Y', strtotime($user['last_order_date'])) : 'Nunca' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Páginas impresas:</span>
                            <span class="font-medium text-purple-600"><?= number_format($user['total_pages_printed'] ?? 0) ?></span>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex space-x-2">
                        <!-- Toggle Estado -->
                        <form method="post" class="flex-1">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                            <button type="submit" name="toggle_active" 
                                    class="w-full px-4 py-2 rounded-xl font-semibold transition-all duration-200 <?= $user['is_active'] 
                                        ? 'bg-orange-100 hover:bg-orange-200 text-orange-700 border-2 border-orange-200' 
                                        : 'bg-green-100 hover:bg-green-200 text-green-700 border-2 border-green-200' ?>">
                                <i class="fas fa-<?= $user['is_active'] ? 'pause' : 'play' ?> mr-2"></i>
                                <?= $user['is_active'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>

                        <!-- Ver Pedidos -->
                        <a href="orders.php?user_id=<?= $user['id'] ?>" 
                           class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-xl font-semibold transition-colors border-2 border-blue-200">
                            <i class="fas fa-shopping-cart"></i>
                        </a>

                        <!-- Eliminar -->
                        <form method="post" onsubmit="return confirm('¿Eliminar usuario?')">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" 
                                    class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-xl transition-colors border-2 border-red-200">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación moderna -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center">
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-slate-200">
                <div class="flex items-center space-x-2">
                    
                    <!-- Página anterior -->
                    <?php if ($page > 1): ?>
                    <a href="users.php?page=<?= $page - 1 ?>&search=<?= urlencode($search_term) ?>&status=<?= $search_status ?>" 
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors font-medium">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <!-- Números de página -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="users.php?page=<?= $i ?>&search=<?= urlencode($search_term) ?>&status=<?= $search_status ?>" 
                       class="px-4 py-2 rounded-xl font-medium transition-all duration-200 <?= ($i == $page) 
                           ? 'bg-blue-500 text-white shadow-lg' 
                           : 'text-slate-700 hover:bg-slate-100' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>

                    <!-- Página siguiente -->
                    <?php if ($page < $total_pages): ?>
                    <a href="users.php?page=<?= $page + 1 ?>&search=<?= urlencode($search_term) ?>&status=<?= $search_status ?>" 
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors font-medium">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estado vacío -->
        <?php if (empty($users)): ?>
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-4xl text-slate-400"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">No hay usuarios</h3>
            <p class="text-slate-500">No se encontraron usuarios con los filtros aplicados.</p>
            <a href="users.php" class="inline-flex items-center mt-4 text-blue-600 hover:text-blue-700 font-medium">
                <i class="fas fa-refresh mr-2"></i>Ver todos
            </a>
        </div>
        <?php endif; ?>

    </div>

    <!-- Mensajes de éxito -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-2xl shadow-2xl z-50 transform animate-pulse">
        <div class="flex items-center space-x-3">
            <i class="fas fa-check-circle text-xl"></i>
            <span class="font-medium"><?= $_SESSION['success_message'] ?></span>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <script>
    // Auto-hide success message
    setTimeout(() => {
        const successMsg = document.querySelector('.animate-pulse');
        if (successMsg) {
            successMsg.style.transform = 'translateX(100%)';
            successMsg.style.opacity = '0';
            setTimeout(() => successMsg.remove(), 300);
        }
    }, 3000);

    // Smooth scroll pour ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Efectos hover para las cards
    document.querySelectorAll('.group').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    </script>

</body>
</html>