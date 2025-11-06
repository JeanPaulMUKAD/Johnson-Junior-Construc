<?php
session_start();

// Message de succès
$_SESSION['logout_success'] = true;

// Détruire toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil avec un message
header("Location: index.php");
exit();
?>
