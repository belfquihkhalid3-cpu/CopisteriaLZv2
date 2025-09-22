<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $order_id = $_GET['order_id'] ?? 0;
    
    if (!$order_id) {
        throw new Exception('ID de commande manquant');
    }
    
    $order = fetchOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
    
    if (!$order) {
        throw new Exception('Commande non trouvée');
    }
    
    $print_config = json_decode($order['print_config'], true) ?: [];
    
    // Récupérer TOUS les dossiers et leurs configurations
    $folders_data = [];
    
    foreach ($print_config['folders'] ?? [] as $folderIndex => $folder) {
        $files_data = [];
        
        foreach ($folder['files'] ?? [] as $file) {
            $files_data[] = [
                'name' => $file['name'],
                'stored_name' => $file['stored_name'] ?? $file['name'],
                'pages' => $file['pages'] ?? 1,
                'url' => "https://tintaexpreslz.com/uploads/documents/" . ($file['stored_name'] ?? $file['name'])
            ];
        }
        
        $config = $folder['configuration'] ?? [];
        
        $folders_data[] = [
            'name' => $folder['name'] ?? "Dossier " . ($folderIndex + 1),
            'copies' => $folder['copies'] ?? 1,
            'files' => $files_data,
            'configuration' => [
                'paperSize' => $config['paperSize'] ?? 'A4',
                'paperWeight' => $config['paperWeight'] ?? '80g',
                'colorMode' => $config['colorMode'] ?? 'bw',
                'sides' => $config['sides'] ?? 'single',
                'orientation' => $config['orientation'] ?? 'portrait',
                'finishing' => $config['finishing'] ?? 'individual',
                'pagesPerSheet' => $config['pagesPerSheet'] ?? 1
            ]
        ];
    }
    
    // Données client
    $customer_data = $print_config['customer_data'] ?? [];
    $customer_name = $customer_data['name'] ?? ($order['customer_name'] ?? 'Cliente Invitado');
    
    echo json_encode([
        'success' => true,
        'order_number' => $order['order_number'],
        'customer_name' => $customer_name,
        'customer_phone' => $order['customer_phone'] ?? '',
        'folders' => $folders_data,
        'total_files' => count($print_config['folders'] ?? [])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>