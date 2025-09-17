<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $file = $input['file'] ?? '';
    $printer_id = $input['printer_id'] ?? 0;
    $config = $input['config'] ?? [];
    
    $printer = fetchOne("SELECT * FROM printers WHERE id = ?", [$printer_id]);
    $file_path = realpath('../../uploads/documents/' . $file);
    
    if (!$printer) {
        throw new Exception('Imprimante non trouvée');
    }
    
    if (!$file_path || !file_exists($file_path)) {
        throw new Exception('Fichier non trouvé: ' . $file);
    }
    
    // Vérifier compatibilité couleur/imprimante
    if ($config['colorMode'] === 'color' && $printer['type'] === 'BW') {
        throw new Exception('La impresora ' . $printer['name'] . ' no soporta color');
    }
    
    // Imprimer avec Adobe Acrobat DC
    $success = printFileWithAcrobat($printer['system_name'], $file_path, $config);
    
    if ($success) {
        // Log de l'impression
        error_log("Impression réussie: {$file} sur {$printer['name']} - {$config['copies']} copies - {$config['colorMode']}");
        echo json_encode([
            'success' => true, 
            'message' => "Archivo impreso: {$config['copies']} copias en {$printer['name']}"
        ]);
    } else {
        throw new Exception('Error enviando archivo a impresora');
    }
    
} catch (Exception $e) {
    error_log("Erreur impression: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function printFileWithAcrobat($printerName, $filePath, $config) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        
        $copies = max(1, intval($config['copies'] ?? 1));
        $acrobatPath = "C:\\Program Files\\Adobe\\Acrobat DC\\Acrobat\\Acrobat.exe";
        
        // Vérifier qu'Acrobat existe
        if (!file_exists($acrobatPath)) {
            error_log("Adobe Acrobat DC non trouvé à: $acrobatPath");
            return false;
        }
        
        // Imprimer chaque copie avec Adobe Acrobat DC
        for ($i = 0; $i < $copies; $i++) {
            $command = '"' . $acrobatPath . '" /t "' . $filePath . '" "' . $printerName . '"';
            $output = shell_exec($command . " 2>&1");
            
            // Log chaque impression
            error_log("Impression copie " . ($i + 1) . "/$copies - Commande: $command");
            
            // Délai entre copies pour laisser Acrobat finir proprement
            if ($copies > 1 && $i < $copies - 1) {
                sleep(8); // 8 secondes entre chaque copie
            }
        }
        
        return true;
        
    } else {
        // Linux/Mac avec CUPS
        $options = [];
        if ($config['copies'] > 1) {
            $options[] = "-n {$config['copies']}";
        }
        if ($config['sides'] === 'double') {
            $options[] = "-o sides=two-sided-long-edge";
        }
        if ($config['orientation'] === 'landscape') {
            $options[] = "-o orientation-requested=4";
        }
        
        $optionsStr = implode(' ', $options);
        $command = "lp -d \"$printerName\" $optionsStr \"$filePath\"";
        $output = shell_exec($command . " 2>&1");
        
        return $output === null || strpos($output, 'error') === false;
    }
}
?>