<?php
session_start();
require_once '../config/database.php';
require_once '../config/social-config.php';
require_once '../includes/user_functions.php';

try {
    // Vérifier le state pour sécurité
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        throw new Exception('État OAuth invalide');
    }
    
    if (!isset($_GET['code'])) {
        throw new Exception('Code d\'autorisation manquant');
    }
    
    // Échanger le code contre un token
    $token_data = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Error al obtener token de Google');
    }
    
    $token_info = json_decode($response, true);
    if (!$token_info || !isset($token_info['access_token'])) {
        throw new Exception('Token de acceso inválido');
    }
    
    // Obtenir infos utilisateur Google
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_info['access_token']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $user_response = curl_exec($ch);
    curl_close($ch);
    
    $google_user = json_decode($user_response, true);
    
    if (!$google_user || !isset($google_user['email'])) {
        throw new Exception('No se pudieron obtener datos del usuario');
    }
    
    // Vérifier si utilisateur existe
    $existing_user = fetchOne("SELECT * FROM users WHERE email = ? OR google_id = ?", 
                             [$google_user['email'], $google_user['id']]);
    
    $action = $_SESSION['social_action'] ?? 'login';
    
    if ($existing_user) {
        // Utilisateur existe - connexion
        if (!$existing_user['is_active']) {
            throw new Exception('Cuenta desactivada');
        }
        
        // Mettre à jour Google ID si pas encore fait
        if (!$existing_user['google_id']) {
            executeQuery("UPDATE users SET google_id = ? WHERE id = ?", 
                        [$google_user['id'], $existing_user['id']]);
        }
        
        // Créer session
        $_SESSION['user_id'] = $existing_user['id'];
        $_SESSION['email'] = $existing_user['email'];
        $_SESSION['first_name'] = $existing_user['first_name'];
        $_SESSION['last_name'] = $existing_user['last_name'];
        
        // Update last login
        executeQuery("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$existing_user['id']]);
        
        header('Location: ../index.php?social_login=success');
        
    } 
    else {
    // Nouvel utilisateur - TOUJOURS créer le compte automatiquement
    // Séparer nom/prénom
    $name_parts = explode(' ', $google_user['name'] ?? '', 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Créer nouveau compte automatiquement
    $sql = "INSERT INTO users (email, first_name, last_name, google_id, email_verified, password, avatar_url, social_provider, created_at) 
            VALUES (?, ?, ?, ?, 1, ?, ?, 'google', NOW())";
    
    // Password temporaire pour les comptes sociaux
    $temp_password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $avatar_url = $google_user['picture'] ?? null;
    
    $stmt = executeQuery($sql, [
        $google_user['email'],
        $first_name,
        $last_name,
        $google_user['id'],
        $temp_password,
        $avatar_url
    ]);
    
    if (!$stmt) {
        throw new Exception('Error al crear la cuenta automáticamente');
    }
    
    $user_id = getLastInsertId();
    
    // Créer notification de bienvenue
    executeQuery("INSERT INTO notifications (user_id, title, message, notification_type, created_at) 
                 VALUES (?, ?, ?, 'GENERAL', NOW())", [
        $user_id,
        'Bienvenido a Copisteria',
        'Tu cuenta ha sido creada automáticamente con Google. ¡Ya puedes empezar a imprimir!'
    ]);
    
    // Créer session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $google_user['email'];
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    
    // Log pour admin
    error_log("Nueva cuenta creada automáticamente vía Google: " . $google_user['email']);
    
    header('Location: ../index.php?social_register=success&auto=true');
}
} catch (Exception $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    header('Location: ../index.php?social_error=' . urlencode($e->getMessage()));
}

exit();
?>