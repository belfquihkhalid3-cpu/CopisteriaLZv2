<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $printers = [];
    
    // Método 1: PowerShell en Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = 'powershell -Command "Get-WmiObject -Class Win32_Printer | Select-Object Name, DriverName, PortName | ConvertTo-Json"';
        $output = shell_exec($command);
        
        if ($output) {
            $winPrinters = json_decode($output, true);
            if (is_array($winPrinters)) {
                foreach ($winPrinters as $printer) {
                    $printers[] = [
                        'name' => $printer['Name'],
                        'driver' => $printer['DriverName'] ?? '',
                        'port' => $printer['PortName'] ?? '',
                        'type' => detectPrinterType($printer['Name'], $printer['DriverName'] ?? '')
                    ];
                }
            }
        }
    }
    
    // Método 2: CUPS en Linux/Mac
    else {
        $command = 'lpstat -p -d 2>/dev/null';
        $output = shell_exec($command);
        
        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (preg_match('/printer (.+) is/', $line, $matches)) {
                    $printers[] = [
                        'name' => $matches[1],
                        'driver' => '',
                        'port' => '',
                        'type' => detectPrinterType($matches[1])
                    ];
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'printers' => $printers]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function detectPrinterType($name, $driver = '') {
    $name = strtolower($name . ' ' . $driver);
    
    // Detectar por palabras clave
    if (strpos($name, 'color') !== false || 
        strpos($name, 'colour') !== false ||
        strpos($name, 'clj') !== false ||
        strpos($name, 'cp') !== false) {
        return 'COLOR';
    }
    
    if (strpos($name, 'laser') !== false ||
        strpos($name, 'mono') !== false ||
        strpos($name, 'black') !== false) {
        return 'BW';
    }
    
    return 'BOTH'; // Por defecto
}
?>