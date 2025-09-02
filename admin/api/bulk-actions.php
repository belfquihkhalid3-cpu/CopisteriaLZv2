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
    $action = $input['action'] ?? '';
    $order_ids = $input['order_ids'] ?? [];
    
    if (empty($order_ids) || !is_array($order_ids)) {
        throw new Exception('IDs de pedidos requeridos');
    }
    
    $success_count = 0;
    
    beginTransaction();
    
    switch ($action) {
        case 'mark_confirmed':
            foreach ($order_ids as $id) {
                $stmt = executeQuery("UPDATE orders SET status = 'CONFIRMED', updated_at = NOW() WHERE id = ?", [$id]);
                if ($stmt) $success_count++;
            }
            break;
            
        case 'mark_ready':
            foreach ($order_ids as $id) {
                $stmt = executeQuery("UPDATE orders SET status = 'READY', updated_at = NOW() WHERE id = ?", [$id]);
                if ($stmt) $success_count++;
            }
            break;
            
        case 'mark_completed':
            foreach ($order_ids as $id) {
                $stmt = executeQuery("UPDATE orders SET status = 'COMPLETED', completed_at = NOW(), updated_at = NOW() WHERE id = ?", [$id]);
                if ($stmt) $success_count++;
            }
            break;
            
        case 'export_selected':
            // Créer ZIP avec fichiers sélectionnés
            $zip_path = createBulkZip($order_ids);
            commit();
            
            echo json_encode([
                'success' => true,
                'download_url' => 'download-bulk.php?file=' . basename($zip_path)
            ]);
            exit();
            
        default:
            throw new Exception('Acción no válida');
    }
    
    commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$success_count pedidos actualizados correctamente"
    ]);
    
} catch (Exception $e) {
    rollback();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function createBulkZip($order_ids) {
    $zip = new ZipArchive();
    $zip_filename = 'pedidos-bulk-' . date('Y-m-d-H-i') . '.zip';
    $zip_path = sys_get_temp_dir() . '/' . $zip_filename;
    
    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
        throw new Exception('Error creando archivo ZIP');
    }
    
    foreach ($order_ids as $order_id) {
        $order = fetchOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
        $files = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order_id]);
        
        $folder_name = "pedido-{$order['order_number']}/";
        
        foreach ($files as $file) {
            $file_path = '../uploads/documents/' . $file['file_name'];
            if (file_exists($file_path)) {
                $zip->addFile($file_path, $folder_name . $file['file_original_name']);
            }
        }
    }
    
    $zip->close();
    return $zip_path;
}
?>