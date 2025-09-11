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
    
    // Pour les terminaux, accepter transfer et store
    if (!in_array($data['paymentMethod']['type'], ['transfer', 'store'])) {
        throw new Exception('Método de pago no válido para terminales');
    }
    
    // Obtenir infos terminal
    $terminal_info = getTerminalInfo();
    
    // Variables pour transaction
    global $pdo;
    $transaction_started = false;
    
    // Commencer transaction
    $pdo->beginTransaction();
    $transaction_started = true;
    
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
    
    // Déterminer user_id - utiliser 0 pour invités ou créer utilisateur invité
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // Créer ou récupérer utilisateur invité
        $guest_user = fetchOne("SELECT id FROM users WHERE email = 'guest@terminal.local'");
        if (!$guest_user) {
            $sql_guest = "INSERT INTO users (email, first_name, last_name, password, is_admin, is_active, created_at) 
                          VALUES ('guest@terminal.local', 'Cliente', 'Invitado', 'no_password', 0, 1, NOW())";
            executeQuery($sql_guest);
            $user_id = getLastInsertId();
        } else {
            $user_id = $guest_user['id'];
        }
    }
    
    // Créer la commande terminal
    $order_sql = "INSERT INTO orders (
        user_id, order_number, status, payment_method, payment_status,
        total_price, total_pages, total_files, pickup_code,
        print_config, customer_notes, source_type, terminal_id, 
        terminal_ip, is_guest, created_at
    ) VALUES (?, ?, 'PENDING', ?, 'PENDING', ?, ?, ?, ?, ?, ?, 'TERMINAL', ?, ?, ?, NOW())";
    
    $print_config = json_encode([
        'folders' => $data['folders'],
        'paymentMethod' => $data['paymentMethod'],
        'terminal_info' => $terminal_info,
        'promoCode' => $data['promoCode'] ?? null,
        'discount' => $discount_amount
    ]);
    
    // Déterminer le mode de paiement
    $payment_method = $data['paymentMethod']['type'] === 'store' ? 'STORE_PAYMENT' : 'BANK_TRANSFER';
    
    $stmt = executeQuery($order_sql, [
        $user_id,
        $order_number,
        $payment_method,
        $final_total,
        $total_pages,
        $total_files,
        $pickup_code,
        $print_config,
        $data['comments'] ?? '',
        $terminal_info['id'],
        $_SERVER['REMOTE_ADDR'],
        !isset($_SESSION['user_id']) ? 1 : 0
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
            
            // Calculer prix unitaire (fonction à créer si n'existe pas)
            $unit_price = calculateUnitPriceSimple($config);
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
                mapFinishingSimple($config['finishing'] ?? 'none'),
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
            "Tu pedido #{$order_number} ha sido creado en {$terminal_info['name']}."
        ]);
    }
    
    // Valider transaction
    $pdo->commit();
    $transaction_started = false;
    
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
    // Rollback seulement si transaction active
    if ($transaction_started && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
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

// Fonction simple pour calculer prix unitaire
function calculateUnitPriceSimple($config) {
    $base_price = 0.05; // Prix de base par page
    
    // Ajuster selon le type de papier
    if (($config['paperWeight'] ?? '80g') === '160g') $base_price *= 1.4;
    if (($config['paperWeight'] ?? '80g') === '280g') $base_price *= 2.4;
    
    // Ajuster selon la couleur
    if (($config['colorMode'] ?? 'bw') === 'color') $base_price *= 3;
    
    return $base_price;
}

// Fonction simple pour mapper le finishing
function mapFinishingSimple($finishing) {
    $map = [
        'none' => 'NONE',
        'individual' => 'NONE', 
        'grouped' => 'NONE',
        'spiral' => 'SPIRAL',
        'staple' => 'STAPLE'
    ];
    
    return $map[$finishing] ?? 'NONE';
}
// Fonctions manquantes
function generatePickupCode() {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

function generateOrderNumber() {
    $prefix = 'COP-' . date('Y') . '-';
    $number = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    return $prefix . $number;
}
?>