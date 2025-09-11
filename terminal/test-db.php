<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

echo "<h1>Test Base de Données Terminal</h1>";

// Test 1 : Connexion
try {
    echo "<h2>1. Test connexion</h2>";
    $test = fetchOne("SELECT 1 as test");
    echo "✅ Connexion OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur connexion: " . $e->getMessage() . "<br>";
    exit();
}

// Test 2 : Structure table files
echo "<h2>2. Structure table files</h2>";
try {
    $columns = fetchAll("DESCRIBE files");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Erreur table: " . $e->getMessage() . "<br>";
}

// Test 3 : INSERT simple
// Remplace la section "Test 3" dans terminal/test-db.php :
echo "<h2>3. Test INSERT détaillé</h2>";
try {
    $sql = "INSERT INTO files (user_id, original_name, stored_name, file_path, file_size, mime_type, page_count, file_hash, status) 
            VALUES (0, 'test.pdf', 'test123.pdf', '/uploads/test.pdf', 1000, 'application/pdf', 1, 'hash123', 'READY')";
    
    echo "SQL: $sql<br>";
    
    // Test direct PDO
    global $pdo;
    echo "PDO object: " . (is_object($pdo) ? "✅ OK" : "❌ Manquant") . "<br>";
    
    try {
        $stmt_direct = $pdo->prepare($sql);
        echo "Prepare: ✅ OK<br>";
        
        $result = $stmt_direct->execute();
        echo "Execute result: " . ($result ? "✅ OK" : "❌ Failed") . "<br>";
        
        if ($result) {
            $id = $pdo->lastInsertId();
            echo "Last Insert ID: $id<br>";
            
            // Nettoyer
            $pdo->exec("DELETE FROM files WHERE id = $id");
        } else {
            $error = $stmt_direct->errorInfo();
            echo "Erreur execute: " . print_r($error, true) . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Erreur PDO directe: " . $e->getMessage() . "<br>";
    }
    
    // Test avec executeQuery
    echo "<br><strong>Test avec executeQuery:</strong><br>";
    $stmt = executeQuery($sql);
    echo "executeQuery result: " . ($stmt ? "✅ OK" : "❌ Failed") . "<br>";
    
    if (!$stmt) {
        $error = $pdo->errorInfo();
        echo "Erreur executeQuery: " . print_r($error, true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

// Ajoute après le test 3 :
echo "<h2>4. Test colonnes obligatoires</h2>";
$required_columns = ['user_id', 'original_name', 'stored_name', 'file_path', 'file_size', 'mime_type', 'page_count', 'file_hash', 'status'];

$existing_columns = fetchAll("SHOW COLUMNS FROM files");
$column_names = array_column($existing_columns, 'Field');

foreach ($required_columns as $col) {
    if (in_array($col, $column_names)) {
        echo "✅ $col existe<br>";
    } else {
        echo "❌ $col manquante<br>";
    }
}

// Test 4 : executeQuery function
echo "<h2>4. Test fonction executeQuery</h2>";
if (function_exists('executeQuery')) {
    echo "✅ Fonction executeQuery existe<br>";
} else {
    echo "❌ Fonction executeQuery manquante<br>";
}

if (function_exists('getLastInsertId')) {
    echo "✅ Fonction getLastInsertId existe<br>";
} else {
    echo "❌ Fonction getLastInsertId manquante<br>";
}
?>