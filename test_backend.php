<?php
/**
 * Test du backend Copisteria
 * Vérification de la connexion BDD et des fonctions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Backend Copisteria</h1>";

// Test de la connexion à la base de données
echo "<h2>1. Test de connexion BDD</h2>";
try {
    require_once 'config/database.php';
    echo "✅ Connexion à la base de données réussie<br>";
    
    $info = getDatabaseInfo();
    echo "📊 Informations BDD :<br>";
    echo "- Host: " . $info['host'] . "<br>";
    echo "- Database: " . $info['database'] . "<br>";
    echo "- User: " . $info['user'] . "<br>";
    echo "- Connecté: " . ($info['connected'] ? 'Oui' : 'Non') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur connexion BDD: " . $e->getMessage() . "<br>";
}

// Test des tables
echo "<h2>2. Vérification des tables</h2>";
$required_tables = ['users', 'orders', 'order_items', 'files', 'pricing', 'finishing_costs', 'notifications'];

foreach ($required_tables as $table) {
    try {
        $stmt = executeQuery("SELECT COUNT(*) as count FROM $table");
        if ($stmt) {
            $result = $stmt->fetch();
            echo "✅ Table '$table': " . $result['count'] . " enregistrements<br>";
        } else {
            echo "❌ Erreur avec la table '$table'<br>";
        }
    } catch (Exception $e) {
        echo "❌ Table '$table' manquante ou erreur: " . $e->getMessage() . "<br>";
    }
}

// Test des fonctions
echo "<h2>3. Test des fonctions</h2>";
require_once 'includes/user_functions.php';

// Test validation email
echo "<h3>Test validation email:</h3>";
$test_emails = ['test@email.com', 'invalid-email', 'user@copisteria.com'];
foreach ($test_emails as $email) {
    $valid = filter_var($email, FILTER_VALIDATE_EMAIL);
    echo "- $email: " . ($valid ? "✅ Valide" : "❌ Invalide") . "<br>";
}

// Test de création d'utilisateur (simulation)
echo "<h3>Test données d'inscription:</h3>";
$test_data = [
    'full_name' => 'Juan Pérez',
    'email' => 'juan@test.com',
    'password' => 'password123'
];

$errors = validateRegistrationData($test_data);
if (empty($errors)) {
    echo "✅ Données valides pour: " . $test_data['full_name'] . "<br>";
} else {
    echo "❌ Erreurs trouvées: " . implode(', ', $errors) . "<br>";
}

// Test tarification
echo "<h2>4. Test des tarifs</h2>";
try {
    $pricing_test = fetchAll("SELECT paper_size, paper_weight, color_mode, price_per_page 
                             FROM pricing 
                             WHERE is_active = 1 
                             ORDER BY paper_size, paper_weight, color_mode 
                             LIMIT 5");
    
    if (!empty($pricing_test)) {
        echo "✅ Tarifs disponibles:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Format</th><th>Grammage</th><th>Couleur</th><th>Prix/page</th></tr>";
        foreach ($pricing_test as $price) {
            echo "<tr>";
            echo "<td>" . $price['paper_size'] . "</td>";
            echo "<td>" . $price['paper_weight'] . "</td>";
            echo "<td>" . $price['color_mode'] . "</td>";
            echo "<td>" . number_format($price['price_per_page'], 3) . "€</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ Aucun tarif trouvé - Importez le script SQL<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur tarifs: " . $e->getMessage() . "<br>";
}

// Test des coûts de finition
echo "<h2>5. Test coûts de finition</h2>";
try {
    $finishing_test = fetchAll("SELECT service_name, cost, cost_type FROM finishing_costs WHERE is_active = 1");
    
    if (!empty($finishing_test)) {
        echo "✅ Finitions disponibles:<br>";
        foreach ($finishing_test as $finishing) {
            echo "- " . $finishing['service_name'] . ": " . 
                 number_format($finishing['cost'], 2) . "€ (" . $finishing['cost_type'] . ")<br>";
        }
    } else {
        echo "⚠️ Aucune finition trouvée<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur finitions: " . $e->getMessage() . "<br>";
}

// Test API endpoints
echo "<h2>6. Test des APIs</h2>";
$api_files = [
    'api/register.php' => 'API d\'inscription',
    'api/login.php' => 'API de connexion'
];

foreach ($api_files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file) - Fichier présent<br>";
    } else {
        echo "❌ $description ($file) - Fichier manquant<br>";
    }
}

// Informations système
echo "<h2>7. Informations système</h2>";
echo "- PHP Version: " . phpversion() . "<br>";
echo "- Extensions installées: <br>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'filter'];
foreach ($required_extensions as $ext) {
    echo "  • $ext: " . (extension_loaded($ext) ? "✅ Installé" : "❌ Manquant") . "<br>";
}

echo "<h2>✅ Test terminé</h2>";
echo "<p><strong>Pour utiliser le système:</strong></p>";
echo "<ol>";
echo "<li>Vérifiez que toutes les tables sont présentes (importez scriptsql.sql si nécessaire)</li>";
echo "<li>Les APIs sont prêtes dans le dossier api/</li>";
echo "<li>Les fonctions sont disponibles dans includes/</li>";
echo "<li>Testez l'inscription depuis votre modal</li>";
echo "</ol>";
?>