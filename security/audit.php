<?php
echo "<h1>Audit de Sécurité</h1>";

// Vérifier permissions
$checks = [
    'config/database.php readable' => is_readable('../config/database.php'),
    'uploads/ protégé' => file_exists('../uploads/.htaccess'),
    'admin/ protégé' => file_exists('../admin/.htaccess'),
    'logs/ existe' => is_dir('logs/'),
    'PHP version OK' => version_compare(PHP_VERSION, '7.4.0', '>=')
];

foreach ($checks as $check => $result) {
    echo ($result ? "✅" : "❌") . " $check<br>";
}

// Vérifier variables sensibles
$sensitive_vars = ['DB_PASS', 'SECRET_KEY'];
foreach ($sensitive_vars as $var) {
    echo (isset($_ENV[$var]) ? "✅" : "❌") . " Variable $var configurée<br>";
}
?>