<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$admin = getAdminUser();

// --- Lógica POST para acciones (Activar/Desactivar, Eliminar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Activar/Desactivar un usuario
    if (isset($_POST['toggle_active'])) {
        $user_id_to_toggle = $_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1; // Invierte el estado
        execute("UPDATE users SET is_active = :is_active WHERE id = :id", ['is_active' => $new_status, 'id' => $user_id_to_toggle]);
        $_SESSION['success_message'] = "El estado del usuario ha sido actualizado.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    // Eliminar un usuario
    if (isset($_POST['delete_user'])) {
        $user_id_to_delete = $_POST['user_id'];
        execute("DELETE FROM users WHERE id = :id", ['id' => $user_id_to_delete]);
        $_SESSION['success_message'] = "El usuario ha sido eliminado correctamente.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// --- Lógica GET para búsqueda y paginación ---
$limit = 15; // Usuarios por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_term = $_GET['search'] ?? '';
$search_status = $_GET['status'] ?? '';

// Usamos la vista user_stats que ya calcula los totales por nosotros
$base_query = "FROM user_stats us JOIN users u ON us.id = u.id";
$where_clauses = ["u.is_admin = 0"]; // Siempre excluir a los administradores
$params = [];

if (!empty($search_term)) {
    $where_clauses[] = "(us.first_name LIKE :term OR us.last_name LIKE :term OR us.email LIKE :term)";
    $params['term'] = '%' . $search_term . '%';
}
if ($search_status !== '') {
    $where_clauses[] = "u.is_active = :is_active";
    $params['is_active'] = $search_status;
}

$where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

$total_results = fetchOne("SELECT COUNT(us.id) as total " . $base_query . $where_sql, $params)['total'];
$total_pages = ceil($total_results / $limit);

$users = fetchAll(
    "SELECT us.*, u.is_active, u.created_at " . $base_query . $where_sql . " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset",
    array_merge($params, ['limit' => $limit, 'offset' => $offset])
);

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-active { position: relative; }
        .nav-active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); height: 60%; width: 4px; background-color: white; border-radius: 0 4px 4px 0; }
    </style>
</head>
<body class="bg-slate-100 flex">

    <aside class="w-64 bg-slate-800 text-slate-300 flex-col z-30 h-screen sticky top-0 hidden md:flex">
         <div class="flex items-center justify-center h-20 border-b border-slate-700">
            <div class="flex items-center space-x-3"><i class="fas fa-print text-white text-2xl"></i><span class="text-xl font-bold text-white">Panel Admin</span></div>
        </div>
        <nav class="flex-1 mt-6">
            <ul class="space-y-2 px-4">
                <li><a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?= ($current_page == 'dashboard.php') ? 'nav-active bg-slate-900 text-white font-semibold' : 'hover:bg-slate-700 hover:text-white' ?>"><i class="fas fa-tachometer-alt w-6 text-center"></i><span>Dashboard</span></a></li>
                <li><a href="orders.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?= ($current_page == 'orders.php') ? 'nav-active bg-slate-900 text-white font-semibold' : 'hover:bg-slate-700 hover:text-white' ?>"><i class="fas fa-shopping-cart w-6 text-center"></i><span>Pedidos</span></a></li>
                <li><a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?= ($current_page == 'users.php') ? 'nav-active bg-slate-900 text-white font-semibold' : 'hover:bg-slate-700 hover:text-white' ?>"><i class="fas fa-users w-6 text-center"></i><span>Usuarios</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-4 sm:p-8">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Gestión de Usuarios</h1>
                <p class="text-sm text-slate-500">Añade, edita y gestiona los usuarios de la plataforma.</p>
            </div>
            <a href="#" class="bg-sky-500 text-white px-4 py-2 rounded-lg hover:bg-sky-600 transition-colors text-sm font-bold flex items-center mt-4 sm:mt-0">
                <i class="fas fa-plus mr-2"></i>Añadir Usuario
            </a>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <form action="users.php" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-slate-600 mb-1">Buscar Usuario</label>
                        <input type="text" name="search" id="search" class="w-full border-slate-300 rounded-lg shadow-sm" placeholder="Buscar por nombre, apellido, email..." value="<?= htmlspecialchars($search_term) ?>">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-600 mb-1">Estado</label>
                        <select name="status" id="status" class="w-full border-slate-300 rounded-lg shadow-sm">
                            <option value="">Todos</option>
                            <option value="1" <?= ($search_status === '1') ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= ($search_status === '0') ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <a href="users.php" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-300 text-sm font-bold">Restablecer</a>
                    <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-900 text-sm font-bold flex items-center"><i class="fas fa-search mr-2"></i>Filtrar</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-4 text-left font-semibold text-slate-600">Usuario</th>
                        <th class="p-4 text-left font-semibold text-slate-600">Fecha de Registro</th>
                        <th class="p-4 text-left font-semibold text-slate-600">Pedidos</th>
                        <th class="p-4 text-left font-semibold text-slate-600">Total Gastado</th>
                        <th class="p-4 text-left font-semibold text-slate-600">Estado</th>
                        <th class="p-4 text-left font-semibold text-slate-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">No se encontraron usuarios.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600">
                                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-slate-600"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td class="p-4 text-slate-600"><?= $user['total_orders'] ?></td>
                            <td class="p-4 text-slate-800 font-semibold">€<?= number_format($user['total_spent'], 2, ',', '.') ?></td>
                            <td class="p-4">
                                <?php if ($user['is_active']): ?>
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Activo</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 bg-slate-200 text-slate-700 rounded-full text-xs font-semibold">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <a href="#" class="text-sky-600 hover:text-sky-800" title="Editar Usuario"><i class="fas fa-pencil-alt"></i></a>
                                    <form action="users.php?<?= http_build_query($_GET) ?>" method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                                        <button type="submit" name="toggle_active" class="<?= $user['is_active'] ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' ?>" title="<?= $user['is_active'] ? 'Desactivar Usuario' : 'Activar Usuario' ?>">
                                            <i class="fas <?= $user['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </button>
                                    </form>
                                    <form action="users.php?<?= http_build_query($_GET) ?>" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción es irreversible.');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="text-red-600 hover:text-red-800" title="Eliminar Usuario">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
            <div class="p-4 border-t border-slate-200 flex justify-between items-center">
                <span class="text-sm text-slate-600">Página <?= $page ?> de <?= $total_pages ?></span>
                <div>
                     <?php
                        $query_params = $_GET;
                        $query_params['page'] = $page - 1;
                        $prev_link = http_build_query($query_params);
                        $query_params['page'] = $page + 1;
                        $next_link = http_build_query($query_params);
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="?<?= $prev_link ?>" class="bg-white border border-slate-300 text-slate-700 px-3 py-1 rounded-md hover:bg-slate-50 text-sm font-bold">&laquo; Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= $next_link ?>" class="bg-white border border-slate-300 text-slate-700 px-3 py-1 rounded-md hover:bg-slate-50 text-sm font-bold">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>