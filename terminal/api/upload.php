<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/security_headers.php';
require_once '../config.php';

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
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

try {
    // Pour les terminaux, autoriser les invités
    $terminal_info = getTerminalInfo();
    $is_guest_terminal = isset($_POST['terminal_mode']) || 
                         isset($_GET['terminal_mode']) ||
                         $terminal_info['status'] === 'active'; // AJOUTER CETTE LIGNE
    
    // Vérifier autorisation (utilisateur connecté OU mode invité terminal OU terminal autorisé)
    if (!isset($_SESSION['user_id']) && !$is_guest_terminal) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
        exit();
    }
    
    // Configuration upload
    $max_file_size = 50 * 1024 * 1024; // 50MB
    $allowed_types = [
        'application/pdf',
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
    
    $upload_dir = '../../uploads/documents/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Impossible de créer le dossier uploads');
        }
    }
    
    // Vérifier les fichiers uploadés
    if (empty($_FILES['files'])) {
        throw new Exception('Aucun fichier reçu');
    }
    
    $files = $_FILES['files'];
    $uploaded_files = [];
    $errors = [];
    
    // Traiter chaque fichier
    $file_count = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $file_count; $i++) {
        $file = [
            'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
            'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
            'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
            'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
            'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
        ];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error upload: {$file['name']}";
            continue;
        }
        
        // Validation
        if (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $allowed_extensions)) {
            $errors[] = "Extension non autorisée: {$file['name']}";
            continue;
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Type MIME non autorisé: {$file['name']}";
            continue;
        }
        
        if ($file['size'] > $max_file_size) {
            $errors[] = "Fichier trop volumineux: {$file['name']}";
            continue;
        }
        
        // Générer nom unique
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_name = uniqid() . '.' . $extension;
        $file_path = $upload_dir . $unique_name;
        
        // Déplacer fichier
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Compter pages PDF
            $page_count = 1;
            if ($extension === 'pdf') {
                try {
                    $pdf_content = file_get_contents($file_path);
                    preg_match_all('/\/Count\s+(\d+)/', $pdf_content, $matches);
                    if (!empty($matches[1])) {
                        $page_count = max($matches[1]);
                    }
                } catch (Exception $e) {
                    $page_count = 1;
                }
            }
            
            // Calculer hash
            $file_hash = md5_file($file_path);
            
            // Déterminer user_id (null pour invités)
            // Gérer user_id pour invités - utiliser 0 au lieu de null
if ($is_guest_terminal) {
    // Créer ou récupérer utilisateur invité
    $guest_user = fetchOne("SELECT id FROM users WHERE email = 'guest@terminal.local'");
    if (!$guest_user) {
        $sql_guest = "INSERT INTO users (email, first_name, last_name, password, is_admin, is_active, created_at) 
                      VALUES ('guest@terminal.local', 'Cliente', 'Invitado', 'no_password', 0, 1, NOW())";
        executeQuery($sql_guest);
        $user_id_for_db = getLastInsertId();
    } else {
        $user_id_for_db = $guest_user['id'];
    }
} else {
    $user_id_for_db = $_SESSION['user_id'];
}

// SQL ajusté selon la vraie structure de la table
$sql = "INSERT INTO files (user_id, original_name, stored_name, file_path, file_size, mime_type, page_count, file_hash, status, created_at, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'READY', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";

$stmt = executeQuery($sql, [
    $user_id_for_db,
    $file['name'],
    $unique_name,
    $file_path,
    $file['size'],
    $file['type'],
    $page_count,
    $file_hash
]);
            
            if ($stmt) {
                $uploaded_files[] = [
                    'id' => getLastInsertId(),
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'pages' => $page_count,
                    'type' => $file['type'],
                    'stored_name' => $unique_name
                ];
            } else {
                unlink($file_path);
                $errors[] = "Error base de datos: {$file['name']}";
            }
        } else {
            $errors[] = "Error al mover archivo: {$file['name']}";
        }
    }
    
    // Réponse
    echo json_encode([
        'success' => !empty($uploaded_files),
        'files' => $uploaded_files,
        'count' => count($uploaded_files),
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    error_log("Error upload terminal: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>