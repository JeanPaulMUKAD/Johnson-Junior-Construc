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
// Récupérer les informations de l'utilisateur
try {
    $stmt_user = $conn->prepare("SELECT nom FROM utilisateurs WHERE id = :user_id");
    $stmt_user->bindParam(':user_id', $user_id);
    $stmt_user->execute();
    $utilisateur = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $nomClient = $utilisateur ? $utilisateur['nom'] : 'Client';
} catch (PDOException $e) {
    $nomClient = 'Client';
}

$nomProduit = htmlspecialchars(urldecode($_GET['nom']));

try {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM produits WHERE nom = :nom");
    $stmt->bindParam(':nom', $nomProduit);
    $stmt->execute();

    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        die("Produit introuvable !");
    }

    // Gestion du chemin de l'image
    $imageProduit = $produit['image'];
    if (!empty($imageProduit)) {
        if (strpos($imageProduit, 'uploads/') === 0) {
            $imagePath = "admin/" . $imageProduit;
        } else {
            $imagePath = "admin/uploads/" . $imageProduit;
        }

        // Vérifier si le fichier existe
        if (!file_exists($imagePath)) {
            $imagePath = "admin/uploads/default.jpg";
        }
    } else {
        $imagePath = "admin/uploads/default.jpg";
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Gestion de la réservation
$reservation_effectuee = false;
$reservation_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_reservation'])) {

    // Récupération et validation des données
    $quantite = isset($_POST['quantite']) ? intval($_POST['quantite']) : 0;
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';

    // Validation des champs obligatoires
    if (empty($telephone)) {
        echo "Le numéro de téléphone est obligatoire";
        exit;
    }

    if (empty($adresse)) {
        echo "L'adresse est obligatoire";
        exit;
    }

    if ($quantite < 1) {
        $quantite = 1;
    }

    // Vérifier que la quantité ne dépasse pas le stock
    if ($quantite > $produit['quantite']) {
        echo "Quantité demandée supérieure au stock disponible";
        exit;
    }

    $montant_total = $quantite * floatval($produit['prix']);

    try {
        // Commencer une transaction
        $conn->beginTransaction();

        // Insérer la réservation
        $stmt = $conn->prepare("INSERT INTO reservations (utilisateur_id, produit_id, quantite, montant_total, telephone, adresse, statut, date_reservation) VALUES (:user_id, :produit_id, :quantite, :montant_total, :telephone, :adresse, 'confirmée', NOW())");

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':produit_id', $produit['id'], PDO::PARAM_INT);
        $stmt->bindParam(':quantite', $quantite, PDO::PARAM_INT);
        $stmt->bindParam(':montant_total', $montant_total);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':adresse', $adresse);

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'insertion de la réservation");
        }

        // Mettre à jour le stock du produit
        $updateStmt = $conn->prepare("UPDATE produits SET quantite = quantite - :quantite WHERE id = :produit_id AND quantite >= :quantite");
        $updateStmt->bindParam(':quantite', $quantite, PDO::PARAM_INT);
        $updateStmt->bindParam(':produit_id', $produit['id'], PDO::PARAM_INT);

        if (!$updateStmt->execute() || $updateStmt->rowCount() == 0) {
            throw new Exception("Stock insuffisant pour effectuer la réservation");
        }

        // Valider la transaction
        $conn->commit();

        // Réponse de succès
        echo "success";
        exit;

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }

        echo "Erreur lors de la réservation : " . $e->getMessage();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($produit['nom']) ?> - Johnson Jr Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="shortcut icon" href="assets/img/logo.jpg" type="image/x-icon">
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
            <span class="text-blue-600 font-medium"><?= htmlspecialchars($produit['nom']) ?></span>
        </nav>
    </div>

    <!-- Product Details -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">

                <!-- Product Image -->
                <div class="flex flex-col items-center">
                    <div class="w-full max-w-md rounded-2xl overflow-hidden shadow-lg bg-gray-100">
                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($produit['nom']) ?>"
                            class="w-full h-96 object-cover transition duration-300 hover:scale-105"
                            onerror="this.onerror=null; this.src='admin/uploads/default.jpg';">
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
                            <?= htmlspecialchars($produit['categorie']) ?>
                        </div>

                        <!-- Product Name -->
                        <h1 class="text-4xl font-bold text-gray-800 mb-4 leading-tight">
                            <?= htmlspecialchars($produit['nom']) ?>
                        </h1>

                        <!-- Description -->
                        <div class="prose prose-lg mb-6">
                            <p class="text-gray-600 leading-relaxed text-lg">
                                <?= nl2br(htmlspecialchars($produit['description'])) ?>
                            </p>
                        </div>

                        <!--Poids-->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-2">Poids</h2>
                            <p class="text-gray-600 text-lg">
                                <?= htmlspecialchars($produit['poids']) ?>
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
                                    <?= htmlspecialchars($produit['devise']) ?>
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
                                    <button type="button" id="decreaseQty"
                                        class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition duration-200">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" id="quantiteProduit" value="1" min="1"
                                        max="<?= $produit['quantite'] ?>"
                                        class="w-20 h-12 text-center border-0 bg-transparent text-lg font-semibold focus:outline-none focus:ring-0">
                                    <button type="button" id="increaseQty"
                                        class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition duration-200">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="text-right flex-1">
                                    <p class="text-sm text-gray-500">Total</p>
                                    <p id="totalPrice" class="text-xl font-bold text-green-600">
                                        <?= number_format(floatval($produit['prix']), 2, ',', ' ') ?>
                                        <?= htmlspecialchars($produit['devise']) ?>
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
            <div class="p-8">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-cart text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">Confirmer la commande</h2>
                <p class="text-gray-600 mb-6 text-center">Voulez-vous confirmer votre réservation pour ce produit ?</p>

                <!-- Formulaire avec téléphone et adresse -->
                <form id="reservationForm">
                    <div class="space-y-4 mb-6">
                        <!-- Champ Téléphone -->
                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-phone mr-2"></i>Téléphone *
                            </label>
                            <input type="tel" id="telephone" name="telephone" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Votre numéro de téléphone">
                            <p class="text-xs text-gray-500 mt-1">Ex: +243 97 123 4567</p>
                        </div>

                        <!-- Champ Adresse -->
                        <div>
                            <label for="adresse" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-2"></i>Adresse de livraison *
                            </label>
                            <textarea id="adresse" name="adresse" required rows="3"
                                class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 resize-none"
                                placeholder="Votre adresse complète de livraison"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-center space-x-4">
                        <button type="button" id="nonConfirmer"
                            class="px-8 py-3 border-2 border-gray-300 text-gray-700 rounded-2xl font-semibold hover:bg-gray-50 transition duration-200 flex items-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Annuler</span>
                        </button>
                        <button type="submit" id="ouiConfirmer"
                            class="px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl font-semibold hover:from-green-600 hover:to-green-700 transition duration-200 flex items-center space-x-2 shadow-lg">
                            <i class="fas fa-check"></i>
                            <span>Confirmer</span>
                        </button>
                    </div>
                </form>
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

                <!-- Message de paiement -->
                <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
                    <div class="flex items-start space-x-3">
                        <div
                            class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <i class="fas fa-mobile-alt text-blue-600 text-sm"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-blue-800 mb-2">Instructions de paiement</p>
                            <p class="text-xs text-blue-700 leading-relaxed mb-3">
                                Une fois le reçu téléchargé, veillez faire le paiement aux numéros suivants :
                            </p>
                            <div class="space-y-2">
                                <p class="text-sm text-orange-900 flex items-center">
                                    <i class="fas fa-phone mr-2 w-5"></i>
                                    <span class="font-semibold">AirtelMoney:</span>
                                    <span class="ml-2">+243 975 413 369</span>
                                </p>
                                <p class="text-sm text-red-900 flex items-center">
                                    <i class="fas fa-phone mr-2 w-5"></i>
                                    <span class="font-semibold">OrangeMoney:</span>
                                    <span class="ml-2">+243 851 653 923</span>
                                </p>
                                <p class="text-sm text-red-900 flex items-center">
                                    <i class="fas fa-phone mr-2 w-5"></i>
                                    <span class="font-semibold">M-Pesa:</span>
                                    <span class="ml-2">+243 839 049 583</span>
                                </p>
                                <p class="text-sm text-blue-900 flex items-center">
                                    <i class="fas fa-user mr-2 w-5"></i>
                                    <span class="font-semibold">Nom:</span>
                                    <span class="ml-2">Patrick MULUMBA JEAN</span>
                                </p>
                            </div>
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
        const reservationForm = document.getElementById('reservationForm');

        const prixUnitaire = <?= floatval($produit['prix']) ?>;
        const devise = '<?= $produit['devise'] ?>';
        const nomProduit = '<?= $produit['nom'] ?>';
        const nomClient = '<?= addslashes($nomClient) ?>';

        // Gestion de la quantité
        function updateTotalPrice() {
            const quantite = parseInt(quantiteInput.value) || 1;
            const total = quantite * prixUnitaire;
            totalPrice.textContent = `${total.toFixed(2).replace('.', ',')} ${devise}`;
        }

        decreaseBtn.addEventListener('click', () => {
            let value = parseInt(quantiteInput.value) || 1;
            if (value > 1) {
                quantiteInput.value = value - 1;
                updateTotalPrice();
            }
        });

        increaseBtn.addEventListener('click', () => {
            let value = parseInt(quantiteInput.value) || 1;
            const max = parseInt(quantiteInput.max) || 1;
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

        // Gestion du formulaire de réservation
        reservationForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const telephone = document.getElementById('telephone').value.trim();
            const adresse = document.getElementById('adresse').value.trim();
            const quantite = parseInt(quantiteInput.value) || 1;

            // Validation côté client
            if (!telephone) {
                alert('Veuillez saisir votre numéro de téléphone');
                return;
            }

            if (!adresse) {
                alert('Veuillez saisir votre adresse de livraison');
                return;
            }

            // Désactiver le bouton pour éviter les doubles clics
            const confirmBtn = document.getElementById('ouiConfirmer');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Traitement...</span>';

            try {
                const formData = new FormData();
                formData.append('confirmer_reservation', 'true');
                formData.append('quantite', quantite);
                formData.append('telephone', telephone);
                formData.append('adresse', adresse);

                const response = await fetch(window.location.href, {
                    method: "POST",
                    body: formData
                });

                const result = await response.text();

                if (result.trim() === 'success') {
                    // SUCCÈS - Afficher la modale de confirmation
                    confirmModal.querySelector('.transform').classList.add('scale-95');
                    setTimeout(() => {
                        confirmModal.classList.add('hidden');
                        successModal.classList.remove('hidden');
                        setTimeout(() => {
                            successModal.querySelector('.transform').classList.remove('scale-95');
                        }, 10);
                    }, 300);

                    successText.innerHTML = `Vous avez réservé <strong>${quantite}</strong> unité(s) du produit <strong>${nomProduit}</strong>.`;
                } else {
                    // ÉCHEC - Afficher l'erreur
                    throw new Error(result || 'Erreur inconnue lors de la réservation');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur: ' + error.message);
            } finally {
                // Réactiver le bouton
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i><span>Confirmer</span>';
            }
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
            doc.text(`Client : ${nomClient}`, 20, 55);
            doc.text(`Produit : ${nomProduit}`, 20, 55);
            doc.text(`Quantité : ${quantiteInput.value}`, 20, 65);
            doc.text(`Prix unitaire : ${prixUnitaire.toFixed(2)} ${devise}`, 20, 75);
            doc.text(`Montant total : ${(prixUnitaire * parseInt(quantiteInput.value)).toFixed(2)} ${devise}`, 20, 85);
            doc.text(`Téléphone : ${document.getElementById('telephone').value}`, 20, 95);
            doc.text(`Adresse : ${document.getElementById('adresse').value}`, 20, 105);
            doc.text(`Date : ${new Date().toLocaleDateString('fr-FR')}`, 20, 115);

            // Instructions de paiement
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text("Instructions de paiement :", 20, 145);
            doc.text("AirtelMoney: +243 975 413 369", 20, 155);
            doc.text("OrangeMoney: +243 851 653 923", 20, 160);
            doc.text("M-Pesa: +243 839 049 583", 20, 165);
            doc.text("Nom: Patrick MULUMBA JEAN", 20, 170);

            // Pied de page
            doc.setFontSize(8);
            doc.text("Merci pour votre confiance !", 105, 185, { align: "center" });

            // Nom du fichier avec le nom du client
            doc.save("reçu_" + nomClient.replace(/[^a-z0-9]/gi, '_') + "_" + nomProduit.replace(/[^a-z0-9]/gi, '_') + ".pdf");
        });
    </script>

    <!-- SEARCH LOGO -->
    <script>
        let a = 0;
        let masque = document.createElement('div');
        let cercle = document.createElement('div');
        let angle = 0;

        window.addEventListener('load', () => {
            a = 1;

            // Le cercle commence à tourner immédiatement
            anime = setInterval(() => {
                angle += 10; // Vitesse de rotation du cercle
                cercle.style.transform = `translate(-50%, -50%) rotate(${angle}deg)`;
            }, 20);

            // Après 1 seconde, on arrête l'animation et on fait disparaître le masque
            setTimeout(() => {
                clearInterval(anime);
                masque.style.opacity = '0';
            }, 1000);

            setTimeout(() => {
                masque.style.visibility = 'hidden';
            }, 1500);
        });

        // Création du masque
        masque.style.width = '100%';
        masque.style.height = '100vh';
        masque.style.zIndex = 100000;
        masque.style.background = '#ffffff';
        masque.style.position = 'fixed';
        masque.style.top = '0';
        masque.style.left = '0';
        masque.style.opacity = '1';
        masque.style.transition = '0.5s ease';
        masque.style.display = 'flex';
        masque.style.justifyContent = 'center';
        masque.style.alignItems = 'center';
        document.body.appendChild(masque);

        // Création du cercle (réduit)
        cercle.style.width = '40px';  // Au lieu de 15vh
        cercle.style.height = '40px'; // Au lieu de 15vh
        cercle.style.border = '2px solid #f3f3f3'; // Bordure plus fine
        cercle.style.borderTop = '2px solid #2F1C6A';
        cercle.style.borderRadius = '50%';
        cercle.style.position = 'absolute';
        cercle.style.top = '50%';
        cercle.style.left = '50%';
        cercle.style.transform = 'translate(-50%, -50%)';
        cercle.style.boxSizing = 'border-box';
        cercle.style.zIndex = '1';
        masque.appendChild(cercle);

        // Variable de l'animation
        let anime;
    </script>

</body>

</html>