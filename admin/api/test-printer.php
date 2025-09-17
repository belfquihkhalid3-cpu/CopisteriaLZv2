<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $printer_id = $input['printer_id'] ?? 0;
    
    $printer = fetchOne("SELECT * FROM printers WHERE id = ?", [$printer_id]);
    
    if (!$printer) {
        throw new Exception('Impresora no encontrada');
    }
    
    // Crear archivo de test
    $test_content = "
        PRUEBA DE IMPRESIÓN
        ===================
        
        Impresora: {$printer['name']}
        Tipo: {$printer['type']}
        Fecha: " . date('d/m/Y H:i:s') . "
        
        Esta es una página de prueba para verificar
        que la impresora funciona correctamente.
        
        ✓ Texto normal
        ✓ Caracteres especiales: áéíóúñü¡¿
        ✓ Números: 1234567890
        
        Si puede leer este texto, la impresora
        está funcionando correctamente.
    ";
    
    $test_file = sys_get_temp_dir() . '/test_print_' . time() . '.txt';
    file_put_contents($test_file, $test_content);
    
    // Enviar a impresora
    $result = sendToPrinter($printer['system_name'], $test_file);
    
    // Limpiar archivo temporal
    unlink($test_file);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Página de prueba enviada']);
    } else {
        throw new Exception('Error enviando a la impresora');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function sendToPrinter($printerName, $filePath) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $command = 'print /D:"' . $printerName . '" "' . $filePath . '"';
        $output = shell_exec($command);
        return $output !== null;
    } else {
        // Linux/Mac
        $command = 'lp -d "' . $printerName . '" "' . $filePath . '"';
        $output = shell_exec($command);
        return $output !== null;
    }
}
?>