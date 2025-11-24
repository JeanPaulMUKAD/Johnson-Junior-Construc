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
               r.telephone, r.adresse,
               p.nom AS produit_nom, p.image AS produit_image, p.devise AS devise, 
               p.description AS produit_description, p.poids AS produit_poids
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

// Fonction pour obtenir le chemin correct de l'image
function getImagePath($imageProduit) {
    if (empty($imageProduit)) {
        return "admin/uploads/default.jpg";
    }
    
    if (strpos($imageProduit, 'uploads/') === 0) {
        $imagePath = "admin/" . $imageProduit;
    } else {
        $imagePath = "admin/uploads/" . $imageProduit;
    }
    
    // Vérifier si le fichier existe
    if (!file_exists($imagePath)) {
        return "admin/uploads/default.jpg";
    }
    
    return $imagePath;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes - Johnson Jr Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media (max-width: 640px) {
            .mobile-card {
                padding: 1rem;
            }
            .mobile-stack {
                flex-direction: column;
                align-items: stretch;
            }
            .mobile-text {
                font-size: 0.875rem;
            }
            .mobile-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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

    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4 md:py-6">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-white text-sm md:text-lg"></i>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Mes Commandes</h1>
                </div>
                <a href="index.php" class="bg-[#811313] hover:bg-[#053d36] text-white px-4 py-2 md:px-6 md:py-2 rounded-lg transition duration-200 flex items-center space-x-2 text-sm md:text-base">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden sm:inline">Retour</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8 py-6 md:py-8">
        <!-- Stats Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm border p-4 md:p-6 animate-fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm font-medium text-gray-500">Total des commandes</p>
                        <p class="text-xl md:text-2xl font-bold text-gray-800 mt-1"><?= count($commandes) ?></p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg md:rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-blue-600 text-base md:text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm border p-4 md:p-6 animate-fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm font-medium text-gray-500">En attente</p>
                        <p class="text-xl md:text-2xl font-bold text-yellow-600 mt-1">
                            <?= count(array_filter($commandes, fn($c) => $c['statut'] === 'en_attente')) ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg md:rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-base md:text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl md:rounded-2xl shadow-sm border p-4 md:p-6 animate-fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm font-medium text-gray-500">Confirmées</p>
                        <p class="text-xl md:text-2xl font-bold text-green-600 mt-1">
                            <?= count(array_filter($commandes, fn($c) => $c['statut'] === 'confirmée')) ?>
                        </p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg md:rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-base md:text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commandes List -->
        <?php if (empty($commandes)): ?>
            <div class="text-center py-12 md:py-16">
                <div class="w-16 h-16 md:w-24 md:h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6">
                    <i class="fas fa-box-open text-gray-400 text-2xl md:text-3xl"></i>
                </div>
                <h3 class="text-lg md:text-xl font-semibold text-gray-600 mb-2">Aucune commande</h3>
                <p class="text-gray-500 mb-6 text-sm md:text-base">Vous n'avez pas encore passé de commande.</p>
                <a href="index.php" class="bg-[#811313] hover:bg-[#053d36] text-white px-6 py-3 md:px-8 md:py-3 rounded-lg transition duration-200 inline-flex items-center space-x-2 text-sm md:text-base">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Découvrir nos produits</span>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4 md:space-y-6">
                <?php foreach ($commandes as $commande): ?>
                    <?php 
                    // Obtenir le chemin correct de l'image
                    $imagePath = getImagePath($commande['produit_image']);
                    ?>
                    <div class="bg-white rounded-xl md:rounded-2xl shadow-sm border hover:shadow-md transition duration-200 overflow-hidden animate-fade-in">
                        <div class="p-4 md:p-6 mobile-card">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 md:gap-6 mobile-stack">
                                
                                <!-- Product Info -->
                                <div class="flex items-start space-x-3 md:space-x-4 flex-1 min-w-0">
                                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-lg md:rounded-xl overflow-hidden flex-shrink-0 border">
                                        <img src="<?= $imagePath ?>" 
                                             alt="<?= htmlspecialchars($commande['produit_nom']) ?>" 
                                             class="w-full h-full object-cover"
                                             onerror="this.onerror=null; this.src='admin/uploads/default.jpg';">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base md:text-lg font-semibold text-gray-800 mb-1 truncate">
                                            <?= htmlspecialchars($commande['produit_nom']) ?>
                                        </h3>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 md:gap-4 text-xs md:text-sm text-gray-600 mobile-grid">
                                            <div class="flex items-center space-x-1 md:space-x-2">
                                                <i class="fas fa-hashtag text-gray-400 text-xs"></i>
                                                <span>Quantité : <strong><?= $commande['quantite'] ?></strong></span>
                                            </div>
                                            <div class="flex items-center space-x-1 md:space-x-2">
                                                <i class="fas fa-calendar text-gray-400 text-xs"></i>
                                                <span><?= date('d/m/Y H:i', strtotime($commande['date_reservation'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status & Amount -->
                                <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row items-start lg:items-end gap-3 md:gap-4 mobile-stack">
                                    <!-- Amount -->
                                    <div class="text-left sm:text-right">
                                        <p class="text-xs md:text-sm text-gray-500 mb-1">Montant total</p>
                                        <p class="text-lg md:text-xl font-bold text-green-600">
                                            <?php 
                                            $montant = floatval($commande['montant_total']);
                                            echo number_format($montant, 2, ',', ' ');
                                            ?> 
                                            <?= htmlspecialchars($commande['devise']) ?>
                                        </p>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="flex flex-col items-start sm:items-end">
                                        <p class="text-xs md:text-sm text-gray-500 mb-1">Statut</p>
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
                                        <div class="status-badge <?= $statusConfig['color'] ?> border">
                                            <i class="<?= $statusConfig['icon'] ?> text-xs"></i>
                                            <span class="mobile-text"><?= ucfirst(str_replace('_', ' ', $commande['statut'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                                <div class="text-xs md:text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ID Commande : #<?= $commande['reservation_id'] ?>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button onclick="showDetails(<?= htmlspecialchars(json_encode($commande)) ?>, '<?= $imagePath ?>')" 
                                            class="text-blue-600 hover:text-blue-800 transition duration-200 flex items-center space-x-1 md:space-x-2 px-2 md:px-3 py-1 md:py-2 rounded-lg hover:bg-blue-50 text-xs md:text-sm">
                                        <i class="fas fa-eye text-xs"></i>
                                        <span>Détails</span>
                                    </button>
                                    <button onclick="downloadReceipt(<?= htmlspecialchars(json_encode($commande)) ?>)" 
                                            class="text-green-600 hover:text-green-800 transition duration-200 flex items-center space-x-1 md:space-x-2 px-2 md:px-3 py-1 md:py-2 rounded-lg hover:bg-green-50 text-xs md:text-sm">
                                        <i class="fas fa-download text-xs"></i>
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

    <!-- Modal Détails -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-2 sm:p-4">
        <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl w-full max-w-2xl transform transition-all duration-300 scale-95 max-h-[95vh] md:max-h-[90vh] overflow-hidden mx-2">
            <div class="p-4 md:p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg md:text-2xl font-bold text-gray-800">Détails de la commande</h2>
                    <button onclick="closeDetails()" class="text-gray-400 hover:text-gray-600 transition duration-200">
                        <i class="fas fa-times text-lg md:text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-120px)] md:max-h-[calc(90vh-120px)]">
                <div id="detailsContent">
                    <!-- Le contenu sera injecté ici par JavaScript -->
                </div>
            </div>
            
            <div class="p-4 md:p-6 border-t bg-gray-50">
                <div class="flex justify-end">
                    <button onclick="closeDetails()" class="bg-[#811313] hover:bg-[#053d36] text-white px-4 py-2 md:px-6 md:py-2 rounded-lg transition duration-200 flex items-center space-x-2 text-sm md:text-base">
                        <i class="fas fa-times"></i>
                        <span>Fermer</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour afficher les détails
        function showDetails(commande, imagePath) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            // Formater la date
            const date = new Date(commande.date_reservation);
            const formattedDate = date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Créer le contenu des détails adapté mobile
            content.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8">
                    <!-- Informations produit -->
                    <div class="space-y-4 md:space-y-6">
                        <div class="flex items-start space-x-3 md:space-x-4">
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-lg md:rounded-xl overflow-hidden border flex-shrink-0">
                                <img src="${imagePath}" alt="${commande.produit_nom}" 
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null; this.src='admin/uploads/default.jpg';">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base md:text-xl font-bold text-gray-800 mb-1 md:mb-2 break-words">${commande.produit_nom}</h3>
                                <p class="text-gray-600 text-sm md:text-base">${commande.produit_description || 'Aucune description disponible'}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 md:space-y-4">
                            <div class="grid grid-cols-2 gap-3 md:gap-4">
                                <div class="bg-blue-50 p-3 md:p-4 rounded-xl md:rounded-2xl">
                                    <p class="text-xs md:text-sm text-blue-600 font-medium">Quantité</p>
                                    <p class="text-base md:text-lg font-bold text-gray-800">${commande.quantite}</p>
                                </div>
                                <div class="bg-green-50 p-3 md:p-4 rounded-xl md:rounded-2xl">
                                    <p class="text-xs md:text-sm text-green-600 font-medium">Poids</p>
                                    <p class="text-base md:text-lg font-bold text-gray-800">${commande.produit_poids || 'Non spécifié'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations commande -->
                    <div class="space-y-4 md:space-y-6">
                        <div class="bg-gray-50 p-4 md:p-6 rounded-xl md:rounded-2xl">
                            <h4 class="text-base md:text-lg font-semibold text-gray-800 mb-3 md:mb-4">Informations de la commande</h4>
                            <div class="space-y-2 md:space-y-3 text-sm md:text-base">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">ID Commande:</span>
                                    <span class="font-semibold">#${commande.reservation_id}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-semibold text-xs md:text-sm">${formattedDate}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Statut:</span>
                                    <span class="inline-flex items-center px-2 py-1 md:px-3 md:py-1 rounded-full text-xs md:text-sm font-medium ${getStatusColor(commande.statut)}">
                                        <i class="${getStatusIcon(commande.statut)} mr-1 md:mr-2 text-xs"></i>
                                        ${getStatusText(commande.statut)}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Montant total:</span>
                                    <span class="text-base md:text-xl font-bold text-green-600">
                                        ${parseFloat(commande.montant_total).toFixed(2).replace('.', ',')} ${commande.devise}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations livraison -->
                        <div class="bg-orange-50 p-4 md:p-6 rounded-xl md:rounded-2xl">
                            <h4 class="text-base md:text-lg font-semibold text-gray-800 mb-3 md:mb-4">Informations de livraison</h4>
                            <div class="space-y-2 md:space-y-3">
                                <div class="flex items-start space-x-2 md:space-x-3">
                                    <i class="fas fa-phone text-orange-600 mt-0.5 text-sm md:text-base"></i>
                                    <div>
                                        <p class="text-xs md:text-sm text-gray-600">Téléphone</p>
                                        <p class="font-semibold text-gray-800 text-sm md:text-base">${commande.telephone}</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-2 md:space-x-3">
                                    <i class="fas fa-map-marker-alt text-orange-600 mt-0.5 text-sm md:text-base"></i>
                                    <div>
                                        <p class="text-xs md:text-sm text-gray-600">Adresse</p>
                                        <p class="font-semibold text-gray-800 text-sm md:text-base break-words">${commande.adresse}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Afficher la modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
            
            // Empêcher le scroll du body
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour fermer les détails
        function closeDetails() {
            const modal = document.getElementById('detailsModal');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                // Restaurer le scroll du body
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        // Fonctions utilitaires pour le statut
        function getStatusColor(status) {
            const colors = {
                'en_attente': 'bg-yellow-100 text-yellow-800',
                'confirmée': 'bg-green-100 text-green-800',
                'annulée': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }
        
        function getStatusIcon(status) {
            const icons = {
                'en_attente': 'fas fa-clock',
                'confirmée': 'fas fa-check-circle',
                'annulée': 'fas fa-times-circle'
            };
            return icons[status] || 'fas fa-question-circle';
        }
        
        function getStatusText(status) {
            return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
        }
        
        // Fonction pour télécharger le reçu
        function downloadReceipt(commande) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // En-tête
            doc.setFontSize(20);
            doc.setTextColor(30, 64, 175);
            doc.text("Johnson Jr Construction", 105, 20, { align: "center" });
            
            doc.setFontSize(16);
            doc.setTextColor(0, 0, 0);
            doc.text("Reçu de commande", 105, 35, { align: "center" });
            
            // Informations commande
            doc.setFontSize(12);
            doc.text(`ID Commande: #${commande.reservation_id}`, 20, 55);
            doc.text(`Date: ${new Date(commande.date_reservation).toLocaleDateString('fr-FR')}`, 20, 65);
            doc.text(`Statut: ${getStatusText(commande.statut)}`, 20, 75);
            
            // Informations produit
            doc.text(`Produit: ${commande.produit_nom}`, 20, 90);
            doc.text(`Quantité: ${commande.quantite}`, 20, 100);
            doc.text(`Prix unitaire: ${(parseFloat(commande.montant_total) / parseInt(commande.quantite)).toFixed(2)} ${commande.devise}`, 20, 110);
            doc.text(`Montant total: ${parseFloat(commande.montant_total).toFixed(2)} ${commande.devise}`, 20, 120);
            
            // Informations livraison
            doc.text(`Téléphone: ${commande.telephone}`, 20, 140);
            doc.text(`Adresse: ${commande.adresse}`, 20, 150);
            
            doc.save("reçu_commande_" + commande.reservation_id + ".pdf");
        }
        
        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetails();
            }
        });
        
        // Fermer avec la touche Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetails();
            }
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