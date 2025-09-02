<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

$type = $_GET['type'] ?? 'basic';
$period = $_GET['period'] ?? 'month';

// Calculer dates
switch ($period) {
    case 'today':
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        $date_to = date('Y-m-d');
        break;
    case 'month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-d');
        break;
    case 'year':
        $date_from = date('Y-01-01');
        $date_to = date('Y-m-d');
        break;
    case 'custom':
        $date_from = $_GET['from'] ?: date('Y-m-01');
        $date_to = $_GET['to'] ?: date('Y-m-d');
        break;
}

$filename = "reporte-{$type}-{$period}-" . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF"; // BOM UTF-8

switch ($type) {
    case 'detailed':
        exportDetailedReport($date_from, $date_to);
        break;
    case 'summary':
        exportSummaryReport($date_from, $date_to);
        break;
    case 'financial':
        exportFinancialReport($date_from, $date_to);
        break;
    default:
        exportBasicReport($date_from, $date_to);
}

function exportDetailedReport($date_from, $date_to) {
    // Headers
    fputcsv(STDOUT, [
        'Pedido', 'Cliente', 'Email', 'Teléfono', 'Estado', 'Fecha Pedido', 'Fecha Completado',
        'Archivo', 'Páginas', 'Copias', 'Papel', 'Peso', 'Color', 'Caras', 'Acabado', 
        'Precio Unitario', 'Subtotal', 'Total Pedido', 'Método Pago', 'Código Recogida'
    ], ';');
    
    // Datos con JOIN de order_items
    $sql = "SELECT 
        o.order_number, CONCAT(u.first_name, ' ', u.last_name) as cliente, u.email, u.phone,
        o.status, o.created_at, o.completed_at, o.total_price, o.payment_method, o.pickup_code,
        oi.file_original_name, oi.page_count, oi.copies, oi.paper_size, oi.paper_weight,
        oi.color_mode, oi.sides, oi.binding, oi.unit_price, oi.item_total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC";
    
    $results = fetchAll($sql, [$date_from, $date_to]);
    
    foreach ($results as $row) {
        fputcsv(STDOUT, [
            $row['order_number'],
            $row['cliente'],
            $row['email'],
            $row['phone'] ?? '',
            $row['status'],
            date('d/m/Y H:i', strtotime($row['created_at'])),
            $row['completed_at'] ? date('d/m/Y H:i', strtotime($row['completed_at'])) : '',
            $row['file_original_name'] ?? '',
            $row['page_count'] ?? '',
            $row['copies'] ?? '',
            $row['paper_size'] ?? '',
            $row['paper_weight'] ?? '',
            $row['color_mode'] ?? '',
            $row['sides'] ?? '',
            $row['binding'] ?? '',
            $row['unit_price'] ? number_format($row['unit_price'], 4) : '',
            $row['item_total'] ? number_format($row['item_total'], 2) : '',
            number_format($row['total_price'], 2),
            $row['payment_method'],
            $row['pickup_code']
        ], ';');
    }
}

function exportSummaryReport($date_from, $date_to) {
    // Headers résumé exécutif
    fputcsv(STDOUT, ['REPORTE EJECUTIVO COPISTERIA'], ';');
    fputcsv(STDOUT, ['Período:', date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to))], ';');
    fputcsv(STDOUT, ['Generado:', date('d/m/Y H:i')], ';');
    fputcsv(STDOUT, [], ';'); // Línea vacía
    
    // KPIs principales
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'COMPLETED' THEN 1 END) as completed_orders,
            COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(total_pages), 0) as total_pages,
            COALESCE(AVG(CASE WHEN status = 'COMPLETED' THEN total_price END), 0) as avg_order
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ", [$date_from, $date_to]);
    
    fputcsv(STDOUT, ['MÉTRICAS PRINCIPALES'], ';');
    fputcsv(STDOUT, ['Total Pedidos', $stats['total_orders']], ';');
    fputcsv(STDOUT, ['Pedidos Completados', $stats['completed_orders']], ';');
fputcsv(STDOUT, ['Tasa de Conversión', $stats['total_orders'] > 0 ? round(($stats['completed_orders'] / $stats['total_orders']) * 100, 2) . '%' : '0%'], ';');
   fputcsv(STDOUT, ['Ingresos Totales', '€' . number_format($stats['total_revenue'], 2)], ';');
   fputcsv(STDOUT, ['Páginas Impresas', number_format($stats['total_pages'])], ';');
   fputcsv(STDOUT, ['Ticket Promedio', '€' . number_format($stats['avg_order'], 2)], ';');
   fputcsv(STDOUT, [], ';');
   
   // Top clientes
   fputcsv(STDOUT, ['TOP 10 CLIENTES'], ';');
   fputcsv(STDOUT, ['Cliente', 'Email', 'Pedidos', 'Total Gastado'], ';');
   
   $top_clients = fetchAll("
       SELECT CONCAT(u.first_name, ' ', u.last_name) as cliente, u.email,
              COUNT(o.id) as total_orders,
              COALESCE(SUM(CASE WHEN o.status = 'COMPLETED' THEN o.total_price ELSE 0 END), 0) as total_spent
       FROM users u
       JOIN orders o ON u.id = o.user_id
       WHERE DATE(o.created_at) BETWEEN ? AND ?
       GROUP BY u.id ORDER BY total_spent DESC LIMIT 10
   ", [$date_from, $date_to]);
   
   foreach ($top_clients as $client) {
       fputcsv(STDOUT, [
           $client['cliente'],
           $client['email'],
           $client['total_orders'],
           '€' . number_format($client['total_spent'], 2)
       ], ';');
   }
}

function exportFinancialReport($date_from, $date_to) {
   fputcsv(STDOUT, ['REPORTE FINANCIERO COPISTERIA'], ';');
   fputcsv(STDOUT, ['Período', date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to))], ';');
   fputcsv(STDOUT, [], ';');
   
   // Ingresos por día
   fputcsv(STDOUT, ['INGRESOS DIARIOS'], ';');
   fputcsv(STDOUT, ['Fecha', 'Pedidos', 'Ingresos Brutos', 'IVA (21%)', 'Ingresos Netos'], ';');
   
   $daily_revenue = fetchAll("
       SELECT DATE(created_at) as date,
              COUNT(*) as orders_count,
              COALESCE(SUM(CASE WHEN status = 'COMPLETED' THEN total_price ELSE 0 END), 0) as revenue
       FROM orders 
       WHERE DATE(created_at) BETWEEN ? AND ?
       GROUP BY DATE(created_at) ORDER BY date ASC
   ", [$date_from, $date_to]);
   
   $total_gross = 0;
   foreach ($daily_revenue as $day) {
       $gross = $day['revenue'];
       $vat = $gross * 0.21;
       $net = $gross - $vat;
       $total_gross += $gross;
       
       fputcsv(STDOUT, [
           date('d/m/Y', strtotime($day['date'])),
           $day['orders_count'],
           '€' . number_format($gross, 2),
           '€' . number_format($vat, 2),
           '€' . number_format($net, 2)
       ], ';');
   }
   
   fputcsv(STDOUT, [], ';');
   fputcsv(STDOUT, ['RESUMEN FINANCIERO'], ';');
   fputcsv(STDOUT, ['Total Ingresos Brutos', '€' . number_format($total_gross, 2)], ';');
   fputcsv(STDOUT, ['IVA Total', '€' . number_format($total_gross * 0.21, 2)], ';');
   fputcsv(STDOUT, ['Ingresos Netos', '€' . number_format($total_gross * 0.79, 2)], ';');
}

function exportBasicReport($date_from, $date_to) {
   // Reporte básico como el anterior
   fputcsv(STDOUT, [
       'Número Pedido', 'Estado', 'Cliente', 'Email', 'Total', 'Fecha'
   ], ';');
   
   $orders = fetchAll("
       SELECT o.order_number, o.status, CONCAT(u.first_name, ' ', u.last_name) as cliente,
              u.email, o.total_price, o.created_at
       FROM orders o 
       JOIN users u ON o.user_id = u.id 
       WHERE DATE(o.created_at) BETWEEN ? AND ?
       ORDER BY o.created_at DESC
   ", [$date_from, $date_to]);
   
   foreach ($orders as $order) {
       fputcsv(STDOUT, [
           $order['order_number'],
           $order['status'],
           $order['cliente'],
           $order['email'],
           '€' . number_format($order['total_price'], 2),
           date('d/m/Y H:i', strtotime($order['created_at']))
       ], ';');
   }
}

exit();
?>