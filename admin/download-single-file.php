<?php
session_start();
require_once 'auth.php';
requireAdmin();

$file_name = $_GET['file'] ?? '';
$original_name = $_GET['name'] ?? 'documento.pdf';

if (!$file_name) {
    header('Location: orders.php');
    exit();
}

$file_path = '../uploads/documents/' . $file_name;

if (!file_exists($file_path)) {
    header('Location: orders.php?error=file_not_found');
    exit();
}

// Headers pour téléchargement
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $original_name . '"');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit();
?>