<?php
session_start();
require_once 'auth.php';
requireAdmin();

$fileName = $_GET['file'] ?? '';
$filePath = '../uploads/' . basename($fileName);

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Fichier non trouvé');
}

// Déterminer le type MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Headers pour l'affichage dans le navigateur
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
header('Content-Length: ' . filesize($filePath));

// Lire et afficher le fichier
readfile($filePath);
?>