<?php declare(strict_types=1); 
session_start();
include 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = getConnection();

    // Récupérer toutes les commandes de l'utilisateur avec les informations produit ET la devise
    $stmt = $conn->prepare("
        SELECT r.id AS reservation_id, r.quantite, r.montant_total, r.statut, r.date_reservation,
               p.nom AS produit_nom, p.image AS produit_image, p.devise AS devise
        FROM reservations r
        INNER JOIN produits p ON r.produit_id = p.id
        WHERE r.utilisateur_id = :user_id
        ORDER BY r.date_reservation ASC
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
    <title>Mes commandes - Johnson Jr Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3730a3',
                        accent: '#7e22ce'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen" style="font-family: 'Arial', sans-serif;">

    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-white text-lg"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Mes Commandes</h1>
                </div>
                <a href="index.php" class="bg-[#811313] hover:bg-[#053d36] text-white px-6 py-2 rounded-lg transition duration-200 flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retour</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total des commandes</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= count($commandes) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">En attente</p>
                        <p class="text-2xl font-bold text-yellow-600 mt-1">
                            <?= count(array_filter($commandes, fn($c) => $c['statut'] === 'en_attente')) ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Confirmées</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">
                            <?= count(array_filter($commandes, fn($c) => $c['statut'] === 'confirmée')) ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commandes List -->
        <?php if (empty($commandes)): ?>
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucune commande</h3>
                <p class="text-gray-500 mb-6">Vous n'avez pas encore passé de commande.</p>
                <a href="index.php" class="bg-[#811313] hover:bg-[#053d36] text-white px-8 py-3 rounded-lg transition duration-200 inline-flex items-center space-x-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Découvrir nos produits</span>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($commandes as $commande): ?>
                    <div class="bg-white rounded-2xl shadow-sm border hover:shadow-md transition duration-200 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                                
                                <!-- Product Info -->
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 border">
                                        <img src="<?= $commande['produit_image'] ?>" alt="<?= $commande['produit_nom'] ?>" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-1 truncate">
                                            <?= $commande['produit_nom'] ?>
                                        </h3>
                                        
                                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-hashtag text-gray-400"></i>
                                                <span>Quantité : <strong><?= $commande['quantite'] ?></strong></span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-calendar text-gray-400"></i>
                                                <span><?= date('d/m/Y H:i', strtotime($commande['date_reservation'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status & Amount -->
                                <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row items-start lg:items-end gap-4">
                                    <!-- Amount -->
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500 mb-1">Montant total</p>
                                        <p class="text-xl font-bold text-green-600">
                                            <?php 
                                            $montant = floatval($commande['montant_total']);
                                            echo number_format($montant, 2, ',', ' ');
                                            ?> 
                                            <?= $commande['devise'] ?>
                                        </p>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="flex flex-col items-end">
                                        <p class="text-sm text-gray-500 mb-1">Statut</p>
                                        <?php
                                            $statusConfig = match($commande['statut']) {
                                                'en_attente' => [
                                                    'color' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                    'icon' => 'fas fa-clock'
                                                ],
                                                'confirmée' => [
                                                    'color' => 'bg-green-100 text-green-800 border-green-200',
                                                    'icon' => 'fas fa-check-circle'
                                                ],
                                                'annulée' => [
                                                    'color' => 'bg-red-100 text-red-800 border-red-200',
                                                    'icon' => 'fas fa-times-circle'
                                                ],
                                                default => [
                                                    'color' => 'bg-gray-100 text-gray-800 border-gray-200',
                                                    'icon' => 'fas fa-question-circle'
                                                ]
                                            };
                                        ?>
                                        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full border text-sm font-medium <?= $statusConfig['color'] ?>">
                                            <i class="<?= $statusConfig['icon'] ?> text-xs"></i>
                                            <span><?= ucfirst(str_replace('_', ' ', $commande['statut'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-gray-50 px-6 py-4 border-t">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ID Commande : #<?= $commande['reservation_id'] ?>
                                </div>
                                <div class="flex space-x-3">
                                    <button class="text-blue-600 hover:text-blue-800 transition duration-200 flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-blue-50">
                                        <i class="fas fa-eye"></i>
                                        <span>Détails</span>
                                    </button>
                                    <button class="text-green-600 hover:text-green-800 transition duration-200 flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-green-50">
                                        <i class="fas fa-download"></i>
                                        <span>Télécharger</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

   

    <!--=================== Search JS ====================-->
    <script src="assets/js/search.js"></script>

</body>
</html>