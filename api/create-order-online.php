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

// Vérifier authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

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
    
    // Désactiver temporairement les autres modes de paiement
  // Désactiver temporairement les autres modes de paiement
if ($data['paymentMethod']['type'] !== 'transfer') {
    throw new Exception('Solo está disponible la transferencia bancaria actualmente');
}
    
    // Commencer transaction
    beginTransaction();
    
    // Générer numéro de commande
    $order_number = generateOrderNumber();
    
    // Générer code de récupération
    $pickup_code = generatePickupCode();
    
// Utiliser le prix du résumé commande
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

    $billing_data = null;
if (isset($data['billingData'])) {
    $billing_data = json_encode($data['billingData']);
}
  $print_config = json_encode([
        'folders' => $data['folders'],
        'paymentMethod' => $data['paymentMethod'],
        'promoCode' => $data['promoCode'] ?? null,
        'discount' => $discount_amount
    ]);
    // Créer la commande
// Créer la commande
$order_sql = "INSERT INTO orders (
    user_id, order_number, status, payment_method, payment_status,
    total_price, total_pages, total_files, pickup_code,
    print_config, customer_notes, billing_data, created_at
) VALUES (?, ?, 'PENDING', 'BANK_TRANSFER', 'PENDING', ?, ?, ?, ?, ?, ?, ?, NOW())";
    
  
    
   $stmt = executeQuery($order_sql, [
    $_SESSION['user_id'],
    $order_number,
    $final_total,
    $total_pages,
    $total_files,
    $pickup_code,
    $print_config,
    $data['comments'] ?? '',
    $billing_data  // Ajouter ici
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
    
    // Créer notification pour l'utilisateur
    $notif_sql = "INSERT INTO notifications (user_id, order_id, title, message, notification_type, created_at) 
                  VALUES (?, ?, ?, ?, 'ORDER_CREATED', NOW())";
    
    executeQuery($notif_sql, [
        $_SESSION['user_id'],
        $order_id,
        'Pedido creado',
        "Tu pedido #{$order_number} ha sido creado correctamente. Código de recogida: {$pickup_code}"
    ]);
    
    // Valider transaction
    commit();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'pickup_code' => $pickup_code,
        'total_price' => $final_total,
        'message' => 'Pedido creado correctamente'
    ]);
    
} catch (Exception $e) {
    rollback();
    error_log("Error creación pedido: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Fonctions utilitaires
function generateOrderNumber() {
    return 'COP-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function generatePickupCode() {
    $letters = chr(rand(65, 90)) . chr(rand(65, 90)) . chr(rand(65, 90));
    $numbers = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    return $letters . $numbers;
}

function calculateUnitPrice($config) {
    // Grille de prix (même que dans main.js)
    $prices = [
        'A4' => [
            '80g' => ['bw' => 0.05, 'color' => 0.15],
            '160g' => ['bw' => 0.07, 'color' => 0.20],
            '280g' => ['bw' => 0.12, 'color' => 0.30]
        ],
        'A3' => [
            '80g' => ['bw' => 0.10, 'color' => 0.25],
            '160g' => ['bw' => 0.15, 'color' => 0.35],
            '280g' => ['bw' => 0.20, 'color' => 0.40]
        ]
    ];
    
    $size = $config['paperSize'] ?? 'A4';
    $weight = $config['paperWeight'] ?? '80g';
    $color = $config['colorMode'] ?? 'bw';
    
    return $prices[$size][$weight][$color] ?? 0.05;
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