<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/security_headers.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['error' => 'Email et mot de passe requis']);
        exit();
    }
    
    $email = trim(strtolower($data['email']));
    $password = $data['password'];
    
    // Récupérer l'utilisateur
    $sql = "SELECT id, password, first_name, last_name FROM users WHERE email = ? AND is_active = 1";
    $stmt = executeQuery($sql, [$email]);
    
    if (!$stmt || !$user = $stmt->fetch()) {
        echo json_encode(['error' => 'Identifiants incorrects']);
        exit();
    }
    
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Identifiants incorrects']);
        exit();
    }
    
    // Créer la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur']);
}
?>