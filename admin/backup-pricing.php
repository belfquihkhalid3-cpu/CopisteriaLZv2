<?php
session_start();
require_once 'auth.php';
requireAdmin();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Exportar backup de precios actuales
$pricing_backup = fetchAll("SELECT * FROM pricing WHERE is_active = 1 ORDER BY paper_size, paper_weight, color_mode");
$finishing_backup = fetchAll("SELECT * FROM finishing_costs WHERE is_active = 1");

$filename = 'backup-precios-' . date('Y-m-d-H-i') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";

// Backup precios impresión
fputcsv(STDOUT, ['BACKUP PRECIOS IMPRESIÓN'], ';');
fputcsv(STDOUT, ['Generado', date('d/m/Y H:i')], ';');
fputcsv(STDOUT, ['Admin', $_SESSION['admin_name']], ';');
fputcsv(STDOUT, [], ';');

fputcsv(STDOUT, ['Papel', 'Peso', 'Color', 'Precio/Página', 'Válido Desde'], ';');

foreach ($pricing_backup as $price) {
    fputcsv(STDOUT, [
        $price['paper_size'],
        $price['paper_weight'],
        $price['color_mode'],
        number_format($price['price_per_page'], 4),
        $price['valid_from']
    ], ';');
}

fputcsv(STDOUT, [], ';');
fputcsv(STDOUT, ['BACKUP COSTOS ACABADO'], ';');
fputcsv(STDOUT, ['Servicio', 'Tipo', 'Costo', 'Tipo Costo'], ';');

foreach ($finishing_backup as $cost) {
    fputcsv(STDOUT, [
        $cost['service_name'],
        $cost['service_type'],
        number_format($cost['cost'], 2),
        $cost['cost_type']
    ], ';');
}

exit();
?>