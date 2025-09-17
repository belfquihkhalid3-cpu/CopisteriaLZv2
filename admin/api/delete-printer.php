<?php
session_start();
require_once '../auth.php';
requireAdmin();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$printer_id = $_GET['id'] ?? 0;

if ($printer_id) {
    executeQuery("DELETE FROM printers WHERE id = ?", [$printer_id]);
    header('Location: ../printer-config.php?deleted=1');
} else {
    header('Location: ../printer-config.php?error=1');
}
?>