<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

require_once '../../config/database.php';
require_once '../includes/security_headers.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? 0;
    $new_status = $input['status'] ?? '';
    
    if (!$order_id || !$new_status) {
        throw new Exception('Datos incompletos');
    }
    
    // Valider statut
    $valid_statuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'READY', 'COMPLETED', 'CANCELLED'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Estado no válido');
    }
    
    // Récupérer commande actuelle
    $order = fetchOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) {
        throw new Exception('Pedido no encontrado');
    }
    
    // Mettre à jour statut
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    if ($new_status === 'COMPLETED') {
        $sql = "UPDATE orders SET status = ?, completed_at = NOW(), updated_at = NOW() WHERE id = ?";
    }
    
    $stmt = executeQuery($sql, [$new_status, $order_id]);
    
    if (!$stmt) {
        throw new Exception('Error al actualizar el pedido');
    }
    
    // Créer notification pour le client
    $status_messages = [
        'CONFIRMED' => 'Tu pedido ha sido confirmado',
        'PROCESSING' => 'Tu pedido está siendo procesado',
        'READY' => '¡Tu pedido está listo para recoger!',
        'COMPLETED' => 'Pedido completado. ¡Gracias por tu confianza!',
        'CANCELLED' => 'Tu pedido ha sido cancelado'
    ];
    
    if (isset($status_messages[$new_status])) {
        $notif_sql = "INSERT INTO notifications (user_id, order_id, title, message, notification_type, created_at) 
                      VALUES (?, ?, ?, ?, 'ORDER_STATUS_CHANGED', NOW())";
        executeQuery($notif_sql, [
            $order['user_id'],
            $order_id,
            'Estado del pedido actualizado',
            $status_messages[$new_status] . " (#{$order['order_number']})"
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'new_status' => $new_status
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>