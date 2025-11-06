<?php
session_start();
include 'config/database.php';

// Vérifier si utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_POST['reservation_id'])) {
    die("Réservation non spécifiée !");
}

$reservation_id = (int)$_POST['reservation_id'];
$user_id = $_SESSION['user_id'];

try {
    $conn = getConnection();

    // Récupérer la réservation avec les informations produit
    $stmt = $conn->prepare("
        SELECT r.id AS reservation_id, r.quantite, r.montant_total, r.statut, r.date_reservation,
               p.nom AS produit_nom, p.image AS produit_image, u.nom AS client_nom, u.email AS client_email
        FROM reservations r
        INNER JOIN produits p ON r.produit_id = p.id
        INNER JOIN utilisateurs u ON r.utilisateur_id = u.id
        WHERE r.id = :reservation_id AND r.utilisateur_id = :user_id
        LIMIT 1
    ");
    $stmt->bindParam(':reservation_id', $reservation_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        die("Réservation introuvable !");
    }

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Inclure TCPDF
require_once __DIR__ . 'tcpdf.php';


$pdf = new TCPDF();
$pdf->SetCreator('Johnson Construction');
$pdf->SetAuthor('Johnson Construction');
$pdf->SetTitle('Reçu de réservation');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// Contenu du PDF
$html = '
<h1 style="color:#811313;">Reçu de réservation</h1>
<p><strong>Client :</strong> '.$reservation['client_nom'].' ('.$reservation['client_email'].')</p>
<p><strong>Date :</strong> '.date('d/m/Y H:i', strtotime($reservation['date_reservation'])).'</p>
<p><strong>Produit :</strong> '.$reservation['produit_nom'].'</p>
<p><strong>Quantité :</strong> '.$reservation['quantite'].'</p>
<p><strong>Montant total :</strong> '.number_format($reservation['montant_total'], 2, ',', ' ').' €</p>
<p><strong>Statut :</strong> '.$reservation['statut'].'</p>
<br><br>
<img src="'.$reservation['produit_image'].'" width="150" height="150" style="border:1px solid #000;">
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('recu_reservation_'.$reservation_id.'.pdf', 'D');
