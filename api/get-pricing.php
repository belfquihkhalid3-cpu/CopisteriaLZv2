<?php


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../includes/security_headers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

           require_once 'includes/csrf.php';
    
    // Vérifier token AVANT tout traitement
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Token CSRF invalide - Action bloquée');
    }
        // Récupérer un tarif spécifique
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['paperSize']) || !isset($data['paperWeight']) || !isset($data['colorMode'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres manquants']);
            exit();
        }
        
        $sql = "SELECT price_per_page FROM pricing 
                WHERE paper_size = ? AND paper_weight = ? AND color_mode = ? AND is_active = 1
                ORDER BY valid_from DESC LIMIT 1";
        
        $result = fetchOne($sql, [$data['paperSize'], $data['paperWeight'], $data['colorMode']]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'price_per_page' => floatval($result['price_per_page'])
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tarif non trouvé']);
        }
        
    } else {
        // Récupérer tous les tarifs (GET)
        $sql = "SELECT paper_size, paper_weight, color_mode, price_per_page 
                FROM pricing 
                WHERE is_active = 1 
                ORDER BY paper_size, paper_weight, color_mode";
        
        $results = fetchAll($sql);
        
        // Organiser les données comme dans le JavaScript
        $pricing = [];
        foreach ($results as $row) {
            $pricing[$row['paper_size']][$row['paper_weight']][$row['color_mode']] = floatval($row['price_per_page']);
        }
        
        echo json_encode([
            'success' => true,
            'pricing' => $pricing
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur API pricing: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>