<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $email = trim($_POST['email'] ?? '');
    $order_number = trim($_POST['order_number'] ?? '');
    
    if (empty($email) || empty($order_number)) {
        throw new Exception('Email y número de pedido son requeridos');
    }
    
    // Buscar la orden
    $order = fetchOne("
        SELECT o.*, u.first_name, u.last_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.order_number = ? AND u.email = ?
    ", [$order_number, $email]);
    
    if (!$order) {
        throw new Exception('Pedido no encontrado. Verifica tu email y número de pedido.');
    }
    
    // Mapear estados a español
    $status_map = [
        'PENDING' => ['label' => 'Pendiente', 'color' => 'yellow', 'icon' => 'clock'],
        'CONFIRMED' => ['label' => 'Confirmado', 'color' => 'blue', 'icon' => 'check-circle'],
        'PROCESSING' => ['label' => 'En Proceso', 'color' => 'purple', 'icon' => 'cog'],
        'PRINTING' => ['label' => 'Imprimiendo', 'color' => 'indigo', 'icon' => 'print'],
        'READY' => ['label' => 'Listo para Recoger', 'color' => 'green', 'icon' => 'box'],
        'COMPLETED' => ['label' => 'Completado', 'color' => 'emerald', 'icon' => 'check-double'],
        'CANCELLED' => ['label' => 'Cancelado', 'color' => 'red', 'icon' => 'times-circle']
    ];
    
    $current_status = $status_map[$order['status']] ?? $status_map['PENDING'];
    
    // Timeline de estados
    $timeline = [
        'PENDING' => ['step' => 1, 'title' => 'Pedido Recibido'],
        'CONFIRMED' => ['step' => 2, 'title' => 'Pago Confirmado'],
        'PROCESSING' => ['step' => 3, 'title' => 'En Preparación'],
        'PRINTING' => ['step' => 4, 'title' => 'Imprimiendo'],
        'READY' => ['step' => 5, 'title' => 'Listo para Recoger'],
        'COMPLETED' => ['step' => 6, 'title' => 'Entregado']
    ];
    
    $current_step = $timeline[$order['status']]['step'] ?? 1;
    
    echo json_encode([
        'success' => true,
       'order' => [
            'order_number' => $order['order_number'],
            'pickup_code' => $order['pickup_code'],
            'status' => $order['status'],
            'status_info' => $current_status,
            'current_step' => $current_step,
            'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
            'total_price' => $order['total_price'],
            'total_files' => $order['total_files'],
            'total_pages' => $order['total_pages'],
            'created_at' => $order['created_at'],
            'payment_method' => $order['payment_method']
        ],
        'timeline' => $timeline
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>