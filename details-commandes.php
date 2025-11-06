<?php
session_start();
include 'config/database.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Veuillez vous connecter pour réserver.");
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['nom'])) {
    die("Produit non spécifié !");
}

$nomProduit = htmlspecialchars(urldecode($_GET['nom']));

try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM produits WHERE nom = :nom");
    $stmt->bindParam(':nom', $nomProduit);
    $stmt->execute();

    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit)
        die("Produit introuvable !");
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Gestion de la réservation
$reservation_effectuee = false;
$reservation_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_reservation'])) {
    $quantite = intval($_POST['quantite']);
    if ($quantite < 1)
        $quantite = 1;

    $montant_total = $quantite * $produit['prix'];

    try {
        $stmt = $conn->prepare("INSERT INTO reservations (utilisateur_id, produit_id, quantite, montant_total, statut) VALUES (:user_id, :produit_id, :quantite, :montant_total, 'confirmée')");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':produit_id', $produit['id']);
        $stmt->bindParam(':quantite', $quantite);
        $stmt->bindParam(':montant_total', $montant_total);
        $stmt->execute();

        $reservation_effectuee = true;
        $reservation_info = [
            'nom' => $produit['nom'],
            'quantite' => $quantite,
            'montant' => number_format($montant_total, 2, ',', ' ')
        ];
    } catch (PDOException $e) {
        die("Erreur lors de la réservation : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Détails du produit - <?= $produit['nom'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body class="bg-gray-100 font-sans" style="font-family: 'Arial', sans-serif;">

    <div class="max-w-6xl mx-auto mt-10 bg-white shadow-lg rounded-lg p-6 flex flex-col lg:flex-row gap-6">
        <!-- Image produit -->
        <div class="lg:w-1/2 flex justify-center items-center">
            <img src="<?= $produit['image'] ?>" alt="<?= $produit['nom'] ?>"
                class="rounded-lg w-full object-cover max-h-[400px]">
        </div>

        <!-- Détails produit -->
        <div class="lg:w-1/2 flex flex-col justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-4 uppercase"><?= $produit['nom'] ?></h1>
                <p class="text-gray-700 mb-4"><?= nl2br($produit['description']) ?></p>
                <p class="text-xl font-semibold mb-2">Prix : <span
                        class="text-green-600"><?= number_format($produit['prix'], 2, ',', ' ') ?> €</span></p>
                <p class="mb-2">Quantité disponible : <span class="font-medium"><?= $produit['quantite'] ?></span></p>
                <p class="mb-4">Catégorie : <span class="font-medium"><?= $produit['categorie'] ?></span></p>
            </div>

            <div class="mt-4">
                <input type="number" id="quantiteProduit" value="1" min="1" max="<?= $produit['quantite'] ?>"
                    class="border rounded p-2 w-24 mr-2">
                <button id="btnCommander"
                    class="bg-[#811313] hover:bg-[#053d36] text-white px-4 py-2 rounded transition">
                    Commander
                </button>
            </div>
        </div>
    </div>

    <!-- Modal confirmation -->
    <div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md text-center">
            <h2 class="text-xl font-bold mb-4">Voulez-vous confirmer votre réservation ?</h2>
            <div class="flex justify-center gap-4">
                <button id="ouiConfirmer"
                    class="bg-[#053d36] hover:bg-[#811313] text-white px-4 py-2 rounded">Oui</button>
                <button id="nonConfirmer"
                    class="bg-[#811313]hover:bg-[#053d36] text-white px-4 py-2 rounded">Non</button>
            </div>
        </div>
    </div>

    <!-- Modal succès -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md text-center">
            <h2 class="text-xl font-bold mb-4">Félicitations !</h2>
            <p id="successText" class="mb-4"></p>
            <button id="telechargerPDF" class="bg-[#811313] hover:bg-[#053d36] text-white px-4 py-2 rounded">Télécharger
                le reçu</button>
        </div>
    </div>
    <!--=================== Search JS ====================-->
    <script src="assets/js/search.js"> </script>

    <script>
        const btnCommander = document.getElementById('btnCommander');
        const confirmModal = document.getElementById('confirmModal');
        const successModal = document.getElementById('successModal');
        const quantiteInput = document.getElementById('quantiteProduit');
        const successText = document.getElementById('successText');

        btnCommander.addEventListener('click', () => {
            confirmModal.classList.remove('hidden');
        });

        document.getElementById('nonConfirmer').addEventListener('click', () => {
            confirmModal.classList.add('hidden');
        });

        document.getElementById('ouiConfirmer').addEventListener('click', async () => {
            const quantite = parseInt(quantiteInput.value);

            // Appel AJAX pour enregistrer la réservation
            const formData = new FormData();
            formData.append('confirmer_reservation', true);
            formData.append('quantite', quantite);

            const response = await fetch("", {
                method: "POST",
                body: formData
            });
            const text = await response.text();

            confirmModal.classList.add('hidden');
            successModal.classList.remove('hidden');
            successText.innerHTML = `Vous avez réservé <strong>${quantite}</strong> unité(s) du produit <strong><?= $produit['nom'] ?></strong>.`;
        });

        document.getElementById('telechargerPDF').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(18);
            doc.text("Reçu de réservation", 105, 20, { align: "center" });
            doc.setFontSize(14);
            doc.text(`Produit : <?= $produit['nom'] ?>`, 20, 40);
            doc.text(`Quantité : ${quantiteInput.value}`, 20, 50);
            doc.text(`Montant total : ${(<?= $produit['prix'] ?> * quantiteInput.value).toFixed(2)} €`, 20, 60);
            doc.text(`Client : <?= $_SESSION['user_nom'] ?>`, 20, 70);
            doc.save("reçu_<?= $produit['nom'] ?>.pdf");
        });
    </script>

</body>

</html>