<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_commerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>

