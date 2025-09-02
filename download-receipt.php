<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/user_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();

// Récupérer l'ID de la commande
$order_id = $_GET['order'] ?? 0;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Récupérer les détails de la commande
$order = fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $_SESSION['user_id']]);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Vérifier que la commande est complétée
if ($order['status'] !== 'COMPLETED') {
    header('Location: order-status.php?id=' . $order_id);
    exit();
}

// Récupérer les items de la commande
$order_items = fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$order_id]);

// Headers pour téléchargement PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="recibo-' . $order['order_number'] . '.pdf"');

// Générer le contenu HTML pour conversion PDF (ou utiliser une librairie PDF)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo - <?= $order['order_number'] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 5px;
        }
        
        .company-info {
            color: #666;
            font-size: 10px;
        }
        
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .order-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .order-info-left,
        .order-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .order-info-right {
            text-align: right;
        }
        
        .info-block {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .pickup-code {
            background-color: #e3f2fd;
            padding: 10px;
            border: 2px solid #2196f3;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 15px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .items-table td.number {
            text-align: right;
        }
        
        .total-section {
            border-top: 2px solid #333;
            margin-top: 20px;
            padding-top: 15px;
        }
        
        .total-line {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .total-label {
            display: table-cell;
            text-align: right;
            padding-right: 20px;
        }
        
        .total-amount {
            display: table-cell;
            text-align: right;
            width: 100px;
        }
        
        .final-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .thank-you {
            text-align: center;
            margin: 30px 0;
            font-size: 14px;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="company-name">COPISTERIA</div>
        <div class="company-info">
            Servicios de Impresión Digital<br>
            Tel: +34 900 123 456 | Email: info@copisteria.com<br>
            www.copisteria.com
        </div>
    </div>

    <!-- Receipt Title -->
    <div class="receipt-title">
        RECIBO DE COMPRA
    </div>

    <!-- Order Information -->
    <div class="order-info">
        <div class="order-info-left">
            <div class="info-block">
                <div class="info-label">Cliente:</div>
                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?><br>
                <?= htmlspecialchars($user['email']) ?>
                <?php if ($user['phone']): ?>
                    <br>Tel: <?= htmlspecialchars($user['phone']) ?>
                <?php endif; ?>
            </div>
            
            <div class="info-block">
                <div class="info-label">Número de Pedido:</div>
                <?= htmlspecialchars($order['order_number']) ?>
            </div>
        </div>
        
        <div class="order-info-right">
            <div class="info-block">
                <div class="info-label">Fecha de Pedido:</div>
                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
            </div>
            
            <div class="info-block">
                <div class="info-label">Fecha de Entrega:</div>
                <?= $order['completed_at'] ? date('d/m/Y H:i', strtotime($order['completed_at'])) : 'N/A' ?>
            </div>
            
            <div class="info-block">
                <div class="info-label">Estado:</div>
                <strong>COMPLETADO</strong>
            </div>
        </div>
    </div>

    <!-- Pickup Code -->
    <?php if ($order['pickup_code']): ?>
    <div class="pickup-code">
        CÓDIGO DE RECOGIDA: <?= htmlspecialchars($order['pickup_code']) ?>
    </div>
    <?php endif; ?>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%">Archivo</th>
                <th style="width: 15%">Páginas</th>
                <th style="width: 10%">Copias</th>
                <th style="width: 15%">Configuración</th>
                <th style="width: 10%">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items as $item): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($item['file_original_name']) ?></strong><br>
                    <small><?= number_format($item['file_size'] / 1024, 1) ?> KB</small>
                </td>
                <td class="number"><?= $item['page_count'] ?></td>
                <td class="number"><?= $item['copies'] ?></td>
                <td>
                    <small>
                        <?= htmlspecialchars($item['paper_size']) ?> - <?= htmlspecialchars($item['paper_weight']) ?><br>
                        <?= $item['color_mode'] === 'BW' ? 'B/N' : 'Color' ?> - 
                        <?= $item['sides'] === 'SINGLE' ? 'Una cara' : 'Doble cara' ?>
                        <?php if ($item['binding'] && $item['binding'] !== 'NONE'): ?>
                            <br><?= htmlspecialchars($item['binding']) ?>
                        <?php endif; ?>
                    </small>
                </td>
                <td class="number">€<?= number_format($item['item_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="total-section">
        <div class="total-line">
            <div class="total-label">Subtotal:</div>
            <div class="total-amount">€<?= number_format($order['total_price'], 2) ?></div>
        </div>
        
        <div class="total-line">
            <div class="total-label">IVA (21%):</div>
            <div class="total-amount">€<?= number_format($order['total_price'] * 0.21, 2) ?></div>
        </div>
        
        <div class="total-line final-total">
            <div class="total-label">TOTAL:</div>
            <div class="total-amount">€<?= number_format($order['total_price'] * 1.21, 2) ?></div>
        </div>
    </div>

    <!-- Payment Info -->
    <div style="margin-top: 20px;">
        <div class="info-block">
            <div class="info-label">Método de Pago:</div>
            <?= $order['payment_method'] === 'ON_SITE' ? 'Pago en Tienda' : ucfirst(str_replace('_', ' ', strtolower($order['payment_method']))) ?>
        </div>
        
        <div class="info-block">
            <div class="info-label">Estado del Pago:</div>
            <strong>PAGADO</strong>
        </div>
    </div>

    <!-- Customer Notes -->
    <?php if ($order['customer_notes']): ?>
    <div class="info-block" style="margin-top: 20px;">
        <div class="info-label">Comentarios del Cliente:</div>
        <?= nl2br(htmlspecialchars($order['customer_notes'])) ?>
    </div>
    <?php endif; ?>

    <!-- Thank You -->
    <div class="thank-you">
        ¡GRACIAS POR CONFIAR EN COPISTERIA!
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Este recibo es válido como comprobante de compra</div>
        <div>Para cualquier consulta, contacte con nosotros en info@copisteria.com o +34 900 123 456</div>
        <div style="margin-top: 10px;">
            Documento generado el <?= date('d/m/Y H:i') ?>
        </div>
    </div>

</body>
</html>

<?php
// Note: Pour une vraie génération PDF, vous pouvez utiliser:
// - TCPDF: composer require tecnickcom/tcpdf
// - FPDF: composer require setasign/fpdf
// - mPDF: composer require mpdf/mpdf
// - DomPDF: composer require dompdf/dompdf

// Exemple avec DomPDF (si installé):
/*
require_once 'vendor/autoload.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml(ob_get_contents());
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('recibo-' . $order['order_number'] . '.pdf');
*/
?>