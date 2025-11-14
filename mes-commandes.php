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
    <title>Mes commandes - Johnson Jr Construction</title>
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
                    <?php 
                    // Obtenir le chemin correct de l'image
                    $imagePath = getImagePath($commande['produit_image']);
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border hover:shadow-md transition duration-200 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                                
                                <!-- Product Info -->
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 border">
                                        <img src="<?= $imagePath ?>" 
                                             alt="<?= htmlspecialchars($commande['produit_nom']) ?>" 
                                             class="w-full h-full object-cover"
                                             onerror="this.onerror=null; this.src='admin/uploads/default.jpg';">
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-1 truncate">
                                            <?= htmlspecialchars($commande['produit_nom']) ?>
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
                                            <?= htmlspecialchars($commande['devise']) ?>
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
                                    <button onclick="showDetails(<?= htmlspecialchars(json_encode($commande)) ?>, '<?= $imagePath ?>')" 
                                            class="text-blue-600 hover:text-blue-800 transition duration-200 flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-blue-50">
                                        <i class="fas fa-eye"></i>
                                        <span>Détails</span>
                                    </button>
                                    <button onclick="downloadReceipt(<?= htmlspecialchars(json_encode($commande)) ?>)" 
                                            class="text-green-600 hover:text-green-800 transition duration-200 flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-green-50">
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

    <!-- Modal Détails -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl transform transition-all duration-300 scale-95 max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-800">Détails de la commande</h2>
                    <button onclick="closeDetails()" class="text-gray-400 hover:text-gray-600 transition duration-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div id="detailsContent">
                    <!-- Le contenu sera injecté ici par JavaScript -->
                </div>
            </div>
            
            <div class="p-6 border-t bg-gray-50">
                <div class="flex justify-end">
                    <button onclick="closeDetails()" class="bg-[#811313] hover:bg-[#053d36] text-white px-6 py-2 rounded-lg transition duration-200 flex items-center space-x-2">
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
            
            // Créer le contenu des détails
            content.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Informations produit -->
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-24 h-24 rounded-xl overflow-hidden border flex-shrink-0">
                                <img src="${imagePath}" alt="${commande.produit_nom}" 
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null; this.src='admin/uploads/default.jpg';">
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">${commande.produit_nom}</h3>
                                <p class="text-gray-600">${commande.produit_description || 'Aucune description disponible'}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-2xl">
                                    <p class="text-sm text-blue-600 font-medium">Quantité</p>
                                    <p class="text-lg font-bold text-gray-800">${commande.quantite}</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-2xl">
                                    <p class="text-sm text-green-600 font-medium">Poids</p>
                                    <p class="text-lg font-bold text-gray-800">${commande.produit_poids || 'Non spécifié'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informations commande -->
                    <div class="space-y-6">
                        <div class="bg-gray-50 p-6 rounded-2xl">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Informations de la commande</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">ID Commande:</span>
                                    <span class="font-semibold">#${commande.reservation_id}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-semibold">${formattedDate}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Statut:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(commande.statut)}">
                                        <i class="${getStatusIcon(commande.statut)} mr-2"></i>
                                        ${getStatusText(commande.statut)}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Montant total:</span>
                                    <span class="text-xl font-bold text-green-600">
                                        ${parseFloat(commande.montant_total).toFixed(2).replace('.', ',')} ${commande.devise}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations livraison -->
                        <div class="bg-orange-50 p-6 rounded-2xl">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Informations de livraison</h4>
                            <div class="space-y-3">
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-phone text-orange-600 mt-1"></i>
                                    <div>
                                        <p class="text-sm text-gray-600">Téléphone</p>
                                        <p class="font-semibold text-gray-800">${commande.telephone}</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-map-marker-alt text-orange-600 mt-1"></i>
                                    <div>
                                        <p class="text-sm text-gray-600">Adresse</p>
                                        <p class="font-semibold text-gray-800">${commande.adresse}</p>
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
        }
        
        // Fonction pour fermer les détails
        function closeDetails() {
            const modal = document.getElementById('detailsModal');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
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
    </script>

</body>
</html>