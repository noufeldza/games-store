<?php
/**
 * Configuration de la base de données
 * Games Store - Steam Like Platform
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'games_store');
define('DB_USER', 'root');
define('DB_PASS', '2005');

/**
 * Connexion à la base de données avec PDO
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

// Instance globale de connexion
$pdo = getDBConnection();
?>
