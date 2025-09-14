<?php
echo "Test fonctions:<br>";

if (function_exists('generateOrderToken')) {
    echo "✅ generateOrderToken existe<br>";
} else {
    echo "❌ generateOrderToken manquante<br>";
}

if (file_exists('../includes/functions.php')) {
    echo "✅ functions.php existe<br>";
    require_once '../includes/functions.php';
} else {
    echo "❌ functions.php manquant<br>";
}

if (file_exists('../../includes/functions.php')) {
    echo "✅ functions.php niveau 2 existe<br>";
} else {
    echo "❌ functions.php niveau 2 manquant<br>";
}
?>