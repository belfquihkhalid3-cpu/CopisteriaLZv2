<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupérer filtres depuis l'URL
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';

// Construction requête (même logique que orders.php)
$where_conditions = [];
$params = [];

if ($status_filter && $status_filter !== 'ALL') {
    $where_conditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = '(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($date_from) {
    $where_conditions[] = 'DATE(o.created_at) >= ?';
    $params[] = $date_from;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Récupérer toutes les données pour export
$export_sql = "SELECT 
    o.order_number,
    o.status,
    CONCAT(u.first_name, ' ', u.last_name) as cliente,
    u.email,
    u.phone,
    o.total_files,
    o.total_pages,
    o.total_price,
    o.payment_method,
    o.payment_status,
    o.pickup_code,
    o.created_at,
    o.completed_at,
    o.customer_notes
FROM orders o 
JOIN users u ON o.user_id = u.id 
$where_clause 
ORDER BY o.created_at DESC";

$orders = fetchAll($export_sql, $params);

// Générer fichier CSV
$filename = 'pedidos-copisteria-' . date('Y-m-d-H-i') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM UTF-8 pour Excel
echo "\xEF\xBB\xBF";

// Headers CSV
$csv_headers = [
    'Número Pedido',
    'Estado', 
    'Cliente',
    'Email',
    'Teléfono',
    'Archivos',
    'Páginas',
    'Total €',
    'Método Pago',
    'Estado Pago',
    'Código Recogida',
    'Fecha Pedido',
    'Fecha Completado',
    'Comentarios'
];

// Escribir headers
fputcsv(STDOUT, $csv_headers, ';');

// Escribir datos
foreach ($orders as $order) {
    $row = [
        $order['order_number'],
        $order['status'],
        $order['cliente'],
        $order['email'],
        $order['phone'] ?? '',
        $order['total_files'],
        $order['total_pages'],
        number_format($order['total_price'], 2),
        $order['payment_method'],
        $order['payment_status'],
        $order['pickup_code'],
        date('d/m/Y H:i', strtotime($order['created_at'])),
        $order['completed_at'] ? date('d/m/Y H:i', strtotime($order['completed_at'])) : '',
        $order['customer_notes'] ?? ''
    ];
    
    fputcsv(STDOUT, $row, ';');
}

exit();
?>