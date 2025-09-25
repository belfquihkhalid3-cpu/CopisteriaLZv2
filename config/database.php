<?php
/**
 * Configuration de la base de données Copisteria
 * Connexion PDO avec gestion d'erreurs
 */

// Dans config/database.php, ajouter

if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
}
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'c2662029c_copie');
define('DB_USER', 'c2662029c_lz');
define('DB_PASS', 'P@ssw00rd2025');
define('DB_CHARSET', 'utf8mb4');

// Variables globales
$pdo = null;

try {
    // Création de la connexion PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    // Test de connexion réussi (optionnel - à retirer en production)
    // echo "Connexion à la base de données réussie !";
    
} catch (PDOException $e) {
    // En production, logger l'erreur au lieu de l'afficher
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    
    // Message d'erreur générique pour l'utilisateur
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

/**
 * Fonction utilitaire pour exécuter des requêtes préparées
 * @param string $sql Requête SQL avec placeholders
 * @param array $params Paramètres de la requête
 * @return PDOStatement|false
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erreur SQL: " . $e->getMessage() . " | Query: " . $sql);
        return false;
    }
}

/**
 * Fonction pour récupérer un seul résultat
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fonction pour récupérer tous les résultats
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Fonction pour récupérer le dernier ID inséré
 * @return string
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Fonction pour commencer une transaction
 */
function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

/**
 * Fonction pour valider une transaction
 */
function commit() {
    global $pdo;
    return $pdo->commit();
}

/**
 * Fonction pour annuler une transaction
 */
function rollback() {
    global $pdo;
    return $pdo->rollback();
}

/**
 * Fonction pour tester la connexion
 * @return bool
 */
function testConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Fonction pour récupérer les informations de la base de données
 * @return array
 */
function getDatabaseInfo() {
    return [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'user' => DB_USER,
        'charset' => DB_CHARSET,
        'connected' => testConnection()
    ];
}
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
// Fermer la connexion à la fin du script (optionnel)
// register_shutdown_function(function() {
//     global $pdo;
//     $pdo = null;
// });
?>