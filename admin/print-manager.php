<?php
session_start();
require_once 'auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Impresoras - Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 min-h-screen p-6">
        
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-print mr-2 text-blue-500"></i>Gestión de Impresoras (Remoto)
            </h1>
        </div>

        <!-- Configuration IP du PC magasin -->
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
            <h3 class="font-semibold text-yellow-800 mb-2">⚠️ Configuración requerida</h3>
            <p class="text-yellow-700">Este sistema se conecta al PC del magasin para gestionar impresoras.</p>
        </div>

        <!-- Configuration IP -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-network-wired mr-2 text-blue-500"></i>Configurar PC del Magasin
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">IP del PC Magasin</label>
                    <input type="text" id="pc-ip" placeholder="192.168.1.15" value="localhost" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Puerto</label>
                    <input type="text" id="pc-port" value="5000" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="mt-4 flex space-x-3">
                <button onclick="testConnection()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-plug mr-2"></i>Probar Conexión
                </button>
                <button onclick="saveConfig()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                    <i class="fas fa-save mr-2"></i>Guardar Config
                </button>
            </div>
        </div>

        <!-- Status de connexion -->
        <div id="connection-status" class="bg-white rounded-lg shadow-sm p-6 mb-6 hidden">
            <h3 class="text-lg font-semibold mb-4">Estado de Conexión</h3>
            <div id="status-content"></div>
        </div>

        <!-- Detectar imprimantes -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-search mr-2 text-green-500"></i>Detectar Impresoras Remotas
            </h3>
            
            <button onclick="detectRemotePrinters()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 mb-4">
                <i class="fas fa-search mr-2"></i>Buscar Impresoras en PC Magasin
            </button>
            
            <div id="remote-printers" class="mt-4"></div>
        </div>

        <!-- Lista de imprimantes detectadas -->
        <div id="printers-list" class="bg-white rounded-lg shadow-sm p-6 hidden">
            <h3 class="text-lg font-semibold mb-4">Impresoras Detectadas</h3>
            <div id="printers-content"></div>
        </div>

    </div>

<script>
// Configuration
let PRINT_SERVICE_URL = 'http://192.168.1.138:5000';

// Charger configuration sauvegardée
document.addEventListener('DOMContentLoaded', function() {
    const savedIp = localStorage.getItem('pc-ip');
    const savedPort = localStorage.getItem('pc-port');
    
    if (savedIp) {
        document.getElementById('pc-ip').value = savedIp;
        updateServiceUrl();
    }
    if (savedPort) {
        document.getElementById('pc-port').value = savedPort;
        updateServiceUrl();
    }
});

function updateServiceUrl() {
    const ip = document.getElementById('pc-ip').value;
    const port = document.getElementById('pc-port').value;
    PRINT_SERVICE_URL = `http://${ip}:${port}`;
}

function saveConfig() {
    const ip = document.getElementById('pc-ip').value;
    const port = document.getElementById('pc-port').value;
    
    localStorage.setItem('pc-ip', ip);
    localStorage.setItem('pc-port', port);
    updateServiceUrl();
    
    showNotification('Configuración guardada', 'success');
}

async function testConnection() {
    updateServiceUrl();
    const statusDiv = document.getElementById('connection-status');
    const contentDiv = document.getElementById('status-content');
    
    statusDiv.classList.remove('hidden');
    contentDiv.innerHTML = '<p class="text-blue-600">🔄 Probando conexión...</p>';
    
    try {
        const response = await fetch(`${PRINT_SERVICE_URL}/detect-printers`);
        const result = await response.json();
        
        if (result.success) {
            contentDiv.innerHTML = `
                <div class="p-4 bg-green-100 text-green-800 rounded-lg">
                    <h4 class="font-semibold">✅ Conexión Exitosa</h4>
                    <p>PC conectado: ${PRINT_SERVICE_URL}</p>
                    <p>Impresoras encontradas: ${result.printers.length}</p>
                </div>
            `;
        } else {
            throw new Error('Service responded with error');
        }
    } catch (error) {
        contentDiv.innerHTML = `
            <div class="p-4 bg-red-100 text-red-800 rounded-lg">
                <h4 class="font-semibold">❌ Error de Conexión</h4>
                <p>No se puede conectar a: ${PRINT_SERVICE_URL}</p>
                <p>Verificar:</p>
                <ul class="list-disc list-inside mt-2">
                    <li>El servicio Python está ejecutándose</li>
                    <li>La IP es correcta</li>
                    <li>El firewall permite el puerto 5000</li>
                </ul>
            </div>
        `;
    }
}

async function detectRemotePrinters() {
    updateServiceUrl();
    const printersDiv = document.getElementById('remote-printers');
    
    printersDiv.innerHTML = '<p class="text-blue-600">🔄 Detectando impresoras remotas...</p>';
    
    try {
        const response = await fetch(`${PRINT_SERVICE_URL}/detect-printers`);
        const result = await response.json();
        
        if (result.success && result.printers.length > 0) {
            displayPrinters(result.printers);
        } else {
            printersDiv.innerHTML = '<p class="text-red-600">❌ No se encontraron impresoras</p>';
        }
    } catch (error) {
        printersDiv.innerHTML = `
            <div class="p-4 bg-red-100 text-red-800 rounded-lg">
                <p>❌ Error de conexión</p>
                <p class="text-sm">Asegúrate de que el servicio Python esté ejecutándose en el PC del magasin</p>
            </div>
        `;
    }
}

function displayPrinters(printers) {
    const listDiv = document.getElementById('printers-list');
    const contentDiv = document.getElementById('printers-content');
    
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    
    printers.forEach(printer => {
        const typeColor = printer.type === 'COLOR' ? 'purple' : 'gray';
        const statusColor = printer.status === 'Normal' ? 'green' : 'yellow';
        
        html += `
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-800">${printer.name}</h4>
                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-${typeColor}-100 text-${typeColor}-800">
                        ${printer.type}
                    </span>
                </div>
                
                <div class="text-sm text-gray-600 mb-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-${statusColor}-100 text-${statusColor}-800">
                        <i class="fas fa-circle mr-1" style="font-size: 6px;"></i>
                        ${printer.status}
                    </span>
                </div>

                <div class="flex space-x-2">
                    <button onclick="testPrinter('${printer.name}')" 
                            class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                        <i class="fas fa-vial mr-1"></i>Test
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    contentDiv.innerHTML = html;
    listDiv.classList.remove('hidden');
    
    // Sauvegarder les imprimantes pour utilisation dans orders-local.php
    localStorage.setItem('available-printers', JSON.stringify(printers));
}

async function testPrinter(printerName) {
    try {
        showNotification(`Enviando página de prueba a ${printerName}...`, 'info');
        
        // Ici tu peux ajouter un test d'impression
        const printData = {
            file_url: 'https://tintaexpreslz.com/admin/test-page.pdf', // Crée une page de test
            printer_name: printerName,
            copies: 1
        };
        
        const response = await fetch(`${PRINT_SERVICE_URL}/print-file`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(printData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`✅ Página de test enviada a ${printerName}`, 'success');
        } else {
            showNotification(`❌ Error: ${result.error}`, 'error');
        }
        
    } catch (error) {
        showNotification('❌ Error de conexión', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    } text-white max-w-sm`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>

</body>
</html>