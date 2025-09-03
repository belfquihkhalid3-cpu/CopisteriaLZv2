<?php
/**
 * API de connexion utilisateur - Copisteria
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

require_once '../config/database.php';
require_once '../includes/security_headers.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email et mot de passe requis']);
        exit();
    }
    
    $email = trim(strtolower($data['email']));
    $password = $data['password'];
    
    // Vérifier les tentatives de connexion
    $attempt_sql = "SELECT login_attempts, locked_until FROM users WHERE email = ?";
    $stmt = executeQuery($attempt_sql, [$email]);
    
    if ($stmt && $user_attempts = $stmt->fetch()) {
        if ($user_attempts['locked_until'] && strtotime($user_attempts['locked_until']) > time()) {
            http_response_code(429);
            echo json_encode(['error' => 'Compte temporairement bloqué. Réessayez plus tard.']);
            exit();
        }
    }
    
    // Récupérer l'utilisateur
    $sql = "SELECT id, password, first_name, last_name, is_active, login_attempts FROM users WHERE email = ?";
    $stmt = executeQuery($sql, [$email]);
    
    if (!$stmt || !$user = $stmt->fetch()) {
        // Incrémenter les tentatives même si l'utilisateur n'existe pas (sécurité)
        http_response_code(401);
        echo json_encode(['error' => 'Identifiants incorrects']);
        exit();
    }
    
    if (!$user['is_active']) {
        http_response_code(403);
        echo json_encode(['error' => 'Compte désactivé']);
        exit();
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['password'])) {
        // Incrémenter les tentatives échouées
        $attempts = $user['login_attempts'] + 1;
        $locked_until = null;
        
        if ($attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }
        
        executeQuery("UPDATE users SET login_attempts = ?, locked_until = ? WHERE email = ?", 
                    [$attempts, $locked_until, $email]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Identifiants incorrects']);
        exit();
    }
    
    // Connexion réussie - réinitialiser les tentatives
    executeQuery("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?", 
                [$user['id']]);
    
    // Créer la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'user' => [
            'id' => $user['id'],
            'email' => $email,
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur connexion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>