<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['action'] ?? '';
$print_service = 'http://localhost:5000';

// Fonction pour logger les erreurs
function logError($message) {
    error_log("PRINT-PROXY: " . $message);
}

try {
    switch ($action) {
        case 'detect-printers':
            logError("Tentative détection imprimantes...");
            
            $url = $print_service . '/detect-printers';
            
            // Vérifier si le service répond
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                logError("Service Python non accessible");
                echo json_encode([
                    'success' => false, 
                    'error' => 'Service Python non accessible sur localhost:5000',
                    'debug' => 'Vérifiez que python print-service.py fonctionne'
                ]);
                exit;
            }
            
            // Vérifier si c'est du JSON valide
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                logError("Réponse non-JSON: " . substr($response, 0, 200));
                echo json_encode([
                    'success' => false, 
                    'error' => 'Réponse invalide du service Python',
                    'debug' => 'Réponse reçue: ' . substr($response, 0, 200)
                ]);
                exit;
            }
            
            logError("Succès détection: " . count($decoded['printers'] ?? []) . " imprimantes");
            echo $response;
            break;
            
        case 'print-order':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                logError("Tentative impression...");
                
                $input = file_get_contents('php://input');
                logError("Données reçues: " . substr($input, 0, 200));
                
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => $input,
                        'timeout' => 60
                    ]
                ]);
                
                $url = $print_service . '/print-order';
                $response = @file_get_contents($url, false, $context);
                
                if ($response === false) {
                    logError("Échec impression - service non accessible");
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Service d\'impression non accessible'
                    ]);
                    exit;
                }
                
                logError("Réponse impression: " . substr($response, 0, 200));
                echo $response;
            } else {
                echo json_encode(['error' => 'Méthode POST requise']);
            }
            break;
            
        case 'status':
            $url = $print_service . '/status';
            $response = @file_get_contents($url);
            
            if ($response === false) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Service hors ligne'
                ]);
            } else {
                echo $response;
            }
            break;
            
        case 'test':
            // Test de connectivité
            $url = $print_service . '/status';
            $start = microtime(true);
            $response = @file_get_contents($url);
            $time = round((microtime(true) - $start) * 1000);
            
            echo json_encode([
                'success' => $response !== false,
                'url' => $url,
                'response_time' => $time . 'ms',
                'response_preview' => $response ? substr($response, 0, 100) : 'Aucune réponse'
            ]);
            break;
            
        default:
            echo json_encode(['error' => 'Action non valide: ' . $action]);
    }
    
} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur proxy: ' . $e->getMessage(),
        'debug' => 'Vérifiez les logs PHP'
    ]);
}
?>