<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
Commande ECHO désactivée.
    if (loginUser($email, $password)) {
        header('Location: account.php');
        exit();
    } else {
        $error = 'Credenciales incorrectas';
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <h2>Iniciar Sesión</h2>
Commande ECHO désactivée.
    <?php if(isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
Commande ECHO désactivée.
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Iniciar Sesión</button>
    </form>
Commande ECHO désactivée.
    <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
</div>
