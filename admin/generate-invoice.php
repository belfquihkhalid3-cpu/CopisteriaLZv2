<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$order_id = $_GET['order_id'] ?? 0;

// Récupérer les détails de la commande
$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$order_id]);

if (!$order) {
    die('Commande non trouvée');
}

// Récupérer les articles de la commande
$order_items = fetchAll("
    SELECT * FROM order_items 
    WHERE order_id = ? 
    ORDER BY id
", [$order_id]);

// Calculer les totaux
$subtotal = $order['total_price'];
$iva = $subtotal * 0.21;
$total = $subtotal + $iva;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura - <?= htmlspecialchars($order['order_number']) ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .invoice-container { box-shadow: none !important; }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #EF7834, #d96a3f);
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: contain;
            background: white;
            padding: 5px;
        }
        
        .company-details h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        
        .company-details p {
            margin: 2px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .invoice-info {
            text-align: right;
        }
        
        .invoice-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .invoice-content {
            padding: 30px;
        }
        
        .billing-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .billing-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #EF7834;
        }
        
        .billing-box h3 {
            margin: 0 0 15px 0;
            color: #EF7834;
            font-size: 16px;
            font-weight: bold;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .items-table th {
            background: #EF7834;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .totals-section {
            margin-top: 30px;
            text-align: right;
        }
        
        .totals-table {
            margin-left: auto;
            min-width: 300px;
        }
        
        .totals-table td {
            padding: 8px 15px;
            border: none;
        }
        
        .total-row {
            background: #EF7834;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #eee;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #EF7834;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(239, 120, 52, 0.3);
        }
        
        .print-button:hover {
            background: #d96a3f;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-button no-print">
        <i class="fas fa-print"></i> Imprimir Factura
    </button>

    <div class="invoice-container">
        
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="logo-section">
                    <img src="../assets/img/1.jpeg" alt="Logo" class="logo">
                    <div class="company-details">
                        <h1>Tinta Expres LZ</h1>
                        <p>Servicios de Impresión Profesional</p>
                        <p>Carrer de les Tres Creus, 142
08202 Sabadell, Barcelona, Spain</p>
                        <p>Tel: +34 932 52 05 70 | Email: info@tintaexpreslz.com</p>
                        <p>CIF: Y1082366T</p>
                    </div>
                </div>
                <div class="invoice-info">
                    <div class="invoice-number">FACTURA #<?= htmlspecialchars($order['order_number']) ?></div>
                    <p>Fecha: <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
                    <p>Vencimiento: <?= date('d/m/Y', strtotime($order['created_at'] . ' +30 days')) ?></p>
                    <span class="status-badge">PAGADO</span>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="invoice-content">
            
            <!-- Billing Information -->
            <div class="billing-section">
                <div class="billing-box">
                    <h3>FACTURAR A:</h3>
                    <strong><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong><br>
                    <?php if ($order['address']): ?>
                        <?= htmlspecialchars($order['address']) ?><br>
                    <?php endif; ?>
                    Email: <?= htmlspecialchars($order['email']) ?><br>
                    <?php if ($order['phone']): ?>
                        Teléfono: <?= htmlspecialchars($order['phone']) ?><br>
                    <?php endif; ?>
                </div>
                
                <div class="billing-box">
                    <h3>DETALLES DEL PEDIDO:</h3>
                    <strong>Número de Pedido:</strong> <?= htmlspecialchars($order['order_number']) ?><br>
                    <strong>Código de Recogida:</strong> <?= htmlspecialchars($order['pickup_code']) ?><br>
                    <strong>Método de Pago:</strong> 
                    <?= $order['payment_method'] === 'BANK_TRANSFER' ? 'Transferencia Bancaria' : 'Pago en Tienda' ?><br>
                    <strong>Estado:</strong> <?= htmlspecialchars($order['status']) ?><br>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Configuración</th>
                        <th>Páginas</th>
                        <th>Copias</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['file_original_name']) ?></strong>
                        </td>
                        <td>
                            <?= htmlspecialchars($item['paper_size']) ?> - 
                            <?= htmlspecialchars($item['paper_weight']) ?> - 
                            <?= $item['color_mode'] === 'BW' ? 'B/N' : 'Color' ?> - 
                            <?= $item['sides'] === 'SINGLE' ? 'Una cara' : 'Doble cara' ?>
                        </td>
                        <td><?= $item['page_count'] ?></td>
                        <td><?= $item['copies'] ?></td>
                        <td>€<?= number_format($item['unit_price'], 3) ?></td>
                        <td>€<?= number_format($item['item_total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td><strong>€<?= number_format($subtotal, 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>IVA (21%):</strong></td>
                        <td><strong>€<?= number_format($iva, 2) ?></strong></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL:</strong></td>
                        <td><strong>€<?= number_format($total, 2) ?></strong></td>
                    </tr>
                </table>
            </div>

            <!-- Notes -->
            <?php if ($order['customer_notes']): ?>
            <div style="margin-top: 30px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <h4 style="margin: 0 0 10px 0; color: #EF7834;">Notas del Cliente:</h4>
                <p style="margin: 0;"><?= nl2br(htmlspecialchars($order['customer_notes'])) ?></p>
            </div>
            <?php endif; ?>

        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>¡Gracias por confiar en Tinta Expres LZ!</strong></p>
            <p>Esta factura fue generada electrónicamente el <?= date('d/m/Y H:i') ?></p>
            <p>Para cualquier consulta, contacte con nosotros en info@tintaexpreslz.com o +34 932 52 05 70</p>
        </div>

    </div>

</body>
</html>