<?php
session_start();
include 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redirection si pas connecté
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = getConnection();

    // Récupérer toutes les commandes de l'utilisateur avec les informations produit
    $stmt = $conn->prepare("
        SELECT r.id AS reservation_id, r.quantite, r.montant_total, r.statut, r.date_reservation,
               p.nom AS produit_nom, p.image AS produit_image
        FROM reservations r
        INNER JOIN produits p ON r.produit_id = p.id
        WHERE r.utilisateur_id = :user_id
        ORDER BY r.date_reservation DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes commandes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans" style="font-family: 'Arial', sans-serif;">

    <div class="max-w-6xl mx-auto py-10">
        <h1 class="text-3xl font-bold mb-6">Mes commandes</h1>

        <?php if (empty($commandes)): ?>
            <p class="bg-[#811313] text-white py-2 px-4">Vous n'avez aucune commande pour le moment.</p>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($commandes as $commande): ?>
                    <div class="bg-white shadow rounded-lg p-6 flex flex-col md:flex-row items-center gap-6">
                        
                        <!-- Image du produit -->
                        <div class="w-32 h-32 flex-shrink-0">
                            <img src="<?= $commande['produit_image'] ?>" alt="<?= $commande['produit_nom'] ?>" class="w-full h-full object-cover rounded">
                        </div>

                        <!-- Détails de la commande -->
                        <div class="flex-1">
                            <h2 class="text-xl font-semibold mb-2 uppercase"><?= $commande['produit_nom'] ?></h2>
                            <p>Quantité : <span class="font-medium"><?= $commande['quantite'] ?></span></p>
                            <p>Montant total : <span class="font-medium text-green-600"><?= number_format($commande['montant_total'], 2, ',', ' ') ?> €</span></p>
                            <p>Date : <span class="font-medium"><?= date('d/m/Y H:i', strtotime($commande['date_reservation'])) ?></span></p>
                            <p>Statut : 
                                <?php
                                    $color = match($commande['statut']) {
                                        'en_attente' => 'text-yellow-500',
                                        'confirmée' => 'text-green-600',
                                        'annulée' => 'text-red-600',
                                        default => 'text-gray-700'
                                    };
                                ?>
                                <span class="<?= $color ?> font-semibold"><?= ucfirst($commande['statut']) ?></span>
                            </p>
                        </div>

                        <!-- Bouton télécharger le reçu -->
                        <div>
                            <form method="POST" action="generer-recu.php">
                                <input type="hidden" name="reservation_id" value="<?= $commande['reservation_id'] ?>">
                                <button type="submit" class="bg-[#811313] hover:bg-[#053d36] text-white px-4 py-2 rounded transition">
                                    Télécharger le reçu
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!--=================== Search JS ====================-->
    <script src="assets/js/search.js"> </script>

</body>
</html>
