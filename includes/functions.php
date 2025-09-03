<?php
function generateOrderToken($order_id, $user_id) {
    $secret = 'Cp2025!@#$%SecureKey789XyZ_MyApp_V1.0_Production';
    return hash('sha256', $order_id . '_' . $user_id . '_' . $secret . '_' . date('Y-m-d'));
}

function validateOrderAccess($order_id, $token, $user_id) {
    return hash_equals(generateOrderToken($order_id, $user_id), $token);
}

function generateSecureOrderURL($order_id, $user_id) {
    $token = generateOrderToken($order_id, $user_id);
    return "pedido/{$order_id}/{$token}";
}

?>