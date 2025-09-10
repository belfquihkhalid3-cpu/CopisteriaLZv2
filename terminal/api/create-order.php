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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/security_headers.php';
require_once '../config.php';

try {
    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos de pedido no válidos');
    }
    
    // Valider les données obligatoires
    if (empty($data['folders']) || !is_array($data['folders'])) {
        throw new Exception('No hay carpetas en el pedido');
    }
    
    if (empty($data['paymentMethod']['type'])) {
        throw new Exception('Método de pago no seleccionado');
    }
    
    // Pour les terminaux, seul le paiement en magasin est autorisé
    if ($data['paymentMethod']['type'] !== 'store') {
        throw new Exception('Solo está disponible el pago en tienda para terminales');
    }
    
    // Obtenir infos terminal
    $terminal_info = getTerminalInfo();
    
    // Commencer transaction
    beginTransaction();
    
    // Générer numéro de commande avec préfixe terminal
    $order_number = generateTerminalOrderNumber($terminal_info['id']);
    
    // Générer code de récupération
    $pickup_code = generatePickupCode();
    
    // Calculer totaux
    $total_price = $data['finalTotal'] ?? $data['total'] ?? 0;
    $total_files = 0;
    $total_pages = 0;

    foreach ($data['folders'] as $folder) {
        $total_files += count($folder['files'] ?? []);
        foreach ($folder['files'] as $file) {
            $total_pages += ($file['pages'] ?? 1) * ($folder['copies'] ?? 1);
        }
    }
    
    // Appliquer remise si code promo
    $discount_amount = $data['discount'] ?? 0;
    $final_total = $total_price - $discount_amount;
    
    // Déterminer user_id (null pour invités)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Créer la commande terminal
    $order_sql = "INSERT INTO orders (
        user_id, order_number, status, payment_method, payment_status,
        total_price, total_pages, total_files, pickup_code,
        print_config, customer_notes, source_type, terminal_id, 
        terminal_ip, is_guest, created_at
    ) VALUES (?, ?, 'PENDING', 'STORE_PAYMENT', 'PENDING', ?, ?, ?, ?, ?, ?, 'TERMINAL', ?, ?, ?, NOW())";
    
    $print_config = json_encode([
        'folders' => $data['folders'],
        'paymentMethod' => $data['paymentMethod'],
        'terminal_info' => $terminal_info,
        'promoCode' => $data['promoCode'] ?? null,
        'discount' => $discount_amount
    ]);
    
    $stmt = executeQuery($order_sql, [
        $user_id,
        $order_number,
        $final_total,
        $total_pages,
        $total_files,
        $pickup_code,
        $print_config,
        $data['comments'] ?? '',
        $terminal_info['id'],
        $_SERVER['REMOTE_ADDR'],
        $user_id === null ? 1 : 0
    ]);
    
    if (!$stmt) {
        throw new Exception('Error al crear el pedido');
    }
    
    $order_id = getLastInsertId();
    
    // Créer les items de commande
    foreach ($data['folders'] as $folder) {
        foreach ($folder['files'] as $file) {
            $config = $folder['configuration'] ?? [];
            
            $item_sql = "INSERT INTO order_items (
                order_id, file_name, file_original_name, file_path, file_size, mime_type,
                page_count, paper_size, paper_weight, color_mode, orientation, sides,
                binding, copies, unit_price, item_total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            // Calculer prix unitaire
            $unit_price = calculateUnitPrice($config);
            $item_total = $unit_price * ($file['pages'] ?? 1) * ($folder['copies'] ?? 1);
            
            executeQuery($item_sql, [
                $order_id,
                $file['stored_name'] ?? $file['name'],
                $file['name'],
                $file['file_path'] ?? '',
                $file['size'] ?? 0,
                $file['type'] ?? 'application/pdf',
                $file['pages'] ?? 1,
                $config['paperSize'] ?? 'A4',
                $config['paperWeight'] ?? '80g',
                strtoupper($config['colorMode'] ?? 'BW'),
                strtoupper($config['orientation'] ?? 'PORTRAIT'),
                strtoupper($config['sides'] ?? 'DOUBLE'),
                mapFinishing($config['finishing'] ?? 'none'),
                $folder['copies'] ?? 1,
                $unit_price,
                $item_total
            ]);
        }
    }
    
    // Créer notification seulement si utilisateur connecté
    if ($user_id) {
        $notif_sql = "INSERT INTO notifications (user_id, order_id, title, message, notification_type, created_at) 
                      VALUES (?, ?, ?, ?, 'ORDER_CREATED', NOW())";
        
        executeQuery($notif_sql, [
            $user_id,
            $order_id,
            'Pedido creado en terminal',
            "Tu pedido #{$order_number} ha sido creado en {$terminal_info['name']}."
        ]);
    }
    
    // Valider transaction
    commitTransaction();
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'pickup_code' => $pickup_code,
        'total_price' => $final_total,
        'terminal_info' => $terminal_info,
        'message' => 'Pedido creado exitosamente en terminal'
    ]);
    
} catch (Exception $e) {
    rollbackTransaction();
    error_log("Erreur création commande terminal: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Fonction pour générer numéro commande terminal
function generateTerminalOrderNumber($terminal_id) {
    $prefix = "T{$terminal_id}-" . date('Ymd') . "-";
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $random;
}
?>