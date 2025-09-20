<?php
session_start();
require_once '../includes/csrf.php';
// Rediriger si déjà connecté
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_POST) {
    require_once '../config/database.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Vérifier admin
    $admin = fetchOne("SELECT * FROM users WHERE email = ? AND is_admin = 1 AND is_active = 1", [$email]);
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Login success
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
        
        // Update last login
        executeQuery("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$admin['id']]);
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Credenciales incorrectas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Copisteria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-900 to-indigo-900 min-h-screen flex items-center justify-center">
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Panel de Administración</h1>
            <p class="text-gray-600 mt-2">Acceso exclusivo para administradores</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                <span class="text-red-700"><?= htmlspecialchars($error) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <!-- Email -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Email de administrador
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input 
                        type="email" 
                        name="email" 
                        required
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="admin@copisteria.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Contraseña
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        required
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="••••••••"
                    >
                </div>
            </div>

            <!-- Login Button -->
            <button 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 transform hover:scale-105"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                Acceder al Panel
            </button>

        </form>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500">
                Panel administrativo protegido<br>
                Solo para personal autorizado
            </p>
        </div>
        
        <!-- Back to site -->
        <div class="mt-4 text-center">
            <a href="../index.php" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                Volver al sitio web
            </a>
        </div>

    </div>

    <script>
        // Auto focus sur email
        document.querySelector('input[name="email"]').focus();
        
        // Animation au chargement
        document.querySelector('.bg-white').style.opacity = '0';
        document.querySelector('.bg-white').style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            document.querySelector('.bg-white').style.transition = 'all 0.5s ease';
            document.querySelector('.bg-white').style.opacity = '1';
            document.querySelector('.bg-white').style.transform = 'translateY(0)';
        }, 100);
    </script>

</body>
</html>