<?php
session_start();
require_once '../auth.php';
require_once '../../config/database.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

require_once '../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? 0;
    $new_status = $input['status'] ?? '';
    
    if (!$order_id || !$new_status) {
        throw new Exception('Datos incompletos');
    }
    
    $valid_statuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'READY', 'COMPLETED', 'CANCELLED'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Estado no válido');
    }
    
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = executeQuery($sql, [$new_status, $order_id]);
    
    if (!$stmt) {
        throw new Exception('Error al actualizar el pedido');
    }
    
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>