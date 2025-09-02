<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

require_once '../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $updates = $input['updates'] ?? [];
    
    if (empty($updates)) {
        throw new Exception('No hay actualizaciones para procesar');
    }
    
    beginTransaction();
    
    $updated_count = 0;
    
    foreach ($updates as $update) {
        $paper_size = $update['paper_size'];
        $paper_weight = $update['paper_weight'];
        $color_mode = $update['color_mode'];
        $new_price = floatval($update['price']);
        
        if ($new_price < 0 || $new_price > 10) {
            throw new Exception("Precio inválido para {$paper_size} {$paper_weight} {$color_mode}");
        }
        
        $sql = "UPDATE pricing 
                SET price_per_page = ?, updated_at = NOW() 
                WHERE paper_size = ? AND paper_weight = ? AND color_mode = ? AND is_active = 1";
        
        $stmt = executeQuery($sql, [$new_price, $paper_size, $paper_weight, $color_mode]);
        
        if ($stmt && $stmt->rowCount() > 0) {
            $updated_count++;
        }
        
        // Log del cambio
        error_log("Admin {$_SESSION['admin_id']} updated pricing: {$paper_size} {$paper_weight} {$color_mode} = €{$new_price}");
    }
    
    commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Se actualizaron {$updated_count} precios correctamente",
        'updated_count' => $updated_count
    ]);
    
} catch (Exception $e) {
    rollback();
    error_log("Error updating pricing: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>