<?php 
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Veuillez vous connecter pour réserver.");
}

$user_id = $_SESSION['user_id'];

// Initialiser la connexion
try {
    include 'config/database.php';
    $conn = getConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base : " . $e->getMessage());
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

// Vérifier si on reçoit un produit unique ou plusieurs produits
$produits = [];
$totalPanier = 0;
$quantiteTotale = 0;

if (isset($_GET['produits'])) {
    // Cas 1 : Produits multiples passés en paramètre (format JSON)
    $produitsData = json_decode($_GET['produits'], true);
    if ($produitsData && is_array($produitsData)) {
        foreach ($produitsData as $produitData) {
            try {
                $stmt = $conn->prepare("SELECT * FROM produits WHERE nom = :nom");
                $stmt->bindParam(':nom', $produitData['nom']);
                $stmt->execute();
                $produit = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($produit) {
                    $produit['quantite_panier'] = $produitData['quantite'];
                    $produit['sous_total'] = floatval($produit['prix']) * $produitData['quantite'];
                    $produits[] = $produit;
                    
                    $totalPanier += $produit['sous_total'];
                    $quantiteTotale += $produitData['quantite'];
                }
            } catch (PDOException $e) {
                // Ignorer les erreurs pour ce produit
            }
        }
    }
} elseif (isset($_GET['nom'])) {
    // Cas 2 : Un seul produit (ancienne version pour compatibilité)
    $nomProduit = htmlspecialchars(urldecode($_GET['nom']));
    try {
        $stmt = $conn->prepare("SELECT * FROM produits WHERE nom = :nom");
        $stmt->bindParam(':nom', $nomProduit);
        $stmt->execute();
        $produit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produit) {
            $produit['quantite_panier'] = 1; // Par défaut
            $produit['sous_total'] = floatval($produit['prix']);
            $produits[] = $produit;
            
            $totalPanier = $produit['sous_total'];
            $quantiteTotale = 1;
        } else {
            die("Produit introuvable !");
        }
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
} else {
    // Cas 3 : Récupérer depuis le panier en session (recommandé)
    if (isset($_SESSION['panier']) && is_array($_SESSION['panier']) && count($_SESSION['panier']) > 0) {
        foreach ($_SESSION['panier'] as $item) {
            try {
                $stmt = $conn->prepare("SELECT * FROM produits WHERE nom = :nom");
                $stmt->bindParam(':nom', $item['nom']);
                $stmt->execute();
                $produit = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($produit) {
                    $produit['quantite_panier'] = $item['quantite'];
                    $produit['sous_total'] = floatval($produit['prix']) * $item['quantite'];
                    $produits[] = $produit;
                    
                    $totalPanier += $produit['sous_total'];
                    $quantiteTotale += $item['quantite'];
                }
            } catch (PDOException $e) {
                // Ignorer les erreurs pour ce produit
            }
        }
    } else {
        die("Votre panier est vide !");
    }
}

// Si aucun produit trouvé
if (count($produits) === 0) {
    die("Aucun produit trouvé !");
}

// Gestion des réservations multiples
$reservation_effectuee = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_reservation'])) {
    // Récupération des données du formulaire
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

    try {
        // Commencer une transaction
        $conn->beginTransaction();

        // Pour chaque produit, créer une réservation séparée
        foreach ($produits as $produit) {
            $quantite = $produit['quantite_panier'];
            $sous_total = $produit['sous_total'];
            
            // Récupérer les données POST pour les quantités mises à jour si elles existent
            if (isset($_POST['produits']) && is_string($_POST['produits'])) {
                $produitsPost = json_decode($_POST['produits'], true);
                if ($produitsPost && is_array($produitsPost)) {
                    foreach ($produitsPost as $produitPost) {
                        if ($produitPost['id'] == $produit['id']) {
                            $quantite = $produitPost['quantite'];
                            $sous_total = $produitPost['prix'] * $quantite;
                            break;
                        }
                    }
                }
            }

            // Insérer la réservation pour ce produit
            $stmt = $conn->prepare("INSERT INTO reservations (utilisateur_id, produit_id, quantite, montant_total, telephone, adresse, statut, date_reservation) VALUES (:user_id, :produit_id, :quantite, :montant_total, :telephone, :adresse, 'confirmée', NOW())");

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':produit_id', $produit['id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantite', $quantite, PDO::PARAM_INT);
            $stmt->bindParam(':montant_total', $sous_total);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':adresse', $adresse);

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'insertion de la réservation pour le produit: " . $produit['nom']);
            }

            // Mettre à jour le stock du produit
            $updateStmt = $conn->prepare("UPDATE produits SET quantite = quantite - :quantite WHERE id = :produit_id AND quantite >= :quantite");
            $updateStmt->bindParam(':quantite', $quantite, PDO::PARAM_INT);
            $updateStmt->bindParam(':produit_id', $produit['id'], PDO::PARAM_INT);

            if (!$updateStmt->execute() || $updateStmt->rowCount() == 0) {
                throw new Exception("Stock insuffisant pour le produit: " . $produit['nom']);
            }
        }

        // Valider la transaction
        $conn->commit();

        // Vider le panier de la session
        unset($_SESSION['panier']);

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
    <title>Commande multiple - Johnson Jr Construction</title>
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
    <style>
        .product-item {
            transition: all 0.3s ease;
        }
        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .quantity-btn {
            transition: all 0.2s ease;
        }
        .quantity-btn:hover {
            transform: scale(1.1);
        }
        .quantity-btn:active {
            transform: scale(0.95);
        }
        .quantity-input {
            transition: border-color 0.3s ease;
        }
        .quantity-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        .devise-usd {
            color: #059669; /* Vert pour USD */
        }
        .devise-fc {
            color: #dc2626; /* Rouge pour FC */
        }
        .badge-usd {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        .badge-fc {
            background-color: #fee2e2;
            color: #7f1d1d;
            border-color: #ef4444;
        }
    </style>
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
            <span class="text-blue-600 font-medium">Récapitulatif de la commande</span>
        </nav>
    </div>

    <!-- Commande Details -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Récapitulatif de votre commande</h1>
                    <p class="text-gray-600">
                        <span id="nombreProduits"><?= count($produits) ?></span> produit(s) dans votre panier
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Vous pouvez modifier les quantités ci-dessous
                    </p>
                </div>

                <!-- Liste des produits -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-shopping-cart mr-3 text-blue-600"></i>
                        Produits commandés
                    </h2>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto scrollbar-hide p-2" id="listeProduits">
                        <?php foreach ($produits as $index => $produit): 
                            $imagePath = "";
                            if (!empty($produit['image'])) {
                                if (strpos($produit['image'], 'uploads/') === 0) {
                                    $imagePath = "admin/" . $produit['image'];
                                } else {
                                    $imagePath = "admin/uploads/" . $produit['image'];
                                }
                                if (!file_exists($imagePath)) {
                                    $imagePath = "admin/uploads/default.jpg";
                                }
                            } else {
                                $imagePath = "admin/uploads/default.jpg";
                            }
                            
                            // Déterminer la classe CSS selon la devise
                            $deviseClass = $produit['devise'] === 'USD' ? 'devise-usd' : 'devise-fc';
                            $badgeClass = $produit['devise'] === 'USD' ? 'badge-usd' : 'badge-fc';
                        ?>
                        <div class="product-item bg-gray-50 rounded-2xl p-4 border border-gray-200" data-id="<?= $produit['id'] ?>" data-index="<?= $index ?>">
                            <div class="flex items-center space-x-4">
                                <!-- Image du produit -->
                                <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden bg-gray-100">
                                    <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($produit['nom']) ?>"
                                        class="w-full h-full object-cover"
                                        onerror="this.onerror=null; this.src='admin/uploads/default.jpg';">
                                </div>
                                
                                <!-- Détails du produit -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1 mr-4">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h3 class="font-semibold text-gray-800 text-lg">
                                                    <?= htmlspecialchars($produit['nom']) ?>
                                                </h3>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold border <?= $badgeClass ?>">
                                                    <?= htmlspecialchars($produit['devise']) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($produit['description'])): ?>
                                            <p class="text-gray-600 text-sm mb-2 line-clamp-2">
                                                <?= htmlspecialchars(substr($produit['description'], 0, 100)) ?>...
                                            </p>
                                            <?php endif; ?>
                                            <?php if (!empty($produit['poids'])): ?>
                                            <p class="text-gray-500 text-sm mb-1">
                                                <i class="fas fa-weight-hanging mr-1"></i>
                                                <?= htmlspecialchars($produit['poids']) ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-gray-500 text-sm mb-1">Prix unitaire</p>
                                            <p class="font-bold text-lg <?= $deviseClass ?> prix-unitaire" data-prix="<?= floatval($produit['prix']) ?>" data-devise="<?= htmlspecialchars($produit['devise']) ?>">
                                                <?= number_format(floatval($produit['prix']), 2, ',', ' ') ?>
                                                <?= htmlspecialchars($produit['devise']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Contrôle de quantité -->
                                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-700 font-medium">Quantité :</span>
                                            <div class="flex items-center border border-gray-300 rounded-xl bg-white overflow-hidden">
                                                <button type="button" class="quantity-btn decrease-quantity w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-red-600 transition duration-200"
                                                    data-min="1"
                                                    data-max="<?= $produit['quantite'] ?>">
                                                    <i class="fas fa-minus text-sm"></i>
                                                </button>
                                                <input type="number" 
                                                       class="quantite-input w-16 h-10 text-center border-0 bg-transparent text-lg font-semibold focus:outline-none focus:ring-0 quantity-input"
                                                       value="<?= $produit['quantite_panier'] ?>"
                                                       min="1" 
                                                       max="<?= $produit['quantite'] ?>"
                                                       data-prix="<?= floatval($produit['prix']) ?>"
                                                       data-devise="<?= htmlspecialchars($produit['devise']) ?>"
                                                       data-stock="<?= $produit['quantite'] ?>">
                                                <button type="button" class="quantity-btn increase-quantity w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-green-600 transition duration-200"
                                                    data-min="1"
                                                    data-max="<?= $produit['quantite'] ?>">
                                                    <i class="fas fa-plus text-sm"></i>
                                                </button>
                                            </div>
                                            <span class="text-sm text-gray-500">
                                                Stock : <span class="font-semibold stock-display"><?= $produit['quantite'] ?></span>
                                            </span>
                                        </div>
                                        
                                        <!-- Sous-total -->
                                        <div class="text-right">
                                            <span class="text-gray-700 font-medium">Sous-total :</span>
                                            <span class="font-bold text-lg ml-2 sous-total <?= $deviseClass ?>" 
                                                  data-sous-total="<?= $produit['sous_total'] ?>"
                                                  data-devise="<?= htmlspecialchars($produit['devise']) ?>">
                                                <?= number_format($produit['sous_total'], 2, ',', ' ') ?>
                                                <?= htmlspecialchars($produit['devise']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Résumé de la commande par devise -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Total en USD -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-dollar-sign mr-3 text-green-600"></i>
                            Total en USD
                        </h2>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Produits en USD :</span>
                                <span class="font-semibold text-gray-800" id="nombreProduitsUSD">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Quantité totale USD :</span>
                                <span class="font-semibold text-gray-800" id="quantiteTotaleUSD">0</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-green-200 pt-3">
                                <span class="text-lg font-semibold text-gray-800">Montant USD :</span>
                                <span class="text-2xl font-bold text-green-600" id="montantTotalUSD">
                                    0.00 USD
                                </span>
                            </div>
                        </div>
                        
                        <!-- Taux de change (optionnel) -->
                        <div class="mt-4 p-3 bg-green-100 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-exchange-alt text-green-600"></i>
                                    <p class="text-sm text-green-800">
                                        Taux approximatif : 
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-green-900">1 USD ≈ 2,500 FC</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total en FC -->
                    <div class="bg-gradient-to-r from-red-50 to-rose-50 rounded-2xl p-6 border border-red-100">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-money-bill-wave mr-3 text-red-600"></i>
                            Total en FC
                        </h2>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Produits en FC :</span>
                                <span class="font-semibold text-gray-800" id="nombreProduitsFC">0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Quantité totale FC :</span>
                                <span class="font-semibold text-gray-800" id="quantiteTotaleFC">0</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-red-200 pt-3">
                                <span class="text-lg font-semibold text-gray-800">Montant FC :</span>
                                <span class="text-2xl font-bold text-red-600" id="montantTotalFC">
                                    0 FC
                                </span>
                            </div>
                        </div>
                        
                        <!-- Conversion en USD (optionnel) -->
                        <div class="mt-4 p-3 bg-red-100 border border-red-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-calculator text-red-600"></i>
                                    <p class="text-sm text-red-800">
                                        Équivalent USD : 
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-red-900" id="equivalantUSD">0.00 USD</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Récapitulatif général -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-8 border border-blue-100">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar mr-3 text-blue-600"></i>
                        Récapitulatif général
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-white rounded-xl border border-gray-200">
                            <p class="text-gray-500 text-sm mb-1">Total produits</p>
                            <p class="text-2xl font-bold text-gray-800" id="totalProduitsGeneraux">0</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl border border-gray-200">
                            <p class="text-gray-500 text-sm mb-1">Quantité totale</p>
                            <p class="text-2xl font-bold text-gray-800" id="quantiteTotaleGenerale">0</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl border border-gray-200">
                            <p class="text-gray-500 text-sm mb-1">Devises utilisées</p>
                            <p class="text-2xl font-bold text-blue-600" id="devisesUtilisees">0</p>
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user mr-3 text-blue-600"></i>
                        Informations client
                    </h2>
                    <div class="bg-gray-50 rounded-2xl p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-500 text-sm mb-1">Nom complet</p>
                                <p class="font-semibold text-gray-800 text-lg"><?= htmlspecialchars($nomClient) ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm mb-1">Nombre d'articles</p>
                                <p class="font-semibold text-gray-800 text-lg" id="nombreArticles">0 article(s)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bouton de confirmation -->
                <div class="text-center pt-6 border-t border-gray-200">
                    <button id="btnConfirmerCommande"
                        class="w-full max-w-md mx-auto bg-gradient-to-r from-brand to-brandHover hover:from-brandHover hover:to-brand text-white py-4 px-8 rounded-2xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center space-x-3">
                        <i class="fas fa-check-circle text-xl"></i>
                        <span>Confirmer la commande</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <p class="text-gray-500 text-sm mt-4">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Paiement 100% sécurisé - Aucune information bancaire n'est stockée sur nos serveurs
                    </p>
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
                <p class="text-gray-600 mb-6 text-center">
                    Voulez-vous confirmer votre réservation pour 
                    <span class="font-semibold text-blue-600" id="modalNombreProduits"><?= count($produits) ?></span> produit(s) ?
                </p>

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

                        <!-- Récapitulatif par devise -->
                        <div class="space-y-4">
                            <!-- Total USD -->
                            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-semibold text-green-800">Total USD :</span>
                                    <span class="font-bold text-green-600" id="modalMontantTotalUSD">0.00 USD</span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-green-700">
                                    <span>Produits : <span id="modalProduitsUSD">0</span></span>
                                    <span>Quantité : <span id="modalQuantiteUSD">0</span></span>
                                </div>
                            </div>
                            
                            <!-- Total FC -->
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-semibold text-red-800">Total FC :</span>
                                    <span class="font-bold text-red-600" id="modalMontantTotalFC">0 FC</span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-red-700">
                                    <span>Produits : <span id="modalProduitsFC">0</span></span>
                                    <span>Quantité : <span id="modalQuantiteFC">0</span></span>
                                </div>
                            </div>
                            
                            <!-- Récapitulatif global -->
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Récapitulatif global :</p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li class="flex justify-between">
                                        <span>Total produits :</span>
                                        <span class="font-semibold" id="modalProduitsCount">0</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span>Quantité totale :</span>
                                        <span class="font-semibold" id="modalQuantiteTotale">0</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span>Devises :</span>
                                        <span class="font-semibold" id="modalDevisesUtilisees">0</span>
                                    </li>
                                </ul>
                            </div>
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
                <p id="successText" class="text-gray-600 mb-2">
                    Votre commande de <strong><span id="successProduitsCount"><?= count($produits) ?></span> produit(s)</strong> a été enregistrée avec succès.
                </p>
                
                <!-- Totaux par devise -->
                <div class="space-y-3 mb-4">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-green-800">Total USD :</span>
                            <span class="font-bold text-green-600" id="successMontantTotalUSD">0.00 USD</span>
                        </div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-red-800">Total FC :</span>
                            <span class="font-bold text-red-600" id="successMontantTotalFC">0 FC</span>
                        </div>
                    </div>
                </div>

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
        // Données initiales
        const nomClient = '<?= addslashes($nomClient) ?>';
        const produits = <?= json_encode($produits) ?>;
        const TAUX_CHANGE = 2500; // 1 USD = 2500 FC (taux approximatif)
        
        // Fonction pour calculer les totaux par devise
        function calculerTotauxParDevise() {
            let totalProduitsUSD = 0;
            let totalProduitsFC = 0;
            let quantiteTotaleUSD = 0;
            let quantiteTotaleFC = 0;
            let montantTotalUSD = 0;
            let montantTotalFC = 0;
            let totalProduitsGeneraux = 0;
            let quantiteTotaleGenerale = 0;
            let devisesUtilisees = new Set();
            
            // Récupérer toutes les quantités des inputs
            document.querySelectorAll('.quantite-input').forEach((input, index) => {
                const quantite = parseInt(input.value) || 1;
                const prixUnitaire = parseFloat(input.getAttribute('data-prix')) || 0;
                const devise = input.getAttribute('data-devise') || 'USD';
                const sousTotal = quantite * prixUnitaire;
                
                // Ajouter à l'ensemble des devises utilisées
                devisesUtilisees.add(devise);
                
                // Mettre à jour le sous-total affiché
                const sousTotalElement = input.closest('.product-item').querySelector('.sous-total');
                if (sousTotalElement) {
                    sousTotalElement.textContent = sousTotal.toFixed(2).replace('.', ',') + ' ' + devise;
                    sousTotalElement.setAttribute('data-sous-total', sousTotal);
                }
                
                // Mettre à jour les totaux selon la devise
                if (devise === 'USD') {
                    totalProduitsUSD++;
                    quantiteTotaleUSD += quantite;
                    montantTotalUSD += sousTotal;
                } else if (devise === 'FC') {
                    totalProduitsFC++;
                    quantiteTotaleFC += quantite;
                    montantTotalFC += sousTotal;
                }
                
                // Totaux généraux
                totalProduitsGeneraux++;
                quantiteTotaleGenerale += quantite;
                
                // Mettre à jour le stock disponible dans l'affichage
                const stockMax = parseInt(input.getAttribute('data-stock')) || 0;
                const stockDisplay = input.closest('.product-item').querySelector('.stock-display');
                if (stockDisplay) {
                    stockDisplay.textContent = stockMax;
                }
            });
            
            // Mettre à jour l'interface pour USD
            document.getElementById('nombreProduitsUSD').textContent = totalProduitsUSD;
            document.getElementById('quantiteTotaleUSD').textContent = quantiteTotaleUSD;
            document.getElementById('montantTotalUSD').textContent = montantTotalUSD.toFixed(2).replace('.', ',') + ' USD';
            
            // Mettre à jour l'interface pour FC
            document.getElementById('nombreProduitsFC').textContent = totalProduitsFC;
            document.getElementById('quantiteTotaleFC').textContent = quantiteTotaleFC;
            document.getElementById('montantTotalFC').textContent = montantTotalFC.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FC';
            
            // Calculer l'équivalent USD du total FC
            const equivalentUSD = montantTotalFC / TAUX_CHANGE;
            document.getElementById('equivalantUSD').textContent = equivalentUSD.toFixed(2) + ' USD';
            
            // Mettre à jour les totaux généraux
            document.getElementById('nombreProduits').textContent = totalProduitsGeneraux;
            document.getElementById('totalProduitsGeneraux').textContent = totalProduitsGeneraux;
            document.getElementById('quantiteTotaleGenerale').textContent = quantiteTotaleGenerale;
            document.getElementById('nombreArticles').textContent = quantiteTotaleGenerale + ' article(s)';
            document.getElementById('devisesUtilisees').textContent = devisesUtilisees.size;
            
            // Mettre à jour la modale
            document.getElementById('modalNombreProduits').textContent = totalProduitsGeneraux;
            document.getElementById('modalProduitsCount').textContent = totalProduitsGeneraux;
            document.getElementById('modalQuantiteTotale').textContent = quantiteTotaleGenerale;
            document.getElementById('modalDevisesUtilisees').textContent = devisesUtilisees.size;
            
            // Mettre à jour les totaux par devise dans la modale
            document.getElementById('modalMontantTotalUSD').textContent = montantTotalUSD.toFixed(2).replace('.', ',') + ' USD';
            document.getElementById('modalProduitsUSD').textContent = totalProduitsUSD;
            document.getElementById('modalQuantiteUSD').textContent = quantiteTotaleUSD;
            
            document.getElementById('modalMontantTotalFC').textContent = montantTotalFC.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FC';
            document.getElementById('modalProduitsFC').textContent = totalProduitsFC;
            document.getElementById('modalQuantiteFC').textContent = quantiteTotaleFC;
            
            // Mettre à jour le modal de succès
            document.getElementById('successProduitsCount').textContent = totalProduitsGeneraux;
            document.getElementById('successMontantTotalUSD').textContent = montantTotalUSD.toFixed(2).replace('.', ',') + ' USD';
            document.getElementById('successMontantTotalFC').textContent = montantTotalFC.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FC';
            
            return {
                totalProduitsUSD,
                totalProduitsFC,
                quantiteTotaleUSD,
                quantiteTotaleFC,
                montantTotalUSD,
                montantTotalFC,
                totalProduitsGeneraux,
                quantiteTotaleGenerale,
                devisesUtilisees: Array.from(devisesUtilisees)
            };
        }
        
        // Gestion des boutons de quantité
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser les totaux
            calculerTotauxParDevise();
            
            // Événements pour les boutons de diminution
            document.querySelectorAll('.decrease-quantity').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.closest('.product-item').querySelector('.quantite-input');
                    let valeur = parseInt(input.value) || 1;
                    const min = parseInt(this.getAttribute('data-min')) || 1;
                    
                    if (valeur > min) {
                        input.value = valeur - 1;
                        input.dispatchEvent(new Event('change'));
                    } else {
                        // Animation de secousse si déjà au minimum
                        input.classList.add('shake');
                        setTimeout(() => input.classList.remove('shake'), 300);
                    }
                });
            });
            
            // Événements pour les boutons d'augmentation
            document.querySelectorAll('.increase-quantity').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.closest('.product-item').querySelector('.quantite-input');
                    let valeur = parseInt(input.value) || 1;
                    const max = parseInt(this.getAttribute('data-max')) || 999;
                    
                    if (valeur < max) {
                        input.value = valeur + 1;
                        input.dispatchEvent(new Event('change'));
                    } else {
                        // Animation de secousse si déjà au maximum
                        input.classList.add('shake');
                        setTimeout(() => input.classList.remove('shake'), 300);
                        
                        // Afficher un message
                        const productName = this.closest('.product-item').querySelector('h3').textContent;
                        showNotification(`Quantité maximum atteinte pour ${productName} (${max} disponibles)`, 'warning');
                    }
                });
            });
            
            // Événements pour les inputs de quantité
            document.querySelectorAll('.quantite-input').forEach(input => {
                input.addEventListener('change', function() {
                    let valeur = parseInt(this.value) || 1;
                    const min = parseInt(this.getAttribute('min')) || 1;
                    const max = parseInt(this.getAttribute('data-stock')) || 999;
                    
                    // Validation
                    if (valeur < min) {
                        this.value = min;
                    } else if (valeur > max) {
                        this.value = max;
                        showNotification(`Quantité limitée à ${max} unités (stock disponible)`, 'warning');
                    }
                    
                    // Mettre à jour les totaux par devise
                    calculerTotauxParDevise();
                    
                    // Animation de mise à jour
                    this.closest('.product-item').querySelector('.sous-total').classList.add('updated');
                    setTimeout(() => {
                        this.closest('.product-item').querySelector('.sous-total').classList.remove('updated');
                    }, 500);
                });
                
                input.addEventListener('input', function() {
                    // Empêcher les valeurs non numériques
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
                
                // Validation sur focus out
                input.addEventListener('blur', function() {
                    if (!this.value || this.value === '0') {
                        this.value = this.getAttribute('min') || 1;
                        this.dispatchEvent(new Event('change'));
                    }
                });
            });
        });
        
        // Fonction pour afficher les notifications
        function showNotification(message, type = 'info') {
            // Créer la notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-xl shadow-lg transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-green-100 border-green-400 text-green-800' :
                type === 'warning' ? 'bg-yellow-100 border-yellow-400 text-yellow-800' :
                type === 'error' ? 'bg-red-100 border-red-400 text-red-800' :
                'bg-blue-100 border-blue-400 text-blue-800'
            } border max-w-sm`;
            
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'error' ? 'times-circle' : 'info-circle'} text-lg"></i>
                    <div class="flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <button class="text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animation d'entrée
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
                notification.classList.add('translate-x-0');
            }, 10);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('translate-x-0');
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) notification.remove();
                    }, 300);
                }
            }, 5000);
        }
        
        // Gestion des modales
        const btnConfirmerCommande = document.getElementById('btnConfirmerCommande');
        const confirmModal = document.getElementById('confirmModal');
        const successModal = document.getElementById('successModal');
        const successText = document.getElementById('successText');
        const reservationForm = document.getElementById('reservationForm');

        btnConfirmerCommande.addEventListener('click', () => {
            // Mettre à jour les totaux avant d'afficher la modale
            calculerTotauxParDevise();
            
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

            // Validation côté client
            if (!telephone) {
                alert('Veuillez saisir votre numéro de téléphone');
                return;
            }

            if (!adresse) {
                alert('Veuillez saisir votre adresse de livraison');
                return;
            }

            // Récupérer les quantités mises à jour
            const produitsMisAJour = [];
            document.querySelectorAll('.product-item').forEach(item => {
                const produitId = item.getAttribute('data-id');
                const quantiteInput = item.querySelector('.quantite-input');
                const quantite = parseInt(quantiteInput.value) || 1;
                const prix = parseFloat(quantiteInput.getAttribute('data-prix')) || 0;
                const devise = quantiteInput.getAttribute('data-devise') || 'USD';
                
                produitsMisAJour.push({
                    id: produitId,
                    quantite: quantite,
                    prix: prix,
                    devise: devise
                });
            });

            // Désactiver le bouton pour éviter les doubles clics
            const confirmBtn = document.getElementById('ouiConfirmer');
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Traitement...</span>';

            try {
                const formData = new FormData();
                formData.append('confirmer_reservation', 'true');
                formData.append('telephone', telephone);
                formData.append('adresse', adresse);
                formData.append('produits', JSON.stringify(produitsMisAJour));

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

                    const totaux = calculerTotauxParDevise();
                    successText.innerHTML = `Votre commande de <strong>${totaux.totalProduitsGeneraux} produit(s)</strong> a été enregistrée avec succès.`;
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
            doc.text("Reçu de commande", 105, 35, { align: "center" });

            doc.setFontSize(12);
            doc.text(`Client : ${nomClient}`, 20, 50);
            doc.text(`Date : ${new Date().toLocaleDateString('fr-FR')}`, 20, 60);
            doc.text(`Téléphone : ${document.getElementById('telephone').value}`, 20, 70);
            doc.text(`Adresse : ${document.getElementById('adresse').value}`, 20, 80);

            // Détails des produits
            doc.text("Détails des produits :", 20, 95);
            
            let yPosition = 105;
            let totalUSD = 0;
            let totalFC = 0;
            
            document.querySelectorAll('.product-item').forEach((item, index) => {
                const nomElement = item.querySelector('h3');
                const quantiteElement = item.querySelector('.quantite-input');
                const prixElement = item.querySelector('.prix-unitaire');
                const sousTotalElement = item.querySelector('.sous-total');
                
                if (nomElement && quantiteElement && prixElement && sousTotalElement) {
                    const nom = nomElement.textContent;
                    const quantite = quantiteElement.value;
                    const prix = prixElement.getAttribute('data-prix');
                    const devise = prixElement.getAttribute('data-devise');
                    const sousTotal = parseFloat(sousTotalElement.getAttribute('data-sous-total'));
                    
                    // Ajouter au total selon la devise
                    if (devise === 'USD') {
                        totalUSD += sousTotal;
                    } else if (devise === 'FC') {
                        totalFC += sousTotal;
                    }
                    
                    const nomTronque = nom.length > 30 ? nom.substring(0, 30) + '...' : nom;
                    const ligne = `${index + 1}. ${nomTronque} - ${quantite} × ${parseFloat(prix).toFixed(2)} ${devise} = ${sousTotal.toFixed(2)} ${devise}`;
                    
                    if (yPosition > 270) {
                        doc.addPage();
                        yPosition = 20;
                    }
                    doc.text(ligne, 25, yPosition);
                    yPosition += 8;
                }
            });

            // Totaux par devise
            yPosition += 10;
            doc.setFontSize(12);
            doc.setTextColor(5, 150, 105); // Vert pour USD
            doc.text(`Total USD : ${totalUSD.toFixed(2)} USD`, 20, yPosition);
            
            yPosition += 8;
            doc.setTextColor(220, 38, 38); // Rouge pour FC
            doc.text(`Total FC : ${totalFC.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} FC`, 20, yPosition);
            
            // Instructions de paiement
            yPosition += 15;
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text("Instructions de paiement :", 20, yPosition);
            yPosition += 7;
            doc.text("AirtelMoney: +243 975 413 369", 20, yPosition);
            yPosition += 5;
            doc.text("OrangeMoney: +243 851 653 923", 20, yPosition);
            yPosition += 5;
            doc.text("M-Pesa: +243 839 049 583", 20, yPosition);
            yPosition += 5;
            doc.text("Nom: Patrick MULUMBA JEAN", 20, yPosition);

            // Pied de page
            yPosition += 10;
            doc.setFontSize(8);
            doc.text("Merci pour votre confiance !", 105, yPosition + 10, { align: "center" });

            // Nom du fichier avec le nom du client
            const dateStr = new Date().toISOString().split('T')[0];
            doc.save(`commande_${nomClient.replace(/[^a-z0-9]/gi, '_')}_${dateStr}.pdf`);
        });
    </script>

    <!-- Styles additionnels -->
    <style>
        .shake {
            animation: shake 0.3s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .updated {
            animation: highlight 0.5s ease-in-out;
        }
        
        @keyframes highlight {
            0% { background-color: transparent; }
            50% { background-color: rgba(34, 197, 94, 0.2); }
            100% { background-color: transparent; }
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

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