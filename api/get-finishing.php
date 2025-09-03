<?php
/**
 * API pour récupérer les coûts de finition - Copisteria
 */

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
        // Récupérer un coût spécifique
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['finishing'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Type de finition manquant']);
            exit();
        }
        
        // Mapper les codes frontend vers les noms de service
        $finishing_map = [
            'individual' => 'INDIVIDUAL',
            'grouped' => 'GROUPED', 
            'none' => 'NONE',
            'spiral' => 'SPIRAL',
            'staple' => 'STAPLE',
            'laminated' => 'LAMINATING',
            'perforated2' => 'PERFORATION_2',
            'perforated4' => 'PERFORATION_4'
        ];
        
        $service_name = $finishing_map[$data['finishing']] ?? null;
        
        if (!$service_name) {
            http_response_code(404);
            echo json_encode(['error' => 'Type de finition non reconnu']);
            exit();
        }
        
        $sql = "SELECT cost, cost_type FROM finishing_costs 
                WHERE service_name = ? AND is_active = 1";
        
        $result = fetchOne($sql, [$service_name]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'cost' => floatval($result['cost']),
                'cost_type' => $result['cost_type']
            ]);
        } else {
            // Retourner 0 pour les finitions gratuites
            echo json_encode([
                'success' => true,
                'cost' => 0.00,
                'cost_type' => 'FIXED'
            ]);
        }
        
    } else {
        // Récupérer tous les coûts de finition (GET)
        $sql = "SELECT service_name, cost, cost_type FROM finishing_costs WHERE is_active = 1";
        $results = fetchAll($sql);
        
        // Organiser les données comme dans le JavaScript
        $finishing_costs = [
            'individual' => 0,
            'grouped' => 0,
            'none' => 0,
            'spiral' => 2.50,
            'staple' => 0.50,
            'laminated' => 5.00,
            'perforated2' => 1.00,
            'perforated4' => 1.50
        ];
        
        // Mettre à jour avec les données de la BDD
        foreach ($results as $row) {
            switch ($row['service_name']) {
                case 'SPIRAL':
                    $finishing_costs['spiral'] = floatval($row['cost']);
                    break;
                case 'STAPLE':
                    $finishing_costs['staple'] = floatval($row['cost']);
                    break;
                case 'LAMINATING':
                    $finishing_costs['laminated'] = floatval($row['cost']);
                    break;
                case 'PERFORATION_2':
                    $finishing_costs['perforated2'] = floatval($row['cost']);
                    break;
                case 'PERFORATION_4':
                    $finishing_costs['perforated4'] = floatval($row['cost']);
                    break;
            }
        }
        
        echo json_encode([
            'success' => true,
            'finishing_costs' => $finishing_costs
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur API finishing: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>