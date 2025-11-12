<?php declare(strict_types=1); 
session_start();
require_once "../configs/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// ✅ Gestion activation / désactivation
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);

    // Récupérer le statut actuel
    $stmt = $conn->prepare("SELECT statut FROM utilisateurs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        // Alterner le statut
        $newStatus = ($result['statut'] === 'actif') ? 'desactive' : 'actif';

        $stmt2 = $conn->prepare("UPDATE utilisateurs SET statut = ? WHERE id = ?");
        $stmt2->bind_param("si", $newStatus, $id);
        $stmt2->execute();

        $_SESSION['message'] = ($newStatus === 'actif')
            ? "✅ Client réactivé avec succès."
            : "✅ Client désactivé avec succès.";

        header("Location: clients.php");
        exit();
    }
}

// Message de confirmation
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
unset($_SESSION['message']);

// Récupérer tous les clients
$result = $conn->query("SELECT * FROM utilisateurs WHERE role = 'client' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Clients - Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a2e0e6ad4c.js" crossorigin="anonymous"></script>
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

<body class="flex bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen font-sans">
    <?php include "includes/sidebar.php"; ?>

    <div class="flex-1 p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <span>Gestion des Clients</span>
                </h1>
                <p class="text-gray-600 mt-2">Gérez l'activation et la désactivation des comptes clients</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-white rounded-2xl shadow-sm px-4 py-2 border border-gray-200">
                    <p class="text-sm text-gray-500">Total clients</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $result->num_rows ?></p>
                </div>
            </div>
        </div>

        <!-- Message de confirmation -->
        <?php if ($message): ?>
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-4 rounded-2xl shadow-lg mb-6 flex items-center gap-3 animate-fade-in">
                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check"></i>
                </div>
                <span class="font-medium"><?= $message ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-white hover:text-gray-200 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Tableau des clients -->
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Liste des clients</h2>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" placeholder="Rechercher un client..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                        <tr>
                            <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-hashtag"></i>
                                    ID
                                </div>
                            </th>
                            <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user"></i>
                                    Client
                                </div>
                            </th>
                            <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </div>
                            </th>
                            <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar"></i>
                                    Inscription
                                </div>
                            </th>
                            <th class="p-4 text-left font-semibold text-sm uppercase tracking-wider">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-chart-line"></i>
                                    Statut
                                </div>
                            </th>
                            <th class="p-4 text-center font-semibold text-sm uppercase tracking-wider">
                                <div class="flex items-center gap-2 justify-center">
                                    <i class="fas fa-cogs"></i>
                                    Actions
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($c = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 <?= $c['statut'] === 'desactive' ? 'opacity-60 bg-red-50' : '' ?>">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-xl flex items-center justify-center shadow-md">
                                                <span class="text-white font-bold text-sm">#<?= $c['id'] ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($c['nom']) ?></span>
                                            <span class="text-sm text-gray-500">Client</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2 text-gray-700">
                                            <i class="fas fa-envelope text-blue-500"></i>
                                            <?= htmlspecialchars($c['email']) ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-clock text-purple-500"></i>
                                            <?= date('d/m/Y', strtotime($c['date_creation'])) ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($c['statut'] === 'actif'): ?>
                                            <div class="inline-flex items-center gap-2 bg-green-100 text-green-800 px-4 py-2 rounded-full font-medium shadow-sm">
                                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                                <span>Actif</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="inline-flex items-center gap-2 bg-red-100 text-red-800 px-4 py-2 rounded-full font-medium shadow-sm">
                                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                                <span>Désactivé</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-center gap-2">
                                            <?php if ($c['statut'] === 'actif'): ?>
                                                <button
                                                    onclick="openToggleModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nom'])) ?>', 'desactiver')"
                                                    class="group relative bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white px-4 py-2 rounded-xl font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 flex items-center gap-2">
                                                    <i class="fas fa-ban"></i>
                                                    <span>Désactiver</span>
                                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                        Désactiver le client
                                                    </div>
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    onclick="openToggleModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nom'])) ?>', 'activer')"
                                                    class="group relative bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-4 py-2 rounded-xl font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 flex items-center gap-2">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span>Activer</span>
                                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                        Réactiver le client
                                                    </div>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center">
                                    <div class="flex flex-col items-center gap-4 text-gray-500">
                                        <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-users text-3xl text-gray-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-lg font-medium">Aucun client enregistré</p>
                                            <p class="text-sm">Les clients apparaîtront ici une fois inscrits</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation -->
    <div id="toggleModal"
        class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95">
            <!-- Header de la modal -->
            <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6 rounded-t-3xl">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Confirmation requise</h3>
                        <p class="text-orange-100 text-sm mt-1">Action importante sur le compte client</p>
                    </div>
                </div>
            </div>

            <!-- Contenu de la modal -->
            <div class="p-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user-shield text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-700 text-lg leading-relaxed">
                            Voulez-vous <span id="actionText" class="font-bold text-orange-600"></span> le compte de 
                            <span id="clientName" class="font-bold text-gray-800"></span> ?
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span id="actionDescription"></span>
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button onclick="closeToggleModal()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-medium transition-all duration-200 border border-gray-300 hover:border-gray-400 flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i>
                        <span>Annuler</span>
                    </button>
                    <a id="confirmBtn" href="#"
                        class="flex-1 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fas fa-check"></i>
                        <span>Confirmer</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openToggleModal(id, name, action) {
            const modal = document.getElementById('toggleModal');
            const clientName = document.getElementById('clientName');
            const actionText = document.getElementById('actionText');
            const actionDescription = document.getElementById('actionDescription');
            const confirmBtn = document.getElementById('confirmBtn');

            clientName.textContent = name;
            
            if (action === 'desactiver') {
                actionText.textContent = 'désactiver';
                actionText.className = 'font-bold text-red-600';
                actionDescription.textContent = 'Le client ne pourra plus se connecter ni passer de commandes.';
                confirmBtn.className = 'flex-1 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 flex items-center justify-center gap-2';
            } else {
                actionText.textContent = 'réactiver';
                actionText.className = 'font-bold text-green-600';
                actionDescription.textContent = 'Le client retrouvera l\'accès à son compte et pourra passer des commandes.';
                confirmBtn.className = 'flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 flex items-center justify-center gap-2';
            }

            confirmBtn.href = "?toggle=" + id;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
        }

        function closeToggleModal() {
            const modal = document.getElementById('toggleModal');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('toggleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeToggleModal();
            }
        });

        // Empêcher la fermeture en cliquant à l'intérieur
        document.querySelector('#toggleModal > div').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Fermer avec la touche Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeToggleModal();
            }
        });
    </script>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }

        /* Style pour la scrollbar */
        .overflow-x-auto::-webkit-scrollbar {
            height: 8px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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