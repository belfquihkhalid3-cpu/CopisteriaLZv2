<?php
/**
 * API d'inscription utilisateur - Copisteria
 * Gère la création de nouveaux comptes utilisateurs
 */

// Headers CORS et JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Inclure la configuration
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

try {
    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validation des données reçues
    if (!$data || !isset($data['full_name']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes']);
        exit();
    }
    
    // Nettoyer et valider les données
    $full_name = trim($data['full_name']);
    $email = trim(strtolower($data['email']));
    $password = $data['password'];
    
    // Validation côté serveur
    $errors = [];
    
    // Valider le nom complet
    if (empty($full_name) || strlen($full_name) < 2) {
        $errors[] = 'Le nom complet doit contenir au moins 2 caractères';
    }
    
    // Valider l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format d\'email invalide';
    }
    
    // Valider le mot de passe
    if (strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => implode(', ', $errors)]);
        exit();
    }
    
    // Séparer prénom et nom
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '+34';
    
    // Vérifier si l'email existe déjà
    $stmt = executeQuery("SELECT id FROM users WHERE email = ?", [$email]);
    if ($stmt && $stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Cette adresse email est déjà utilisée']);
        exit();
    }
    
    // Hasher le mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Générer un token de vérification
    $verification_token = bin2hex(random_bytes(32));
    
  $sql = "INSERT INTO users (email, password, first_name, last_name, phone, verification_token, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = executeQuery($sql, [$email, $password_hash, $first_name, $last_name, $phone, $verification_token]);
    
    if (!$stmt) {
        throw new Exception('Erreur lors de la création du compte');
    }
    
    $user_id = getLastInsertId();
    
    // Créer une notification de bienvenue
    $welcome_sql = "INSERT INTO notifications (user_id, title, message, notification_type, created_at) 
                    VALUES (?, ?, ?, 'GENERAL', NOW())";
    
    executeQuery($welcome_sql, [
        $user_id,
        'Bienvenido a Copisteria',
        'Tu cuenta ha sido creada con éxito. ¡Ya puedes empezar a imprimir!'
    ]);
    
    // Log de l'inscription (optionnel)
    error_log("Nouveau compte créé: $email (ID: $user_id)");
    
    // Réponse de succès
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Compte créé avec succès',
        'user_id' => $user_id,
        'email' => $email,
        'name' => $first_name . ' ' . $last_name,
        'verification_required' => false // Pour l'instant pas de vérification email
    ]);
    
} catch (Exception $e) {
    // Log de l'erreur
    error_log("Erreur inscription: " . $e->getMessage());
    
    // Réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur lors de la création du compte',
        'debug' => $e->getMessage() // À retirer en production
    ]);
}
?>