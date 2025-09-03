<?php
session_start();
require_once '../includes/functions.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? 0;

if ($order_id && isset($_SESSION['user_id'])) {
    $token = generateOrderToken($order_id, $_SESSION['user_id']);
    echo json_encode(['success' => true, 'token' => $token]);
} else {
    echo json_encode(['success' => false]);
}
?>