<?php
/**
 * API pour upload de fichiers - Copisteria
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

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit();
}

require_once '../config/database.php';

try {
    // Configuration upload
    $max_file_size = 50 * 1024 * 1024; // 50MB
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
    
    $upload_dir = '../uploads/documents/';
    $temp_dir = '../uploads/temp/';
    
    // Créer les dossiers s'ils n'existent pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    // Vérifier si des fichiers ont été uploadés
    if (empty($_FILES['files'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucun fichier reçu']);
        exit();
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
        
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur upload pour {$file['name']}: " . $file['error'];
            continue;
        }
        
        // Vérifier la taille
        if ($file['size'] > $max_file_size) {
            $errors[] = "Fichier {$file['name']} trop volumineux (max 50MB)";
            continue;
        }
        
        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            $errors[] = "Extension non autorisée pour {$file['name']}";
            continue;
        }
        
        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "Type de fichier non autorisé pour {$file['name']}";
            continue;
        }
        
        // Générer un nom unique
        $unique_name = uniqid('doc_') . '_' . time() . '.' . $extension;
        $file_path = $upload_dir . $unique_name;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Compter les pages (simulation pour PDF)
            $page_count = 1;
            if ($extension === 'pdf' && extension_loaded('imagick')) {
                try {
                    $imagick = new Imagick($file_path);
                    $page_count = $imagick->getNumberImages();
                    $imagick->clear();
                } catch (Exception $e) {
                    // Garder 1 par défaut si erreur
                    error_log("Erreur comptage pages PDF: " . $e->getMessage());
                }
            }
            
            // Calculer le hash du fichier
            $file_hash = hash_file('sha256', $file_path);
            
            // Vérifier si le fichier existe déjà
            $existing = fetchOne("SELECT id FROM files WHERE file_hash = ? AND user_id = ?", 
                                [$file_hash, $_SESSION['user_id']]);
            
            if ($existing) {
                unlink($file_path); // Supprimer le doublon
                $errors[] = "Fichier {$file['name']} déjà uploadé";
                continue;
            }
            
            // Insérer en base de données
            $sql = "INSERT INTO files (user_id, original_name, stored_name, file_path, file_size, mime_type, page_count, file_hash, created_at, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";
            
            $stmt = executeQuery($sql, [
                $_SESSION['user_id'],
                $file['name'],
                $unique_name,
                $file_path,
                $file['size'],
                $mime_type,
                $page_count,
                $file_hash
            ]);
            
            if ($stmt) {
                $file_id = getLastInsertId();
                $uploaded_files[] = [
                    'id' => $file_id,
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'pages' => $page_count,
                    'type' => $mime_type,
                    'stored_name' => $unique_name
                ];
            } else {
                unlink($file_path);
                $errors[] = "Erreur base de données pour {$file['name']}";
            }
            
        } else {
            $errors[] = "Erreur déplacement fichier {$file['name']}";
        }
    }
    
    // Réponse
    $response = [
        'success' => !empty($uploaded_files),
        'files' => $uploaded_files,
        'count' => count($uploaded_files)
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    if (empty($uploaded_files)) {
        http_response_code(400);
        $response['error'] = 'Aucun fichier n\'a pu être uploadé';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Erreur upload: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur lors de l\'upload']);
}
?>