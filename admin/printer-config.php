<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupérer imprimantes configurées
$printers = fetchAll("SELECT * FROM printers ORDER BY name");

if ($_POST) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $system_name = $_POST['system_name'];
    $capabilities = json_encode($_POST['capabilities'] ?? []);
    
    executeQuery("INSERT INTO printers (name, type, system_name, capabilities, is_active) VALUES (?, ?, ?, ?, 1)", 
                [$name, $type, $system_name, $capabilities]);
    
    header('Location: printer-config.php?success=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Impresoras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 min-h-screen p-6">
        
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-print mr-2 text-blue-500"></i>Configuración de Impresoras
            </h1>
        </div>

        <!-- Detectar impresoras del sistema -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Detectar Impresoras del Sistema</h3>
            <button onclick="detectPrinters()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                <i class="fas fa-search mr-2"></i>Detectar Impresoras
            </button>
            <div id="detected-printers" class="mt-4"></div>
        </div>

        <!-- Agregar nueva impresora -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Agregar Nueva Impresora</h3>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Impresora</label>
                        <input type="text" name="name" required placeholder="Ej: HP LaserJet Color" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="COLOR">Color</option>
                            <option value="BW">Blanco y Negro</option>
                            <option value="BOTH">Ambos</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Sistema</label>
                    <input type="text" name="system_name" required placeholder="Nombre exacto en Windows (Ej: HP LaserJet Pro MFP M428fdw)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidades</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="capabilities[]" value="duplex" class="mr-2">
                            Doble cara
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="capabilities[]" value="color" class="mr-2">
                            Color
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="capabilities[]" value="a3" class="mr-2">
                            Formato A3
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="capabilities[]" value="staple" class="mr-2">
                            Grapado
                        </label>
                    </div>
                </div>

                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600">
                    <i class="fas fa-plus mr-2"></i>Agregar Impresora
                </button>
            </form>
        </div>

        <!-- Lista de impresoras configuradas -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Impresoras Configuradas</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($printers as $printer): ?>
                    <?php $capabilities = json_decode($printer['capabilities'], true) ?? []; ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($printer['name']) ?></h4>
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-<?= $printer['type'] === 'COLOR' ? 'purple' : ($printer['type'] === 'BW' ? 'gray' : 'blue') ?>-100 text-<?= $printer['type'] === 'COLOR' ? 'purple' : ($printer['type'] === 'BW' ? 'gray' : 'blue') ?>-800">
                                <?= $printer['type'] === 'COLOR' ? 'Color' : ($printer['type'] === 'BW' ? 'B/N' : 'Ambos') ?>
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-600 mb-2">
                            <strong>Sistema:</strong> <?= htmlspecialchars($printer['system_name']) ?>
                        </div>
                        
                        <div class="flex flex-wrap gap-1 mb-3">
                            <?php foreach ($capabilities as $capability): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                    <?= htmlspecialchars($capability) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex space-x-2">
                            <button onclick="testPrinter(<?= $printer['id'] ?>)" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                <i class="fas fa-vial mr-1"></i>Test
                            </button>
                            <button onclick="deletePrinter(<?= $printer['id'] ?>)" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                <i class="fas fa-trash mr-1"></i>Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

<script>
async function detectPrinters() {
    const container = document.getElementById('detected-printers');
    container.innerHTML = '<p>Detectando impresoras...</p>';
    
    try {
        // Usar API del navegador si está disponible
        if ('getInstalledPrinters' in navigator) {
            const printers = await navigator.getInstalledPrinters();
            displayDetectedPrinters(printers);
        } else {
            // Fallback: detectar via PHP
            const response = await fetch('api/detect-printers.php');
            const result = await response.json();
            displayDetectedPrinters(result.printers || []);
        }
    } catch (error) {
        container.innerHTML = '<p class="text-red-600">Error detectando impresoras: ' + error.message + '</p>';
    }
}

function displayDetectedPrinters(printers) {
    const container = document.getElementById('detected-printers');
    
    if (printers.length === 0) {
        container.innerHTML = '<p class="text-gray-600">No se detectaron impresoras</p>';
        return;
    }
    
    let html = '<h4 class="font-semibold mb-2">Impresoras Detectadas:</h4><div class="space-y-2">';
    printers.forEach(printer => {
        html += `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="font-medium">${printer.name}</span>
                <button onclick="addDetectedPrinter('${printer.name}')" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    Agregar
                </button>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

function addDetectedPrinter(printerName) {
    document.querySelector('input[name="name"]').value = printerName;
    document.querySelector('input[name="system_name"]').value = printerName;
}

async function testPrinter(printerId) {
    try {
        const response = await fetch('api/test-printer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({printer_id: printerId})
        });
        
        const result = await response.json();
        alert(result.success ? 'Test de impresión enviado' : 'Error: ' + result.error);
    } catch (error) {
        alert('Error de conexión');
    }
}

function deletePrinter(printerId) {
    if (confirm('¿Eliminar esta impresora?')) {
        window.location.href = `api/delete-printer.php?id=${printerId}`;
    }
}
</script>

</body>
</html>