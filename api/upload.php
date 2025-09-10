<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Validation sécurisée fichier
function secureFileValidation($file) {
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
    $max_size = 50 * 1024 * 1024;
    
    // Vérifier extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return false;
    }
    
    // Vérifier MIME type
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Vérifier taille
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Vérifier signature fichier (magic bytes)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($detected_type, $allowed_types);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Vérifier si l'utilisateur est connecté
// Vérifier si l'utilisateur est connecté OU en mode invité terminal
$is_guest_terminal = false;
if (!isset($_SESSION['user_id'])) {
    // Vérifier si c'est un terminal en mode invité
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, '/terminal/') !== false) {
        $is_guest_terminal = true;
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
        exit();
    }
}

require_once '../config/database.php';
require_once '../includes/security_headers.php';

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
        
        // Vérifications
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error upload {$file['name']}: código {$file['error']}";
            continue;
        }
        
        if ($file['size'] > $max_file_size) {
            $errors[] = "Archivo {$file['name']} demasiado grande (máx 50MB)";
            continue;
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            $errors[] = "Extensión no permitida: {$file['name']}";
            continue;
        }
        
        // Vérifier le type MIME réel
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "Tipo MIME no válido: {$file['name']}";
            continue;
        }
        
        // Générer nom unique
        $unique_name = uniqid('doc_') . '_' . time() . '.' . $extension;
        $file_path = $upload_dir . $unique_name;
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            $errors[] = "Error al mover archivo: {$file['name']}";
            continue;
        }
        
        // Compter les pages pour PDF
        $page_count = 1;
        if ($extension === 'pdf') {
            $page_count = countPDFPages($file_path);
        }
        
        // Calculer hash
        $file_hash = hash_file('sha256', $file_path);
        
       // Vérifier doublon SEULEMENT pour le même nom de fichier
$existing = fetchOne("SELECT id FROM files WHERE original_name = ? AND user_id = ?", 
                    [$file['name'], $_SESSION['user_id']]);

if ($existing) {
    // Créer un nom unique en ajoutant un suffixe
    $name_parts = pathinfo($file['name']);
    $base_name = $name_parts['filename'];
    $extension_orig = $name_parts['extension'];
    
    $counter = 1;
    do {
        $new_name = $base_name . "_($counter)." . $extension_orig;
        $existing = fetchOne("SELECT id FROM files WHERE original_name = ? AND user_id = ?", 
                           [$new_name, $_SESSION['user_id']]);
        $counter++;
    } while ($existing);
    
    $file['name'] = $new_name; // Utiliser le nouveau nom
}
        
        // Sauvegarder en BDD
       $user_id_for_db = $is_guest_terminal ? null : $_SESSION['user_id'];

$sql = "INSERT INTO files (user_id, original_name, stored_name, file_path, file_size, mime_type, page_count, file_hash, created_at, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";

$stmt = executeQuery($sql, [
    $user_id_for_db,  // null pour invités
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
            $errors[] = "Error base de datos: {$file['name']}";
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
        $response['message'] = 'Algunos archivos tuvieron errores';
    }
    
    if (empty($uploaded_files)) {
        http_response_code(400);
        $response['error'] = 'No se pudo subir ningún archivo';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error upload: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

// Fonction pour compter pages PDF
function countPDFPages($filepath) {
    try {
        if (extension_loaded('imagick')) {
            $imagick = new Imagick($filepath);
            $pages = $imagick->getNumberImages();
            $imagick->clear();
            return $pages;
        } elseif (class_exists('PDFInfo')) {
            $pdf = new PDFInfo($filepath);
            return $pdf->pages;
        } else {
            // Méthode basique avec regex
            $content = file_get_contents($filepath);
            $pages = preg_match_all("/\/Page\W/", $content);
            return max(1, $pages);
        }
    } catch (Exception $e) {
        error_log("Erreur comptage pages PDF: " . $e->getMessage());
        return 1;
    }
}
?>