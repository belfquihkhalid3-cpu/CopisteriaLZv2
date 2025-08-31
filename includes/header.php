<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copisteria - Impresión Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-pending { background-color: #fbbf24; color: white; }
        .status-confirmed { background-color: #3b82f6; color: white; }
        .status-processing { background-color: #8b5cf6; color: white; }
        .status-ready { background-color: #10b981; color: white; }
        .status-completed { background-color: #059669; color: white; }
        .pickup-code { font-family: monospace; font-weight: bold; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; }
        .error { background-color: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; margin: 10px 0; }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-print text-blue-500 text-2xl"></i>
                <h1 class="text-xl font-bold"><a href="index.php" class="text-gray-800 hover:text-blue-500">Copisteria</a></h1>
            </div>
Commande ECHO désactivée.
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="account.php" class="text-gray-600 hover:text-blue-500"><i class="fas fa-user"></i> Mi Cuenta</a>
                    <a href="cart.php" class="text-gray-600 hover:text-blue-500"><i class="fas fa-shopping-cart"></i> Carrito</a>
                    <a href="logout.php" class="text-gray-600 hover:text-red-500"><i class="fas fa-sign-out-alt"></i> Salir</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-600 hover:text-blue-500"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
                    <a href="register.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Registrarse</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
Commande ECHO désactivée.
    <main class="container mx-auto px-4 py-8">
