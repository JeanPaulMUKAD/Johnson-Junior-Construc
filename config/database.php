<?php declare(strict_types=1); 
// Configuration de la base de données
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'u913148723_e_commerce_db');
define('DB_USER', 'u913148723_Johnsonjr');
define('DB_PASS', 'Johnsonjr2003');


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

