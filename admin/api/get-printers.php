<?php
session_start();
require_once '../auth.php';
requireAdmin();

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    $printers = fetchAll("SELECT * FROM printers WHERE is_active = 1 ORDER BY name");
    echo json_encode($printers);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>