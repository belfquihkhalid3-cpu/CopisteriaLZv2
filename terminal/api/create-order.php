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
require_once '../config.php';

try {
    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Datos de pedido no válidos');
    }
    $customer_name = $data['customerName'] ?? '';
$customer_phone = $data['customerPhone'] ?? '';
    // Valider les données obligatoires
    if (empty($data['folders']) || !is_array($data['folders'])) {
        throw new Exception('No hay carpetas en el pedido');
    }
    
    if (empty($data['paymentMethod']['type'])) {
        throw new Exception('Método de pago no seleccionado');
    }
    
    // Pour les terminaux, accepter transfer et store
    if (!in_array($data['paymentMethod']['type'], ['transfer', 'store'])) {
        throw new Exception('Método de pago no válido para terminales');
    }
        
    // Obtenir infos terminal
    $terminal_info = getTerminalInfo();
    
    // Commencer transaction
    global $pdo;
    $pdo->beginTransaction();
    
    // Générer numéro de commande avec préfixe terminal
    $order_number = generateTerminalOrderNumber($terminal_info['id']);
    
    // Générer code de récupération
    $pickup_code = generatePickupCode();
    
    // Calculer totaux depuis les folders
    $total_price = 0;
    foreach ($data['folders'] as $folder) {
        $total_price += floatval($folder['total'] ?? 0);
    }
    
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
    
    // Déterminer user_id
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // Créer ou récupérer utilisateur invité
        $guest_user = fetchOne("SELECT id FROM users WHERE email = 'guest@terminal.local'");
        if (!$guest_user) {
            $sql_guest = "INSERT INTO users (email, first_name, last_name, password, is_admin, is_active, created_at) 
                          VALUES ('guest@terminal.local', 'Cliente', 'Invitado', 'no_password', 0, 1, NOW())";
            executeQuery($sql_guest);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $guest_user['id'];
        }
    }
    
    // Préparer les données pour la commande
    $print_config = json_encode([
        'folders' => $data['folders'],
        'paymentMethod' => $data['paymentMethod'],
        'terminal_info' => $terminal_info,
        'promoCode' => $data['promoCode'] ?? null,
        'discount' => $discount_amount
    ]);
    
    $payment_method = $data['paymentMethod']['type'] === 'store' ? 'STORE_PAYMENT' : 'BANK_TRANSFER';
    
    // Créer la commande
$order_sql = "INSERT INTO orders (
    user_id, order_number, status, payment_method, payment_status,
    total_price, total_pages, total_files, pickup_code,
    print_config, customer_notes, customer_name, customer_phone,
    source_type, terminal_id, terminal_ip, is_guest, created_at
) VALUES (?, ?, 'PENDING', ?, 'PENDING', ?, ?, ?, ?, ?, ?, ?, ?, 'TERMINAL', ?, ?, 1, NOW())";
executeQuery($order_sql, [
    $user_id,
    $order_number,
    $payment_method,
    $final_total,
    $total_pages,
    $total_files,
    $pickup_code,
    $print_config,
    $data['comments'] ?? '',
    $customer_name,    // Nouveau
    $customer_phone,   // Nouveau
    $terminal_info['id'],
    $_SERVER['REMOTE_ADDR']
]);
    
  
    
    $order_id = $pdo->lastInsertId();
    
    // Créer les items de commande
    foreach ($data['folders'] as $folder) {
        foreach ($folder['files'] as $file) {
            $config = $folder['configuration'] ?? [];
            
            $item_sql = "INSERT INTO order_items (
                order_id, file_name, file_original_name, file_path, file_size, mime_type,
                page_count, paper_size, paper_weight, color_mode, orientation, sides,
                binding, copies, unit_price, item_total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            // Utiliser prix du folder divisé par nombre de fichiers
            $unit_price = count($folder['files']) > 0 ? ($folder['total'] ?? 0) / count($folder['files']) : 0;
            $item_total = $unit_price;
            
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
    if (isset($_SESSION['user_id'])) {
        $notif_sql = "INSERT INTO notifications (user_id, order_id, title, message, notification_type, created_at) 
                      VALUES (?, ?, ?, ?, 'ORDER_CREATED', NOW())";
        
        executeQuery($notif_sql, [
            $_SESSION['user_id'],
            $order_id,
            'Pedido creado en terminal',
            "Tu pedido #{$order_number} ha sido creado en {$terminal_info['name']}. Código: {$pickup_code}"
        ]);
    }
    
    // Valider transaction
    $pdo->commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'pickup_code' => $pickup_code,
        'total_price' => $final_total,
        'terminal_info' => $terminal_info,
        'message' => 'Pedido creado exitosamente',
        'redirect_url' => "order-confirmation.php?id=" . $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback si transaction active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Error creación pedido terminal: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Fonctions utilitaires
function generateTerminalOrderNumber($terminal_id) {
    $prefix = "T{$terminal_id}-" . date('Ymd') . "-";
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $random;
}

function generatePickupCode() {
    $letters = chr(rand(65, 90)) . chr(rand(65, 90)) . chr(rand(65, 90));
    $numbers = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    return $letters . $numbers;
}

function mapFinishing($finishing) {
    $map = [
        'individual' => 'NONE',
        'grouped' => 'NONE', 
        'none' => 'NONE',
        'spiral' => 'SPIRAL',
        'staple' => 'STAPLE',
        'laminated' => 'THERMAL',
        'perforated2' => 'NONE',
        'perforated4' => 'NONE'
    ];
    
    return $map[$finishing] ?? 'NONE';
}
?>