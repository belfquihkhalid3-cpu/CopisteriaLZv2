<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$order_id = $_GET['id'] ?? 0;

$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$order_id]);

$order_items = fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$order_id]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Trabajo - <?= $order['order_number'] ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .order-title {
            font-size: 18px;
            font-weight: bold;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .pickup-code-section {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        
        .pickup-code {
            font-size: 32px;
            font-weight: bold;
            font-family: monospace;
            letter-spacing: 4px;
            color: #1d4ed8;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        .info-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .work-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .work-table th,
        .work-table td {
            border: 2px solid #374151;
            padding: 12px 8px;
            text-align: left;
        }
        
        .work-table th {
            background-color: #374151;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .config-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .badge {
            background: #3b82f6;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .checklist {
            margin: 30px 0;
            background: #f0fdf4;
            border: 2px solid #22c55e;
            padding: 20px;
            border-radius: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #374151;
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <!-- Print Button -->
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> Imprimir Hoja de Trabajo
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="logo">COPISTERIA</div>
        <div style="text-align: right;">
            <div style="font-size: 16px; font-weight: bold;">HOJA DE TRABAJO</div>
            <div style="color: #666;">Fecha: <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- Order Title -->
    <div class="order-title">
        PEDIDO: <?= htmlspecialchars($order['order_number']) ?>
    </div>

    <!-- Pickup Code -->
    <div class="pickup-code-section">
        <div style="font-size: 14px; margin-bottom: 10px; color: #1d4ed8;">CÓDIGO DE RECOGIDA</div>
        <div class="pickup-code"><?= htmlspecialchars($order['pickup_code']) ?></div>
    </div>

    <!-- Customer and Order Info -->
    <div class="info-grid">
        <div class="info-section">
            <div class="info-title">INFORMACIÓN DEL CLIENTE</div>
            <div><strong>Nombre:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></div>
            <?php if ($order['phone']): ?>
            <div><strong>Teléfono:</strong> <?= htmlspecialchars($order['phone']) ?></div>
            <?php endif; ?>
        </div>
        
        <div class="info-section">
            <div class="info-title">RESUMEN DEL PEDIDO</div>
            <div><strong>Archivos:</strong> <?= $order['total_files'] ?></div>
            <div><strong>Páginas totales:</strong> <?= $order['total_pages'] ?></div>
            <div><strong>Total:</strong> €<?= number_format($order['total_price'], 2) ?></div>
            <div><strong>Pago:</strong> <?= $order['payment_method'] === 'ON_SITE' ? 'En tienda' : $order['payment_method'] ?></div>
        </div>
    </div>

    <!-- Files Work Table -->
    <table class="work-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 30%">ARCHIVO</th>
                <th style="width: 25%">CONFIGURACIÓN</th>
                <th style="width: 8%">PÁGINAS</th>
                <th style="width: 8%">COPIAS</th>
                <th style="width: 10%">SUBTOTAL</th>
                <th style="width: 14%">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items as $index => $item): ?>
            <tr>
                <td style="text-align: center; font-weight: bold;"><?= $index + 1 ?></td>
                <td>
                    <strong><?= htmlspecialchars($item['file_original_name']) ?></strong><br>
                    <small style="color: #666;"><?= number_format($item['file_size'] / 1024, 1) ?> KB</small>
                </td>
                <td>
                    <div class="config-badges">
                        <span class="badge"><?= $item['paper_size'] ?></span>
                        <span class="badge"><?= $item['paper_weight'] ?></span>
                        <span class="badge"><?= $item['color_mode'] ?></span>
                        <span class="badge"><?= $item['sides'] ?></span>
                        <?php if ($item['binding'] && $item['binding'] !== 'NONE'): ?>
                        <span class="badge" style="background: #ef4444;"><?= $item['binding'] ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="text-align: center; font-weight: bold;"><?= $item['page_count'] ?></td>
                <td style="text-align: center; font-weight: bold;"><?= $item['copies'] ?></td>
                <td style="text-align: center; font-weight: bold;">€<?= number_format($item['item_total'], 2) ?></td>
                <td>
                    <div class="checkbox">☐</div> Impreso<br>
                    <div class="checkbox">☐</div> Revisado<br>
                    <div class="checkbox">☐</div> Listo
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Comments -->
    <?php if ($order['customer_notes']): ?>
    <div style="background: #fef3c7; border: 2px solid #f59e0b; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <div style="font-weight: bold; margin-bottom: 8px;">COMENTARIOS DEL CLIENTE:</div>
        <div><?= nl2br(htmlspecialchars($order['customer_notes'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Work Checklist -->
    <div class="checklist">
        <div style="font-size: 16px; font-weight: bold; color: #16a34a; margin-bottom: 15px;">
            ✓ LISTA DE VERIFICACIÓN
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Todos los archivos descargados y verificados</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Configuración de impresión revisada</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Papel y tinta preparados</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Impresión completada según especificaciones</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Acabados aplicados (encuadernado, grapado, etc.)</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Control de calidad realizado</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Pedido empaquetado y etiquetado</span>
        </div>
        
        <div class="checkbox-item">
            <div class="checkbox"></div>
            <span>Cliente notificado (pedido listo)</span>
        </div>
    </div>

    <!-- Footer -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center; color: #666;">
        <div><strong>COPISTERIA</strong> - Servicios de Impresión Digital</div>
        <div>Tel: +34 900 123 456 | Email: info@copisteria.com</div>
        <div style="margin-top: 10px;">Hoja generada el <?= date('d/m/Y H:i') ?> por <?= htmlspecialchars($_SESSION['admin_name']) ?></div>
    </div>

</body>
</html>