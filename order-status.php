<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = $_GET['id'] ?? 0;
$order = getOrderById($order_id, $_SESSION['user_id']);

if ($order) {
    header('Location: account.php');
    exit();
}

$order_items = getOrderItems($order_id);

require_once 'includes/header.php';
?>

<div class="order-status-container">
    <h2>Estado del Pedido #<?= htmlspecialchars($order['order_number']) ?></h2>
Commande ECHO désactivée.
    <div class="order-info">
        <div class="status-badge status-^<?= strtolower^($order['status'^]^) ?^>">
            <?= htmlspecialchars($order['status']) ?>
        </div>
Commande ECHO désactivée.
        <div class="order-details">
            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
            <p><strong>Total:</strong> €<?= number_format($order['total_price'], 2) ?></p>
            <p><strong>Páginas totales:</strong> <?= $order['total_pages'] ?></p>
            <p><strong>Archivos:</strong> <?= $order['total_files'] ?></p>
Commande ECHO désactivée.
            <?php if($order['pickup_code']): ?>
                <p><strong>Código de recogida:</strong> <span class="pickup-code"><?= htmlspecialchars($order['pickup_code']) ?></span></p>
            <?php endif; ?>
        </div>
    </div>
Commande ECHO désactivée.
    <div class="order-items">
        <h3>Archivos del Pedido</h3>
        <?php foreach($order_items as $item): ?>
            <div class="order-item">
                <p><strong><?= htmlspecialchars($item['file_original_name']) ?></strong></p>
                <p>Páginas: <?= $item['page_count'] ?> x <?= $item['copies'] ?> copias</p>
                <p>Configuración: <?= htmlspecialchars($item['paper_size']) ?> - <?= htmlspecialchars($item['color_mode']) ?></p>
                <p>Subtotal: €<?= number_format($item['item_total'], 2) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
Commande ECHO désactivée.
    <div class="actions">
        <a href="account.php">Volver a Mi Cuenta</a>
        <a href="index.php">Nuevo Pedido</a>
    </div>
</div>
