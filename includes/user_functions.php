<?php
/**
 * Fonctions pour gestion des utilisateurs - Copisteria
 */

/**
 * Vérifier si un utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Récupérer les informations de l'utilisateur connecté
 * @return array|false
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $sql = "SELECT id, email, first_name, last_name, phone, address, is_admin, created_at, last_login_at 
            FROM users WHERE id = ? AND is_active = 1";
    
    return fetchOne($sql, [$_SESSION['user_id']]);
}

/**
 * Valider les données d'inscription
 * @param array $data
 * @return array Erreurs trouvées
 */
function validateRegistrationData($data) {
    $errors = [];
    
    // Nom complet
    if (empty($data['full_name']) || strlen(trim($data['full_name'])) < 2) {
        $errors[] = 'Le nom complet doit contenir au moins 2 caractères';
    }
    
    // Email
    if (empty($data['email'])) {
        $errors[] = 'L\'email est requis';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format d\'email invalide';
    } elseif (strlen($data['email']) > 255) {
        $errors[] = 'L\'email est trop long';
    }
    
    // Mot de passe
    if (empty($data['password'])) {
        $errors[] = 'Le mot de passe est requis';
    } elseif (strlen($data['password']) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif (strlen($data['password']) > 255) {
        $errors[] = 'Le mot de passe est trop long';
    }
    
    // Vérifier si l'email existe déjà
    if (empty($errors) && emailExists($data['email'])) {
        $errors[] = 'Cette adresse email est déjà utilisée';
    }
    
    return $errors;
}

/**
 * Vérifier si un email existe déjà
 * @param string $email
 * @return bool
 */
function emailExists($email) {
    $stmt = executeQuery("SELECT id FROM users WHERE email = ?", [strtolower(trim($email))]);
    return $stmt && $stmt->rowCount() > 0;
}

/**
 * Créer un nouvel utilisateur
 * @param array $data
 * @return array Résultat avec success/error
 */
function createUser($data) {
    try {
        // Valider les données
        $errors = validateRegistrationData($data);
        if (!empty($errors)) {
            return ['success' => false, 'error' => implode(', ', $errors)];
        }
        
        // Préparer les données
        $full_name = trim($data['full_name']);
        $email = strtolower(trim($data['email']));
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        
        // Séparer prénom et nom
        $name_parts = explode(' ', $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Commencer une transaction
        beginTransaction();
        
        // Insérer l'utilisateur
        $sql = "INSERT INTO users (email, password, first_name, last_name, verification_token, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = executeQuery($sql, [$email, $password_hash, $first_name, $last_name, $verification_token]);
        
        if (!$stmt) {
            rollback();
            return ['success' => false, 'error' => 'Erreur lors de la création du compte'];
        }
        
        $user_id = getLastInsertId();
        
        // Créer une notification de bienvenue
        createNotification($user_id, 'Bienvenido a Copisteria', 
                          'Tu cuenta ha sido creada con éxito. ¡Ya puedes empezar a imprimir!');
        
        // Confirmer la transaction
        commit();
        
        // Log de l'inscription
        error_log("Nouveau compte créé: $email (ID: $user_id)");
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'email' => $email,
            'name' => $first_name . ' ' . $last_name
        ];
        
    } catch (Exception $e) {
        rollback();
        error_log("Erreur création utilisateur: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erreur serveur lors de la création du compte'];
    }
}

/**
 * Authentifier un utilisateur
 * @param string $email
 * @param string $password
 * @return array Résultat avec success/error
 */
function authenticateUser($email, $password) {
    try {
        $email = strtolower(trim($email));
        
        // Vérifier les tentatives de connexion
        $attempt_sql = "SELECT login_attempts, locked_until FROM users WHERE email = ?";
        $stmt = executeQuery($attempt_sql, [$email]);
        
        if ($stmt && $user_attempts = $stmt->fetch()) {
            if ($user_attempts['locked_until'] && strtotime($user_attempts['locked_until']) > time()) {
                return ['success' => false, 'error' => 'Compte temporairement bloqué. Réessayez plus tard.'];
            }
        }
        
        // Récupérer l'utilisateur
        $sql = "SELECT id, password, first_name, last_name, is_active, login_attempts 
                FROM users WHERE email = ?";
        $stmt = executeQuery($sql, [$email]);
        
        if (!$stmt || !$user = $stmt->fetch()) {
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Compte désactivé'];
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
            
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }
        
        // Connexion réussie
        executeQuery("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?", 
                    [$user['id']]);
        
        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $email,
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Erreur authentification: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erreur serveur'];
    }
}

/**
 * Déconnecter l'utilisateur
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Créer une notification pour un utilisateur
 * @param int $user_id
 * @param string $title
 * @param string $message
 * @param string $type
 * @param int|null $order_id
 * @return bool
 */
function createNotification($user_id, $title, $message, $type = 'GENERAL', $order_id = null) {
    $sql = "INSERT INTO notifications (user_id, order_id, title, message, notification_type, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = executeQuery($sql, [$user_id, $order_id, $title, $message, $type]);
    return $stmt !== false;
}

/**
 * Récupérer les notifications d'un utilisateur
 * @param int $user_id
 * @param bool $unread_only
 * @param int $limit
 * @return array
 */
function getUserNotifications($user_id, $unread_only = false, $limit = 20) {
    $sql = "SELECT n.*, o.order_number 
            FROM notifications n 
            LEFT JOIN orders o ON n.order_id = o.id 
            WHERE n.user_id = ?";
    
    $params = [$user_id];
    
    if ($unread_only) {
        $sql .= " AND n.is_read = 0";
    }
    
    $sql .= " ORDER BY n.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return fetchAll($sql, $params);
}

/**
 * Marquer une notification comme lue
 * @param int $notification_id
 * @param int $user_id
 * @return bool
 */
function markNotificationAsRead($notification_id, $user_id) {
    $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?";
    
    $stmt = executeQuery($sql, [$notification_id, $user_id]);
    return $stmt && $stmt->rowCount() > 0;
}

/**
 * Obtenir les statistiques d'un utilisateur
 * @param int $user_id
 * @return array
 */
function getUserStats($user_id) {
    $sql = "SELECT 
                COUNT(o.id) as total_orders,
                COALESCE(SUM(o.total_price), 0) as total_spent,
                COALESCE(SUM(o.total_pages), 0) as total_pages_printed,
                MAX(o.created_at) as last_order_date,
                COUNT(CASE WHEN o.status = 'COMPLETED' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN o.status IN ('PENDING', 'CONFIRMED', 'PROCESSING', 'PRINTING') THEN 1 END) as active_orders
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'CANCELLED'
            WHERE u.id = ?
            GROUP BY u.id";
    
    $result = fetchOne($sql, [$user_id]);
    
    return $result ?: [
        'total_orders' => 0,
        'total_spent' => 0,
        'total_pages_printed' => 0,
        'last_order_date' => null,
        'completed_orders' => 0,
        'active_orders' => 0
    ];
}



// Ajouter après les fonctions existantes

/**
 * Créer ou connecter utilisateur via réseau social
 * @param array $social_data
 * @param string $provider
 * @param string $action
 * @return array
 */
function handleSocialAuth($social_data, $provider, $action = 'login') {
    try {
        $email = $social_data['email'];
        $provider_id = $social_data['id'];
        
        // Chercher utilisateur existant
        $provider_column = $provider . '_id';
        $existing_user = fetchOne("SELECT * FROM users WHERE email = ? OR {$provider_column} = ?", 
                                 [$email, $provider_id]);
        
        if ($existing_user) {
            // Connexion utilisateur existant
            if (!$existing_user['is_active']) {
                return ['success' => false, 'error' => 'Cuenta desactivada'];
            }
            
            // Mettre à jour provider ID si manquant
            if (!$existing_user[$provider_column]) {
                executeQuery("UPDATE users SET {$provider_column} = ?, social_provider = ? WHERE id = ?", 
                           [$provider_id, $provider, $existing_user['id']]);
            }
            
            // Créer session
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['email'] = $existing_user['email'];
            $_SESSION['first_name'] = $existing_user['first_name'];
            $_SESSION['last_name'] = $existing_user['last_name'];
            
            executeQuery("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$existing_user['id']]);
            
            return ['success' => true, 'action' => 'login'];
            
        } else {
            // Nouvel utilisateur
            if ($action === 'login') {
                return ['success' => false, 'error' => 'Usuario no encontrado. Regístrate primero.'];
            }
            
            $first_name = $social_data['first_name'] ?? $social_data['given_name'] ?? '';
            $last_name = $social_data['last_name'] ?? $social_data['family_name'] ?? '';
            
            // Créer compte
            $sql = "INSERT INTO users (email, first_name, last_name, {$provider_column}, social_provider, email_verified, password, avatar_url, created_at) 
                    VALUES (?, ?, ?, ?, ?, 1, ?, ?, NOW())";
            
            $temp_password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $avatar_url = $social_data['picture'] ?? null;
            
            $stmt = executeQuery($sql, [
                $email, $first_name, $last_name, $provider_id, $provider, $temp_password, $avatar_url
            ]);
            
            if (!$stmt) {
                return ['success' => false, 'error' => 'Error al crear cuenta'];
            }
            
            $user_id = getLastInsertId();
            
            // Notification bienvenue
            createNotification($user_id, 'Bienvenido a Copisteria', 
                             "Tu cuenta ha sido creada con {$provider}. ¡Ya puedes empezar a imprimir!");
            
            // Créer session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            return ['success' => true, 'action' => 'register'];
        }
        
    } catch (Exception $e) {
        error_log("Social auth error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error del servidor'];
    }
}

/**
 * Déconnecter compte des réseaux sociaux
 * @param int $user_id
 * @param string $provider
 * @return bool
 */
function unlinkSocialAccount($user_id, $provider) {
    $provider_column = $provider . '_id';
    
    $sql = "UPDATE users SET {$provider_column} = NULL";
    
    // Si c'était le seul provider, garder au moins un moyen de connexion
    $user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
    if ($user && !$user['password'] && $user['social_provider'] === $provider) {
        $sql .= ", social_provider = NULL";
    }
    
    $sql .= " WHERE id = ?";
    
    $stmt = executeQuery($sql, [$user_id]);
    return $stmt && $stmt->rowCount() > 0;
}

?>