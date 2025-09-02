<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$order_id = $_GET['order'] ?? 0;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Récupérer la commande et ses fichiers
$order = fetchOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
if (!$order) {
    header('Location: orders.php');
    exit();
}

$files = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order_id]);

if (empty($files)) {
    header('Location: orders.php?error=no_files');
    exit();
}

// Créer ZIP avec tous les fichiers
$zip = new ZipArchive();
$zip_filename = "pedido-{$order['order_number']}-archivos.zip";
$zip_path = sys_get_temp_dir() . '/' . $zip_filename;

if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    die('Error al crear archivo ZIP');
}

// Ajouter fichiers au ZIP
foreach ($files as $index => $file) {
    $file_path = '../uploads/documents/' . $file['file_name'];
    
    if (file_exists($file_path)) {
        // Nom avec configuration pour éviter confusion
        $config_suffix = "({$file['copies']}copias-{$file['paper_size']}-{$file['color_mode']})";
        $extension = pathinfo($file['file_original_name'], PATHINFO_EXTENSION);
        $basename = pathinfo($file['file_original_name'], PATHINFO_FILENAME);
        
        $zip_file_name = sprintf("%02d-%s-%s.%s", 
            $index + 1, 
            $basename, 
            $config_suffix, 
            $extension
        );
        
        $zip->addFile($file_path, $zip_file_name);
    }
}

// Ajouter fichier de configuration
$config_content = "PEDIDO: {$order['order_number']}\n";
$config_content .= "FECHA: " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n";
$config_content .= "CÓDIGO RECOGIDA: {$order['pickup_code']}\n";
$config_content .= "TOTAL: €" . number_format($order['total_price'], 2) . "\n\n";

$config_content .= "ARCHIVOS A IMPRIMIR:\n";
$config_content .= str_repeat("=", 50) . "\n";

foreach ($files as $index => $file) {
    $config_content .= sprintf("%02d. %s\n", $index + 1, $file['file_original_name']);
    $config_content .= "    - Páginas: {$file['page_count']}\n";
    $config_content .= "    - Copias: {$file['copies']}\n";
    $config_content .= "    - Papel: {$file['paper_size']} {$file['paper_weight']}\n";
    $config_content .= "    - Color: " . ($file['color_mode'] === 'BW' ? 'Blanco y Negro' : 'Color') . "\n";
    $config_content .= "    - Caras: " . ($file['sides'] === 'SINGLE' ? 'Una cara' : 'Doble cara') . "\n";
    if ($file['binding'] && $file['binding'] !== 'NONE') {
        $config_content .= "    - Encuadernado: {$file['binding']}\n";
    }
    $config_content .= "\n";
}

$zip->addFromString('INSTRUCCIONES-IMPRESION.txt', $config_content);

$zip->close();

// Headers para descarga
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
header('Content-Length: ' . filesize($zip_path));

// Enviar archivo
readfile($zip_path);

// Limpiar archivo temporal
unlink($zip_path);
exit();
?>