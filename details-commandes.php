<?php declare(strict_types=1);
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
    <title><?= $produit['nom'] ?> - Johnson Jr Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3730a3',
                        accent: '#7e22ce',
                        brand: '#811313',
                        brandHover: '#053d36'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen" style="font-family: 'Arial', sans-serif;">

    <!-- Header Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div
                        class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center group-hover:scale-105 transition duration-200">
                        <i class="fas fa-hammer text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Johnson Jr Construction</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="mes-commandes.php"
                        class="text-gray-600 hover:text-blue-600 transition duration-200 flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-blue-50">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Mes Commandes</span>
                    </a>
                    <a href="deconnexion.php"
                        class="text-gray-600 hover:text-red-600 transition duration-200 flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-red-50">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="flex space-x-2 text-sm text-gray-600">
            <a href="index.php" class="hover:text-blue-600 transition duration-200 flex items-center space-x-1">
                <i class="fas fa-home"></i>
                <span>Accueil</span>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-blue-600 font-medium"><?= $produit['nom'] ?></span>
        </nav>
    </div>

    <!-- Product Details -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">

                <!-- Product Image -->
                <div class="flex flex-col items-center">
                    <div class="w-full max-w-md rounded-2xl overflow-hidden shadow-lg bg-gray-100">
                        <img src="<?= $produit['image'] ?>" alt="<?= $produit['nom'] ?>"
                            class="w-full h-96 object-cover transition duration-300 hover:scale-105">
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <div class="w-4 h-4 bg-blue-600 rounded-full"></div>
                        <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="flex flex-col justify-between">
                    <div>
                        <!-- Category Badge -->
                        <div
                            class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium mb-4">
                            <i class="fas fa-tag mr-2"></i>
                            <?= $produit['categorie'] ?>
                        </div>

                        <!-- Product Name -->
                        <h1 class="text-4xl font-bold text-gray-800 mb-4 leading-tight">
                            <?= $produit['nom'] ?>
                        </h1>

                        <!-- Description -->
                        <div class="prose prose-lg mb-6">
                            <p class="text-gray-600 leading-relaxed text-lg">
                                <?= nl2br($produit['description']) ?>
                            </p>
                        </div>

                        <!-- Features -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-boxes text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">En stock</p>
                                    <p class="font-semibold text-gray-800"><?= $produit['quantite'] ?> unités</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-shipping-fast text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Livraison</p>
                                    <p class="font-semibold text-gray-800">Sous 48h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price & Order Section -->
                    <div class="border-t pt-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Prix unitaire</p>
                                <p class="text-3xl font-bold text-green-600">
                                    <?= number_format(floatval($produit['prix']), 2, ',', ' ') ?>
                                    <?= $produit['devise'] ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 mb-1">Disponibilité</p>
                                <div class="flex items-center space-x-2 text-green-600">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="font-semibold">En stock</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="bg-gray-50 rounded-2xl p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <label class="text-lg font-semibold text-gray-800">Quantité</label>
                                <span class="text-sm text-gray-500">Max: <?= $produit['quantite'] ?></span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex items-center border border-gray-300 rounded-2xl bg-white overflow-hidden">
                                    <button id="decreaseQty"
                                        class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition duration-200">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" id="quantiteProduit" value="1" min="1"
                                        max="<?= $produit['quantite'] ?>"
                                        class="w-20 h-12 text-center border-0 bg-transparent text-lg font-semibold focus:outline-none focus:ring-0">
                                    <button id="increaseQty"
                                        class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition duration-200">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="text-right flex-1">
                                    <p class="text-sm text-gray-500">Total</p>
                                    <p id="totalPrice" class="text-xl font-bold text-green-600">
                                        <?= number_format(floatval($produit['prix']), 2, ',', ' ') ?>
                                        <?= $produit['devise'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Button -->
                        <button id="btnCommander"
                            class="w-full bg-gradient-to-r from-brand to-brandHover hover:from-brandHover hover:to-brand text-white py-4 px-8 rounded-2xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center space-x-3">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <span>Commander maintenant</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Trust Badges -->
                        <div class="flex justify-center space-x-6 mt-6 text-gray-400">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-shield-alt"></i>
                                <span class="text-sm">Paiement sécurisé</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-undo"></i>
                                <span class="text-sm">Retour facile</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-headset"></i>
                                <span class="text-sm">Support 24/7</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal"
        class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-cart text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Confirmer la commande</h2>
                <p class="text-gray-600 mb-6">Voulez-vous confirmer votre réservation pour ce produit ?</p>
                <div class="flex justify-center space-x-4">
                    <button id="nonConfirmer"
                        class="px-8 py-3 border-2 border-gray-300 text-gray-700 rounded-2xl font-semibold hover:bg-gray-50 transition duration-200 flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Annuler</span>
                    </button>
                    <button id="ouiConfirmer"
                        class="px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl font-semibold hover:from-green-600 hover:to-green-700 transition duration-200 flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-check"></i>
                        <span>Confirmer</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal"
        class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Commande confirmée !</h2>
                <p id="successText" class="text-gray-600 mb-2"></p>

                <!-- Message de paiement mis à jour -->
                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
                    <div class="flex items-start space-x-3">
                        <div
                            class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <i class="fas fa-mobile-alt text-blue-600 text-sm"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-blue-800 mb-1">Instructions de paiement</p>
                            <p class="text-xs text-blue-700 leading-relaxed">
                                Une fois le reçu téléchargé, veillez faire le paiement au numéro suivant :
                            </p>
                            <p class="text-sm font-bold text-blue-900 mt-2">
                                <i class="fas fa-phone mr-2"></i>+243 977 199 714
                            </p>
                        </div>
                    </div>
                </div>

                <button id="telechargerPDF"
                    class="w-full bg-gradient-to-r from-brand to-brandHover text-white py-4 rounded-2xl font-semibold hover:from-brandHover hover:to-brand transition duration-200 flex items-center justify-center space-x-2 shadow-lg mb-4">
                    <i class="fas fa-download"></i>
                    <span>Télécharger le reçu</span>
                </button>

                <a href="mes-commandes.php"
                    class="inline-block text-blue-600 hover:text-blue-800 transition duration-200 font-semibold text-sm">
                    <i class="fas fa-shopping-bag mr-2"></i>Voir mes commandes →
                </a>
            </div>
        </div>
    </div>

    <script>
        const btnCommander = document.getElementById('btnCommander');
        const confirmModal = document.getElementById('confirmModal');
        const successModal = document.getElementById('successModal');
        const quantiteInput = document.getElementById('quantiteProduit');
        const successText = document.getElementById('successText');
        const totalPrice = document.getElementById('totalPrice');
        const decreaseBtn = document.getElementById('decreaseQty');
        const increaseBtn = document.getElementById('increaseQty');

        const prixUnitaire = <?= floatval($produit['prix']) ?>;
        const devise = '<?= $produit['devise'] ?>';

        // Gestion de la quantité
        function updateTotalPrice() {
            const quantite = parseInt(quantiteInput.value);
            const total = quantite * prixUnitaire;
            totalPrice.textContent = `${total.toFixed(2)} ${devise}`;
        }

        decreaseBtn.addEventListener('click', () => {
            let value = parseInt(quantiteInput.value);
            if (value > 1) {
                quantiteInput.value = value - 1;
                updateTotalPrice();
            }
        });

        increaseBtn.addEventListener('click', () => {
            let value = parseInt(quantiteInput.value);
            const max = parseInt(quantiteInput.max);
            if (value < max) {
                quantiteInput.value = value + 1;
                updateTotalPrice();
            }
        });

        quantiteInput.addEventListener('input', updateTotalPrice);

        // Gestion des modales
        btnCommander.addEventListener('click', () => {
            confirmModal.classList.remove('hidden');
            setTimeout(() => {
                confirmModal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
        });

        document.getElementById('nonConfirmer').addEventListener('click', () => {
            confirmModal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                confirmModal.classList.add('hidden');
            }, 300);
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

            confirmModal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                confirmModal.classList.add('hidden');
                successModal.classList.remove('hidden');
                setTimeout(() => {
                    successModal.querySelector('.transform').classList.remove('scale-95');
                }, 10);
            }, 300);

            successText.innerHTML = `Vous avez réservé <strong>${quantite}</strong> unité(s) du produit <strong><?= $produit['nom'] ?></strong>.`;
        });

        document.getElementById('telechargerPDF').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(20);
            doc.setTextColor(30, 64, 175);
            doc.text("Johnson Jr Construction", 105, 20, { align: "center" });

            doc.setFontSize(16);
            doc.setTextColor(0, 0, 0);
            doc.text("Reçu de réservation", 105, 35, { align: "center" });

            doc.setFontSize(12);
            doc.text(`Produit : <?= $produit['nom'] ?>`, 20, 55);
            doc.text(`Quantité : ${quantiteInput.value}`, 20, 65);
            doc.text(`Prix unitaire : ${prixUnitaire.toFixed(2)} ${devise}`, 20, 75);
            doc.text(`Montant total : ${(prixUnitaire * quantiteInput.value).toFixed(2)} ${devise}`, 20, 85);
            doc.text(`Client : <?= $_SESSION['user_nom'] ?>`, 20, 95);
            doc.text(`Date : ${new Date().toLocaleDateString('fr-FR')}`, 20, 105);

            doc.save("reçu_<?= $produit['nom'] ?>.pdf");
        });
    </script>

</body>

</html>