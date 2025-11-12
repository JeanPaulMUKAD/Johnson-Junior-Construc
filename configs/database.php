<?php declare(strict_types=1);     
    $host = "127.0.0.1:3306";
    $user = "u913148723_Johnsonjr";
    $pass = "Johnsonjr2003";
    $dbname = "u913148723_e_commerce_db";

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }
?>