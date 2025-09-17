<?php
<<<<<<< HEAD
session_start();
require_once '../auth.php';
requireAdmin();
require_once '../../includes/functions.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Debug
error_log("API Token called");

$input = file_get_contents('php://input');
error_log("Input received: " . $input);

$data = json_decode($input, true);
$order_id = $data['order_id'] ?? 0;

error_log("Order ID: " . $order_id);

if ($order_id) {
    $order = fetchOne("SELECT user_id FROM orders WHERE id = ?", [$order_id]);
    if ($order) {
        $token = generateOrderToken($order_id, $order['user_id']);
        error_log("Token generated successfully");
        echo json_encode(['success' => true, 'token' => $token]);
    } else {
        error_log("Order not found");
        echo json_encode(['success' => false, 'error' => 'Commande non trouvée']);
    }
} else {
    error_log("Invalid order ID");
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
=======
echo "Test fonctions:<br>";

if (function_exists('generateOrderToken')) {
    echo "✅ generateOrderToken existe<br>";
} else {
    echo "❌ generateOrderToken manquante<br>";
}

if (file_exists('../includes/functions.php')) {
    echo "✅ functions.php existe<br>";
    require_once '../includes/functions.php';
} else {
    echo "❌ functions.php manquant<br>";
}

if (file_exists('../../includes/functions.php')) {
    echo "✅ functions.php niveau 2 existe<br>";
} else {
    echo "❌ functions.php niveau 2 manquant<br>";
>>>>>>> e2b0e54c53dd611fdc7485e7c43341cd79e07aaf
}
?>