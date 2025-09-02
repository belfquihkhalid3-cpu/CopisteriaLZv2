<?php
session_start();
require_once '../config/database.php';
require_once '../config/social-config.php';
require_once '../includes/user_functions.php';

try {
    // Vérifier state
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        throw new Exception('Estado OAuth invalido');
    }
    
    if (!isset($_GET['code'])) {
        throw new Exception('Código de autorización faltante');
    }
    
    // Obtenir token Facebook
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $_GET['code']
    ]);
    
    $token_response = file_get_contents($token_url);
    $token_data = json_decode($token_response, true);
    
    if (!$token_data || !isset($token_data['access_token'])) {
        throw new Exception('Error obteniendo token de Facebook');
    }
    
    // Obtenir données utilisateur
    $user_url = 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name&access_token=' . $token_data['access_token'];
    $user_response = file_get_contents($user_url);
    $facebook_user = json_decode($user_response, true);
    
    if (!$facebook_user || !isset($facebook_user['email'])) {
        throw new Exception('No se pudieron obtener datos del usuario de Facebook');
    }
    
    // Même logique que Google mais avec facebook_id
    $existing_user = fetchOne("SELECT * FROM users WHERE email = ? OR facebook_id = ?", 
                             [$facebook_user['email'], $facebook_user['id']]);
    
    $action = $_SESSION['social_action'] ?? 'login';
    
    if ($existing_user) {
        // Login existant
        if (!$existing_user['facebook_id']) {
            executeQuery("UPDATE users SET facebook_id = ? WHERE id = ?", 
                        [$facebook_user['id'], $existing_user['id']]);
        }
        
        $_SESSION['user_id'] = $existing_user['id'];
        $_SESSION['email'] = $existing_user['email'];
        $_SESSION['first_name'] = $existing_user['first_name'];
        $_SESSION['last_name'] = $existing_user['last_name'];
        
        executeQuery("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$existing_user['id']]);
        
        header('Location: ../index.php?social_login=success');
        
    } else {
        // Nouveau compte Facebook
        if ($action === 'login') {
            throw new Exception('Usuario no encontrado. ¿Deseas registrarte?');
        }
        
        $sql = "INSERT INTO users (email, first_name, last_name, facebook_id, email_verified, password, created_at) 
                VALUES (?, ?, ?, ?, 1, ?, NOW())";
        
        $temp_password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        
        $stmt = executeQuery($sql, [
            $facebook_user['email'],
            $facebook_user['first_name'] ?? '',
            $facebook_user['last_name'] ?? '',
            $facebook_user['id'],
            $temp_password
        ]);
        
        if (!$stmt) {
            throw new Exception('Error al crear cuenta con Facebook');
        }
        
        $user_id = getLastInsertId();
        
        // Notification bienvenue
        executeQuery("INSERT INTO notifications (user_id, title, message, notification_type, created_at) 
                     VALUES (?, ?, ?, 'GENERAL', NOW())", [
            $user_id,
            'Bienvenido a Copisteria',
            'Tu cuenta ha sido creada con Facebook. ¡Ya puedes empezar a imprimir!'
        ]);
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $facebook_user['email'];
        $_SESSION['first_name'] = $facebook_user['first_name'];
        $_SESSION['last_name'] = $facebook_user['last_name'];
        
        header('Location: ../index.php?social_register=success');
    }
    
} catch (Exception $e) {
    error_log("Facebook OAuth error: " . $e->getMessage());
    header('Location: ../index.php?social_error=' . urlencode($e->getMessage()));
}

exit();
?>