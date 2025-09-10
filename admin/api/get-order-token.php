<?php
session_start();
require_once '../auth.php';
requireAdmin();
require_once '../../includes/functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? 0;

if ($order_id) {
    $order = fetchOne("SELECT user_id FROM orders WHERE id = ?", [$order_id]);
    if ($order) {
        $token = generateOrderToken($order_id, $order['user_id']);
        echo json_encode(['success' => true, 'token' => $token]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Commande non trouvée']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
}
?>