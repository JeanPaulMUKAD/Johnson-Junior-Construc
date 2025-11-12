<?php declare(strict_types=1);
session_start();
require_once "../configs/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Gestion de la suppression
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $conn->query("DELETE FROM reservations WHERE id=$id");
    header("Location: reservations.php?deleted=1");
    exit();
}

// REQUÊTE CORRIGÉE : Ajout de telephone et adresse
$result = $conn->query("
        SELECT r.*, u.nom AS client_nom, p.nom AS produit_nom, p.devise AS devise
        FROM reservations r
        JOIN utilisateurs u ON r.utilisateur_id = u.id
        JOIN produits p ON r.produit_id = p.id
        ORDER BY r.id ASC
    ");
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Réservations - Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="flex bg-gray-100" style="font-family: 'Arial', sans-serif;">
    <?php include "includes/sidebar.php"; ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold mb-6 text-blue-800 flex items-center gap-3">
            <i class="fas fa-list-alt"></i>
            Liste des Réservations Clients
        </h1>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm min-w-[1000px]">
                    <thead class="bg-blue-100 text-blue-800">
                        <tr>
                            <th class="p-3 border text-left">ID</th>
                            <th class="p-3 border text-left">Client</th>
                            <th class="p-3 border text-left">Produit</th>
                            <th class="p-3 border text-left">Quantité</th>
                            <th class="p-3 border text-left">Montant Total</th>
                            <th class="p-3 border text-left">Téléphone</th>
                            <th class="p-3 border text-left">Adresse</th>
                            <th class="p-3 border text-left">Date</th>
                            <th class="p-3 border text-left">Statut</th>
                            <th class="p-3 border text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($r = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="p-3 text-gray-700 font-medium border"><?= $r['id'] ?></td>
                                <td class="p-3 text-gray-800 border"><?= htmlspecialchars($r['client_nom']) ?></td>
                                <td class="p-3 text-gray-800 border"><?= htmlspecialchars($r['produit_nom']) ?></td>
                                <td class="p-3 text-gray-600 border text-center"><?= $r['quantite'] ?></td>
                                <td class="p-3 text-green-600 font-semibold border">
                                    <?php
                                    $montant = floatval($r['montant_total']);
                                    echo number_format($montant, 2, ',', ' ');
                                    ?>
                                    <?= $r['devise'] ?>
                                </td>

                                <!-- Champ Téléphone -->
                                <td class="p-3 text-gray-700 border">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-phone text-blue-500 text-sm"></i>
                                        <span
                                            class="font-mono text-sm"><?= htmlspecialchars($r['telephone'] ?? 'Non renseigné') ?></span>
                                    </div>
                                </td>

                                <!-- Champ Adresse -->
                                <td class="p-3 text-gray-700 border">
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-red-500 text-sm mt-1"></i>
                                        <span class="text-sm max-w-[200px] truncate"
                                            title="<?= htmlspecialchars($r['adresse'] ?? 'Non renseignée') ?>">
                                            <?= htmlspecialchars($r['adresse'] ?? 'Non renseignée') ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="p-3 text-gray-500 border">
                                    <?= date('d/m/Y H:i', strtotime($r['date_reservation'])) ?></td>

                                <!-- Statut coloré -->
                                <td class="p-3 border">
                                    <?php
                                    $statut = strtolower($r['statut']);
                                    $color = match ($statut) {
                                        'confirmée', 'confirmé' => 'bg-green-100 text-green-700 border border-green-300',
                                        'en attente' => 'bg-yellow-100 text-yellow-700 border border-yellow-300',
                                        'annulée', 'annulé' => 'bg-red-100 text-red-700 border border-red-300',
                                        default => 'bg-gray-100 text-gray-700 border border-gray-300'
                                    };
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $color ?>">
                                        <?= ucfirst($r['statut']) ?>
                                    </span>
                                </td>

                                <!-- Boutons d'action -->
                                <td class="p-3 border">
                                    <div class="flex items-center gap-2">
                                        <button
                                            onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['client_nom'])) ?>', '<?= htmlspecialchars(addslashes($r['produit_nom'])) ?>')"
                                            class="text-red-600 hover:text-red-800 transition duration-200 p-2 rounded-lg hover:bg-red-50 group relative"
                                            title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                            <!-- Tooltip -->
                                            <span
                                                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition duration-200 whitespace-nowrap z-10">
                                                Supprimer la réservation
                                            </span>
                                        </button>
                                        <!-- Bouton pour voir les détails complets -->
                                        <button onclick="showReservationDetails(<?= htmlspecialchars(json_encode($r)) ?>)"
                                            class="text-green-600 hover:text-green-800 transition duration-200 p-2 rounded-lg hover:bg-green-50 group relative"
                                            title="Voir les détails">
                                            <i class="fa-solid fa-eye"></i>
                                            <!-- Tooltip -->
                                            <span
                                                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition duration-200 whitespace-nowrap z-10">
                                                Voir les détails
                                            </span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Message de suppression stylisé -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative shadow-md"
                role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong class="font-bold">Succès ! </strong>
                    <span class="block sm:inline ml-1">La réservation a été supprimée avec succès.</span>
                </div>
                <button class="absolute top-3 right-3 text-green-500 hover:text-green-700"
                    onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <div class="p-6">
                <!-- Icone d'alerte -->
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                
                <!-- Titre -->
                <h3 class="text-xl font-bold text-gray-800 text-center mb-2">Confirmer la suppression</h3>
                
                <!-- Message personnalisé -->
                <p class="text-gray-600 text-center mb-6">
                    Êtes-vous sûr de vouloir supprimer la réservation de 
                    <span id="clientName" class="font-semibold text-blue-600"></span> 
                    pour le produit 
                    <span id="productName" class="font-semibold text-green-600"></span> ?
                </p>
                
                <p class="text-sm text-red-600 text-center mb-6 bg-red-50 py-2 px-3 rounded-lg border border-red-200">
                    <i class="fas fa-info-circle mr-1"></i>
                    Cette action est irréversible.
                </p>
                
                <!-- Boutons d'action -->
                <div class="flex justify-center space-x-3">
                    <button id="cancelDelete" 
                            class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition duration-200 flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Annuler</span>
                    </button>
                    <button id="confirmDeleteBtn" 
                            class="px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition duration-200 flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-trash"></i>
                        <span>Supprimer</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher les détails complets -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Détails de la réservation</h3>
                    <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <label class="text-sm text-blue-600 font-semibold">ID Réservation</label>
                            <p id="detail-id" class="text-lg font-bold text-gray-800"></p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <label class="text-sm text-green-600 font-semibold">Client</label>
                            <p id="detail-client" class="text-lg font-bold text-gray-800"></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="text-sm text-gray-600 font-semibold">Produit</label>
                        <p id="detail-produit" class="text-lg font-bold text-gray-800"></p>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <label class="text-sm text-yellow-600 font-semibold">Quantité</label>
                            <p id="detail-quantite" class="text-2xl font-bold text-gray-800 text-center"></p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <label class="text-sm text-green-600 font-semibold">Montant Total</label>
                            <p id="detail-montant" class="text-2xl font-bold text-green-600 text-center"></p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <label class="text-sm text-purple-600 font-semibold">Statut</label>
                            <p id="detail-statut" class="text-lg font-bold text-center"></p>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg">
                        <label class="text-sm text-blue-600 font-semibold flex items-center gap-2">
                            <i class="fas fa-phone"></i>Téléphone
                        </label>
                        <p id="detail-telephone" class="text-lg font-mono text-gray-800"></p>
                    </div>

                    <div class="bg-red-50 p-4 rounded-lg">
                        <label class="text-sm text-red-600 font-semibold flex items-center gap-2">
                            <i class="fas fa-map-marker-alt"></i>Adresse de livraison
                        </label>
                        <p id="detail-adresse" class="text-lg text-gray-800 whitespace-pre-wrap"></p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="text-sm text-gray-600 font-semibold">Date de réservation</label>
                        <p id="detail-date" class="text-lg text-gray-800"></p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="closeDetailsModal()"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentReservationId = null;

        // Fonction pour afficher la modal de confirmation de suppression
        function confirmDelete(reservationId, clientName, productName) {
            currentReservationId = reservationId;
            
            const modal = document.getElementById('deleteConfirmModal');
            const clientNameElement = document.getElementById('clientName');
            const productNameElement = document.getElementById('productName');
            
            // Mettre à jour les informations
            clientNameElement.textContent = clientName;
            productNameElement.textContent = productName;
            
            // Afficher la modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Animation d'entrée
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
        }

        // Gestion de la confirmation de suppression
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (currentReservationId) {
                // Redirection vers la page de suppression
                window.location.href = `?supprimer=${currentReservationId}`;
            }
        });

        // Gestion de l'annulation
        document.getElementById('cancelDelete').addEventListener('click', function() {
            const modal = document.getElementById('deleteConfirmModal');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                currentReservationId = null;
            }, 300);
        });

        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                document.getElementById('cancelDelete').click();
            }
        });

        // Empêcher la fermeture en cliquant à l'intérieur de la modal
        document.querySelector('#deleteConfirmModal > div').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Fonction pour afficher les détails d'une réservation
        function showReservationDetails(reservation) {
            const modal = document.getElementById('detailsModal');
            const statusColors = {
                'confirmée': 'text-green-600 bg-green-100 px-3 py-1 rounded-full',
                'confirmé': 'text-green-600 bg-green-100 px-3 py-1 rounded-full',
                'en attente': 'text-yellow-600 bg-yellow-100 px-3 py-1 rounded-full',
                'annulée': 'text-red-600 bg-red-100 px-3 py-1 rounded-full',
                'annulé': 'text-red-600 bg-red-100 px-3 py-1 rounded-full'
            };

            // Remplir les détails
            document.getElementById('detail-id').textContent = reservation.id;
            document.getElementById('detail-client').textContent = reservation.client_nom;
            document.getElementById('detail-produit').textContent = reservation.produit_nom;
            document.getElementById('detail-quantite').textContent = reservation.quantite;
            document.getElementById('detail-montant').textContent =
                parseFloat(reservation.montant_total).toFixed(2) + ' ' + reservation.devise;
            document.getElementById('detail-telephone').textContent = reservation.telephone || 'Non renseigné';
            document.getElementById('detail-adresse').textContent = reservation.adresse || 'Non renseignée';
            document.getElementById('detail-date').textContent = new Date(reservation.date_reservation).toLocaleString('fr-FR');

            // Gérer le statut avec couleur
            const statutElement = document.getElementById('detail-statut');
            const statut = reservation.statut.toLowerCase();
            statutElement.textContent = reservation.statut;
            statutElement.className = statusColors[statut] || 'text-gray-600 bg-gray-100 px-3 py-1 rounded-full';

            // Afficher la modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Fonction pour fermer la modal
        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('detailsModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeDetailsModal();
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
        anime = setInterval(() => {
            angle += 10;
            cercle.style.transform = `translate(-50%, -50%) rotate(${angle}deg)`;
        }, 20);

        setTimeout(() => {
            clearInterval(anime);
            masque.style.opacity = '0';
        }, 1000);

        setTimeout(() => {
            masque.style.visibility = 'hidden';
        }, 1500);
    });

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

    cercle.style.width = '40px';
    cercle.style.height = '40px';
    cercle.style.border = '2px solid #f3f3f3';
    cercle.style.borderTop = '2px solid #2F1C6A';
    cercle.style.borderRadius = '50%';
    cercle.style.position = 'absolute';
    cercle.style.top = '50%';
    cercle.style.left = '50%';
    cercle.style.transform = 'translate(-50%, -50%)';
    cercle.style.boxSizing = 'border-box';
    cercle.style.zIndex = '1';
    masque.appendChild(cercle);

    let anime;
    </script>
</body>
</html>