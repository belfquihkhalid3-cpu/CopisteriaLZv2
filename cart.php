<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = processOrder($_SESSION['user_id'], $_POST);
    if ($order_id) {
        header("Location: order-status.php?id=$order_id");
        exit();
    }
}

require_once 'includes/header.php';
?>

<div class="cart-container">
    <h2>Carrito de Compras</h2>
Commande ECHO désactivée.
    <div id="cart-items">
        <-- Los elementos del carrito se cargarán dinámicamente -->
    </div>
Commande ECHO désactivée.
    <div class="cart-total">
        <p><strong>Total: €<span id="total-price">0.00</span></strong></p>
    </div>
Commande ECHO désactivée.
    <form method="POST" id="checkout-form">
        <h3>Información de Entrega</h3>
        <textarea name="customer_notes" placeholder="Notas adicionales"></textarea>
Commande ECHO désactivée.
        <div class="payment-methods">
            <h3>Método de Pago</h3>
            <label><input type="radio" name="payment_method" value="ON_SITE" checked> Pagar en tienda</label>
        </div>
Commande ECHO désactivée.
        <button type="submit" class="checkout-btn">Confirmar Pedido</button>
    </form>
</div>

<script src="assets/js/cart.js"></script>
