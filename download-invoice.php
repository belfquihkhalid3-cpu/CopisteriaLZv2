<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/user_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$order_id = $_GET['order_id'] ?? 0;

// Vérifier que la commande appartient à l'utilisateur connecté
$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
", [$order_id, $_SESSION['user_id']]);

if (!$order) {
    die('Commande non trouvée ou accès non autorisé');
}

// Inclure le même template que l'admin mais avec les données du client
include 'admin/generate-invoice.php';
?>