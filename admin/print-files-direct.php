<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$order_id = $_GET['order_id'] ?? 0;
$printer_id = $_GET['printer_id'] ?? 0;

// Récupérer commande, imprimante et configuration
$order = fetchOne("SELECT * FROM orders WHERE id = ?", [$order_id]);
$printer = fetchOne("SELECT * FROM printers WHERE id = ?", [$printer_id]);
$print_config = json_decode($order['print_config'], true) ?: [];

if (!$order || !$printer) {
    die('Commande ou imprimante non trouvée');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Impresión Directa</title>
</head>
<body>

<div style="text-align: center; padding: 20px;">
    <h3>Enviando archivos a: <?= htmlspecialchars($printer['name']) ?></h3>
    <div id="progress">Preparando impresión...</div>
</div>

<script>
const orderConfig = <?= json_encode($print_config) ?>;
const printer = <?= json_encode($printer) ?>;
const orderId = <?= $order_id ?>;

async function startPrinting() {
    const progressDiv = document.getElementById('progress');
    
    try {
        <?php foreach ($print_config['folders'] ?? [] as $folderIndex => $folder): ?>
            <?php $config = $folder['configuration'] ?? []; ?>
            
            progressDiv.innerHTML += '<p>📁 Procesando: <?= htmlspecialchars($folder['name'] ?? "Carpeta " . ($folderIndex + 1)) ?></p>';
            
            // Configuración para esta carpeta
            const folderConfig = {
                copies: <?= $folder['copies'] ?? 1 ?>,
                paperSize: '<?= $config['paperSize'] ?? 'A4' ?>',
                colorMode: '<?= $config['colorMode'] ?? 'bw' ?>',
                sides: '<?= $config['sides'] ?? 'double' ?>',
                orientation: '<?= $config['orientation'] ?? 'portrait' ?>'
            };
            
            <?php foreach ($folder['files'] ?? [] as $fileIndex => $file): ?>
                await printFile('<?= $file['stored_name'] ?? $file['name'] ?>', '<?= $file['name'] ?>', folderConfig);
                progressDiv.innerHTML += '<p>✅ Impreso: <?= htmlspecialchars($file['name']) ?></p>';
            <?php endforeach; ?>
            
        <?php endforeach; ?>
        
        // Marcar como impreso
        await fetch('api/mark-printed.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order_id: orderId})
        });
        
        progressDiv.innerHTML += '<p style="color: green; font-weight: bold;">✅ Impresión completada</p>';
        setTimeout(() => window.close(), 3000);
        
    } catch (error) {
        progressDiv.innerHTML += '<p style="color: red;">❌ Error: ' + error.message + '</p>';
    }
}

async function printFile(storedName, originalName, config) {
    const response = await fetch('api/print-single-file.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            file: storedName,
            printer_id: printer.id,
            config: config,
            order_id: orderId
        })
    });
    
    const result = await response.json();
    if (!result.success) {
        throw new Error(result.error || 'Error imprimiendo archivo');
    }
}

// Iniciar impresión automáticamente
window.onload = startPrinting;
</script>

</body>
</html>