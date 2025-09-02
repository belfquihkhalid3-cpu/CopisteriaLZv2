<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Validar el ID del pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}
$order_id = $_GET['id'];

// Obtener los detalles del pedido
$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = :id", 
    ['id' => $order_id]
);

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Decodificar la configuración de impresión JSON
$print_config = json_decode($order['print_config'], true);
$folders = (json_last_error() === JSON_ERROR_NONE && isset($print_config['folders'])) ? $print_config['folders'] : [];

// Lógica para la línea de tiempo de estado y traducciones
$all_statuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'PRINTING', 'READY', 'COMPLETED'];
$status_translations = [
    'PENDING' => 'Pendiente', 'CONFIRMED' => 'Confirmado', 'PROCESSING' => 'En Proceso', 'PRINTING' => 'Imprimiendo', 
    'READY' => 'Listo', 'COMPLETED' => 'Completado', 'CANCELLED' => 'Cancelado'
];
$current_status_index = array_search($order['status'], $all_statuses);
$is_cancelled = ($order['status'] === 'CANCELLED');

// Traducciones para los detalles de configuración
$config_translations = [
    'copies' => 'Copias', 'colorMode' => 'Color', 'paperSize' => 'Papel', 'paperWeight' => 'Gramaje',
    'sides' => 'Caras', 'orientation' => 'Orientación', 'finishing' => 'Acabado'
];
$config_icons = [
    'copies'=>'copy','colorMode'=>'palette','paperSize'=>'file-lines','paperWeight'=>'weight-hanging','sides'=>'clone','orientation'=>'compass','finishing'=>'scissors'
];

$current_page = 'orders.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido #<?= htmlspecialchars($order['order_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-active { position: relative; }
        .nav-active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); height: 60%; width: 4px; background-color: white; border-radius: 0 4px 4px 0; }
        .timeline-step { display: flex; flex-direction: column; align-items: center; position: relative; flex-grow: 1; }
        .timeline-step:not(:last-child)::after { content: ''; position: absolute; top: 18px; left: 50%; transform: translateX(calc(1.25rem / 2 + 0.5rem)); width: calc(100% - 2.5rem - 1rem); height: 2px; background-color: #e5e7eb; }
        .timeline-step.completed:not(:last-child)::after { background-color: #22c55e; }
        .timeline-icon { width: 2.5rem; height: 2.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.125rem; border: 2px solid white; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
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
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-4 sm:p-8">
        <div class="flex items-center mb-8">
            <a href="orders.php" class="text-slate-500 hover:text-slate-800 mr-4"><i class="fas fa-arrow-left"></i> Volver</a>
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Pedido <span class="text-sky-600">#<?= htmlspecialchars($order['order_number']) ?></span></h1>
                <p class="text-sm text-slate-500">Realizado el <?= date('d M Y \a \l\a\s H:i', strtotime($order['created_at'])) ?></p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h2 class="text-lg font-bold text-slate-700 mb-6">Seguimiento del Pedido</h2>
            <?php if ($is_cancelled): ?>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <i class="fas fa-times-circle text-red-500 text-4xl mb-2"></i>
                    <p class="font-bold text-red-600">Pedido Cancelado</p>
                </div>
            <?php else: ?>
                <div class="flex justify-between text-center">
                    <?php foreach ($all_statuses as $index => $status): ?>
                        <div class="timeline-step <?= ($index <= $current_status_index) ? 'completed' : '' ?>">
                            <div class="timeline-icon <?= ($index <= $current_status_index) ? 'bg-green-500' : 'bg-slate-300' ?> <?= ($index == $current_status_index) ? '!bg-sky-600' : '' ?>">
                                <i class="fas <?= ($index < $current_status_index) ? 'fa-check' : 'fa-box' ?> "></i>
                            </div>
                            <p class="mt-2 text-xs sm:text-sm font-semibold <?= ($index <= $current_status_index) ? 'text-slate-700' : 'text-slate-400' ?>"><?= $status_translations[$status] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center"><i class="fas fa-user mr-3 text-sky-500"></i>Cliente</h3>
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600 text-xl">
                        <?= strtoupper(substr($order['first_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
                        <p class="text-sm text-slate-500"><?= htmlspecialchars($order['email']) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center"><i class="fas fa-box-archive mr-3 text-sky-500"></i>Pedido</h3>
                <div class="space-y-2 text-sm">
                    <p class="flex justify-between"><span>Número:</span> <span class="font-semibold text-slate-800"><?= htmlspecialchars($order['order_number']) ?></span></p>
                    <p class="flex justify-between"><span>Total Archivos:</span> <span class="font-semibold text-slate-800"><?= htmlspecialchars($order['total_files']) ?></span></p>
                    <p class="flex justify-between"><span>Total Páginas:</span> <span class="font-semibold text-slate-800"><?= htmlspecialchars($order['total_pages']) ?></span></p>
                    <p class="flex justify-between"><span>Código de Recogida:</span> <span class="font-bold text-sky-600 tracking-widest bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($order['pickup_code']) ?></span></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center"><i class="fas fa-credit-card mr-3 text-sky-500"></i>Pago</h3>
                <div class="space-y-2 text-sm">
                    <p class="flex justify-between"><span>Importe Total:</span> <span class="font-bold text-xl text-green-600">€<?= number_format($order['total_price'], 2, ',', '.') ?></span></p>
                    <p class="flex justify-between"><span>Método:</span> <span class="font-semibold text-slate-800"><?= htmlspecialchars($order['payment_method']) ?></span></p>
                    <p class="flex justify-between"><span>Estado:</span> <span class="font-semibold text-slate-800"><?= htmlspecialchars($order['payment_status']) ?></span></p>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-semibold text-slate-700 mb-4">Trabajos de Impresión</h2>
        <div class="space-y-6">
            <?php if (empty($folders)): ?>
                <div class="bg-white p-6 rounded-lg shadow-lg"><p class="text-slate-500">No se encontró ninguna configuración de impresión.</p></div>
            <?php else: ?>
                <?php foreach ($folders as $folder): ?>
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="p-4 border-b bg-slate-50 rounded-t-xl">
                        <h3 class="text-lg font-bold text-slate-800 flex items-center"><i class="fas fa-folder text-sky-600 mr-3"></i>Carpeta: <?= htmlspecialchars($folder['name']) ?></h3>
                    </div>
                    <div class="p-6">
                        <h4 class="font-semibold text-slate-600 mb-4">Configuración:</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-5 text-sm mb-6">
                            <?php foreach ($folder['configuration'] as $key => $value): ?>
                                <?php if (isset($config_translations[$key])): ?>
                                <div class="flex items-start">
                                    <i class="fas fa-<?= $config_icons[$key] ?> text-slate-400 w-5 text-center mt-1 mr-3"></i>
                                    <div>
                                        <strong class="block text-slate-500 text-xs"><?= $config_translations[$key] ?></strong>
                                        <span class="text-slate-900 font-medium"><?= is_array($value) ? implode(', ', $value) : htmlspecialchars($value) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <h4 class="font-semibold text-slate-600 mb-3 pt-4 border-t">Archivos:</h4>
                        <ul class="space-y-2">
                           <?php foreach ($folder['files'] as $file): 
                               $file_path = 'uploads/documents/' . htmlspecialchars($file['stored_name']);
                           ?>
                           <li class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 bg-slate-50 hover:bg-slate-100 rounded-lg">
                               <div class="flex items-center mb-2 sm:mb-0">
                                   <i class="fas fa-file-pdf text-red-500 text-2xl mr-4"></i>
                                   <div>
                                       <p class="font-semibold text-slate-800"><?= htmlspecialchars($file['name']) ?></p>
                                       <p class="text-xs text-slate-500"><?= floor($file['size'] / 1024) ?> KB - <?= $file['pages'] ?> Página(s)</p>
                                   </div>
                               </div>
                               <div class="flex items-center space-x-2 self-end sm:self-center">
                                   <a href="../<?= $file_path ?>" download class="bg-sky-500 text-white px-3 py-2 rounded-lg hover:bg-sky-600 transition-colors text-xs font-bold flex items-center"><i class="fas fa-download mr-2"></i>Descargar</a>
                                   <a href="../<?= $file_path ?>" target="_blank" class="bg-slate-200 text-slate-700 px-3 py-2 rounded-lg hover:bg-slate-300 transition-colors text-xs font-bold flex items-center"><i class="fas fa-eye mr-2"></i>Vista Previa</a>
                               </div>
                           </li>
                           <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>