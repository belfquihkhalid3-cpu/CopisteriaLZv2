<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$order_id = $_GET['order_id'] ?? 0;

// Récupérer les détails de la commande
$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$order_id]);

if (!$order) {
    die('Commande non trouvée');
}

// Récupérer les fichiers de la commande
$order_items = fetchAll("
    SELECT * FROM order_items 
    WHERE order_id = ? 
    ORDER BY id
", [$order_id]);

$print_config = json_decode($order['print_config'], true) ?: [];
$folders = $print_config['folders'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - Pedido #<?= htmlspecialchars($order['order_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <div class="max-w-7xl mx-auto p-6">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Vista Previa - Pedido #<?= htmlspecialchars($order['order_number']) ?>
                    </h1>
                    <p class="text-gray-600">
                        Cliente: <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                    </p>
                </div>
                <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-times mr-2"></i>Cerrar
                </button>
            </div>
        </div>

        <!-- Lista de Archivos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Sidebar con lista de archivos -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Archivos del Pedido</h3>
                    <div class="space-y-3" id="file-list">
                        <?php foreach ($folders as $folderIndex => $folder): ?>
                            <?php if (!empty($folder['files'])): ?>
                                <div class="border-b pb-3 mb-3">
                                    <h4 class="font-medium text-gray-800 mb-2">
                                        <?= htmlspecialchars($folder['name'] ?? "Carpeta " . ($folderIndex + 1)) ?>
                                    </h4>
                                    <?php foreach ($folder['files'] as $fileIndex => $file): ?>
                                      <div class="border border-gray-200 rounded-lg mb-2 p-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <i class="fas fa-file-pdf text-red-500"></i>
            <div>
                <div class="font-medium text-sm"><?= htmlspecialchars($file['name']) ?></div>
                <div class="text-xs text-gray-500"><?= $file['pages'] ?? 1 ?> páginas</div>
            </div>
        </div>
        <button onclick="previewFile('<?= htmlspecialchars($file['stored_name'] ?? $file['name']) ?>')" 
                class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-eye mr-1"></i>Vista Previa
        </button>
    </div>
</div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Visor de documentos -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div id="document-viewer" class="text-center">
                        <div class="py-20">
                            <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">Selecciona un archivo para visualizar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function viewFile(fileName, originalName) {
            // Marquer le fichier comme actif
            document.querySelectorAll('.file-item').forEach(item => {
                item.classList.remove('bg-blue-100', 'border-blue-300');
                item.classList.add('border-gray-200');
            });
            
            event.currentTarget.classList.add('bg-blue-100', 'border-blue-300');
            event.currentTarget.classList.remove('border-gray-200');
            
            // Afficher le document
            const viewer = document.getElementById('document-viewer');
            
            // Construire le chemin du fichier
            const filePath = `../uploads/${fileName}`;
            
            viewer.innerHTML = `
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">${originalName}</h3>
                    <div class="flex justify-center space-x-4 mt-2">
                        <button onclick="downloadFile('${filePath}')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-download mr-2"></i>Descargar
                        </button>
                        <button onclick="openInNewTab('${filePath}')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-external-link-alt mr-2"></i>Abrir en nueva pestaña
                        </button>
                    </div>
                </div>
                <div class="border border-gray-300 rounded-lg overflow-hidden" style="height: 600px;">
                    <iframe src="${filePath}" 
                            width="100%" 
                            height="100%" 
                            style="border: none;">
                        <p>Su navegador no soporta la visualización de este archivo. 
                           <a href="${filePath}" target="_blank">Haga clic aquí para descargar</a>
                        </p>
                    </iframe>
                </div>
            `;
        }
        
        function downloadFile(filePath) {
            const link = document.createElement('a');
            link.href = filePath;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        function previewFile(fileName) {
    window.open(`serve-file.php?file=${fileName}`, '_blank');
}
        function openInNewTab(filePath) {
            window.open(filePath, '_blank');
        }
    </script>

</body>
</html>