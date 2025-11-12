<?php declare(strict_types=1); 
session_start();
require_once "../configs/database.php";

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Déterminer le mois sélectionné (par défaut : mois actuel)
$selected_month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');

// Fonction pour obtenir le nom du mois
function getMonthName($num)
{
    $mois = [
        1 => "Janvier",
        2 => "Février",
        3 => "Mars",
        4 => "Avril",
        5 => "Mai",
        6 => "Juin",
        7 => "Juillet",
        8 => "Août",
        9 => "Septembre",
        10 => "Octobre",
        11 => "Novembre",
        12 => "Décembre"
    ];
    return $mois[$num] ?? "";
}

// Requêtes filtrées par mois sélectionné
$stmt = $conn->prepare("SELECT COUNT(*) as total_produits FROM produits WHERE MONTH(date_creation) = ?");
$stmt->bind_param("i", $selected_month);
$stmt->execute();
$produits = $stmt->get_result()->fetch_assoc()['total_produits'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_reservations FROM reservations WHERE MONTH(date_reservation) = ?");
$stmt->bind_param("i", $selected_month);
$stmt->execute();
$reservations = $stmt->get_result()->fetch_assoc()['total_reservations'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_clients FROM utilisateurs WHERE role='client' AND MONTH(date_creation) = ?");
$stmt->bind_param("i", $selected_month);
$stmt->execute();
$clients = $stmt->get_result()->fetch_assoc()['total_clients'];

// Fréquence sur 6 derniers mois
$produits_freq = $conn->query("SELECT MONTH(date_creation) as mois, COUNT(*) as total 
                                FROM produits 
                                WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                GROUP BY MONTH(date_creation) ORDER BY mois ASC")->fetch_all(MYSQLI_ASSOC);

$reservations_freq = $conn->query("SELECT MONTH(date_reservation) as mois, COUNT(*) as total 
                                    FROM reservations 
                                    WHERE date_reservation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                    GROUP BY MONTH(date_reservation) ORDER BY mois ASC")->fetch_all(MYSQLI_ASSOC);

$clients_freq = $conn->query("SELECT MONTH(date_creation) as mois, COUNT(*) as total 
                                FROM utilisateurs 
                                WHERE role='client' AND date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                GROUP BY MONTH(date_creation) ORDER BY mois ASC")->fetch_all(MYSQLI_ASSOC);

function formatFrequency($data)
{
    $labels = [];
    $values = [];
    foreach ($data as $row) {
        // CORRECTION : Conversion en entier pour mktime()
        $month_num = (int)$row['mois'];
        $labels[] = date("M", mktime(0, 0, 0, $month_num, 1));
        $values[] = $row['total'];
    }
    return ['labels' => $labels, 'values' => $values];
}

$produits_chart = formatFrequency($produits_freq);
$reservations_chart = formatFrequency($reservations_freq);
$clients_chart = formatFrequency($clients_freq);

// Métriques avancées
$total_produits = $conn->query("SELECT COUNT(*) as total FROM produits")->fetch_assoc()['total'];
$total_clients = $conn->query("SELECT COUNT(*) as total FROM utilisateurs WHERE role='client'")->fetch_assoc()['total'];
$total_reservations = $conn->query("SELECT COUNT(*) as total FROM reservations")->fetch_assoc()['total'];

// Revenus (si vous avez une table de commandes avec prix)
$revenus_mois = $conn->query("SELECT COALESCE(SUM(prix), 0) as total FROM produits p 
                                 JOIN reservations r ON p.id = r.produit_id 
                                 WHERE MONTH(r.date_reservation) = $selected_month")->fetch_assoc()['total'];

// Produits les plus populaires
$produits_populaires = $conn->query("SELECT p.nom, COUNT(r.id) as reservations_count 
                                        FROM produits p 
                                        LEFT JOIN reservations r ON p.id = r.produit_id 
                                        GROUP BY p.id, p.nom 
                                        ORDER BY reservations_count DESC 
                                        LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Activité récente
$activite_recente = $conn->query("SELECT 'Nouveau produit' as type, p.nom as description, p.date_creation as date_activite 
                                     FROM produits p 
                                     WHERE p.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                     UNION ALL
                                     SELECT 'Nouvelle réservation' as type, CONCAT(u.nom, ' a réservé ', p.nom) as description, r.date_reservation as date_activite
                                     FROM reservations r 
                                     JOIN utilisateurs u ON r.utilisateur_id = u.id 
                                     JOIN produits p ON r.produit_id = p.id 
                                     WHERE r.date_reservation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                     ORDER BY date_activite DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Johnson Jr Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#673DE6',
                        secondary: '#8B5CF6',
                        accent: '#A78BFA'
                    }
                }
            }
        }
    </script>
</head>

<body class="flex h-screen bg-gray-200" style="font-family: 'Arial', sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="flex-1 p-6 overflow-auto bg-gray-50 min-h-screen">

        <!-- Bandeau de filtrage -->
        <div
            class="bg-white shadow rounded-xl p-4 mb-8 flex flex-wrap items-center justify-between gap-3 border border-gray-100">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-blue-500 text-lg"></i>
                Tableau de bord — <span class="text-blue-500"><?= getMonthName($selected_month) ?></span>
            </h2>

            <form method="GET" class="flex items-center gap-2">
                <label for="month" class="font-medium text-gray-700 text-sm">Mois :</label>
                <select name="month" id="month" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 bg-gray-50 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                            <?= getMonthName($m) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <!-- Grille principale -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

            <!-- Cartes statistiques -->
            <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Produits -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-purple-100 text-purple-700 rounded-lg">
                            <i class="fa-solid fa-boxes-stacked text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-x font-bold uppercase tracking-wider">Produits</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $produits ?></p>
                        </div>
                    </div>
                    <p class="mt-3 text-gray-400 text-x leading-snug">
                        Sur 6 derniers mois :
                        <br>
                        <?php foreach ($produits_chart['labels'] as $i => $month): ?>
                            <span class="text-purple-500"><?= $month ?>
                                (<?= $produits_chart['values'][$i] ?>)</span><?= $i < count($produits_chart['labels']) - 1 ? ' · ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>

                <!-- Réservations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-green-100 text-green-700 rounded-lg">
                            <i class="fa-solid fa-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-x font-bold uppercase tracking-wider">Réservations</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $reservations ?></p>
                        </div>
                    </div>
                    <p class="mt-3 text-gray-400 text-x leading-snug">
                        Sur 6 derniers mois :
                        <br>
                        <?php foreach ($reservations_chart['labels'] as $i => $month): ?>
                            <span class="text-green-500"><?= $month ?>
                                (<?= $reservations_chart['values'][$i] ?>)</span><?= $i < count($reservations_chart['labels']) - 1 ? ' · ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>

                <!-- Clients -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-blue-100 text-blue-700 rounded-lg">
                            <i class="fa-solid fa-users text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-500 text-x font-bold uppercase tracking-wider">Clients</h3>
                            <p class="text-2xl font-bold text-gray-800"><?= $clients ?></p>
                        </div>
                    </div>
                    <p class="mt-3 text-gray-400 text-x leading-snug">
                        Sur 6 derniers mois :
                        <br>
                        <?php foreach ($clients_chart['labels'] as $i => $month): ?>
                            <span class="text-blue-500"><?= $month ?>
                                (<?= $clients_chart['values'][$i] ?>)</span><?= $i < count($clients_chart['labels']) - 1 ? ' · ' : '' ?>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>

            <!-- Profil Admin -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center hover:shadow-md transition">
                <div
                    class="w-20 h-20 mb-4 rounded-full border-2 border-purple-700 flex items-center justify-center bg-purple-50 text-purple-700 text-4xl">
                    <i class="fa-solid fa-user"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700"><?= htmlspecialchars($_SESSION['admin_nom']) ?></h3>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars($_SESSION['user_role']) ?></p>
                <p class="text-gray-400 text-xs mt-1 mb-4">Connecté le <?= date("d/m/Y") ?></p>
                <a href="deconnexion.php"
                    class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i> Déconnexion
                </a>
            </div>



        </div>
        <!-- ==================== SECTION GRAPHIQUES & ACTIVITÉS ==================== -->
        <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php


            // 1️⃣ — Trouver les 3 produits les plus vendus
            $topQuery = "
                SELECT p.id, p.nom, SUM(r.quantite) AS total_vendu
                FROM reservations r
                JOIN produits p ON r.produit_id = p.id
                WHERE r.statut = 'confirmée'
                GROUP BY p.id
                ORDER BY total_vendu DESC
                LIMIT 3
            ";
            $topResult = $conn->query($topQuery);
            $topProducts = [];
            if ($topResult && $topResult->num_rows > 0) {
                $topProducts = $topResult->fetch_all(MYSQLI_ASSOC);
            }

            // 2️⃣ — Préparer les labels (6 derniers mois)
            $labels = [];
            for ($i = 5; $i >= 0; $i--) {
                $labels[] = date("M Y", strtotime("-$i months"));
            }

            // 3️⃣ — Récupérer les ventes mensuelles pour ces produits
            $salesData = [];
            foreach ($topProducts as $prod) {
                $data = [];
                foreach ($labels as $month) {
                    [$mois, $annee] = explode(' ', $month);
                    $moisNum = date('m', strtotime($mois));

                    $stmt = $conn->prepare("
                SELECT COALESCE(SUM(r.quantite), 0) AS ventes
                FROM reservations r
                WHERE r.produit_id = ? 
                AND r.statut = 'confirmée'
                AND MONTH(r.date_reservation) = ? 
                AND YEAR(r.date_reservation) = ?
        ");
                    $stmt->bind_param("iii", $prod['id'], $moisNum, $annee);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $data[] = $result['ventes'] ?? 0;
                }
                $salesData[] = [
                    'label' => $prod['nom'],
                    'data' => $data
                ];
            }
            ?>

            <!-- Graphique de tendance des ventes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-blue-600"></i>
                    Tendances des ventes
                </h3>

                <canvas id="salesChart" class="w-full h-64"></canvas>

                <p class="text-gray-400 text-sm mt-3">
                    Aperçu des ventes réalisées sur les 6 derniers mois pour les produits les plus vendus.
                </p>
            </div>

            <!-- Script du graphique -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('salesChart').getContext('2d');
                const salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: <?= json_encode(array_map(function ($item) {
                            return [
                                'label' => $item['label'],
                                'data' => $item['data'],
                                'borderWidth' => 2,
                                'fill' => false,
                            ];
                        }, $salesData)) ?>
                    },
                    options: {
                        responsive: true,
                        tension: 0.4,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { font: { size: 13 } } },
                            tooltip: { backgroundColor: '#111827', titleColor: '#fff', bodyColor: '#fff' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            </script>


            <?php
            // Récupérer les 10 dernières activités parmi produits, réservations et utilisateurs
            $sql = "
    SELECT 'produit' AS type, nom AS titre, date_creation AS date_activite, NULL AS statut
    FROM produits
    UNION ALL
    SELECT 'reservation' AS type, statut AS titre, date_reservation AS date_activite, NULL AS statut
    FROM reservations
    UNION ALL
    SELECT 'utilisateur' AS type, nom AS titre, date_creation AS date_activite, statut
    FROM utilisateurs
    ORDER BY date_activite DESC
    LIMIT 10
";
            $result = $conn->query($sql);
            $activites = [];

            if ($result && $result->num_rows > 0) {
                $activites = $result->fetch_all(MYSQLI_ASSOC);
            }

            // Fonction pour afficher le temps écoulé
            function timeAgo($datetime)
            {
                $timestamp = strtotime($datetime);
                $diff = time() - $timestamp;

                if ($diff < 60)
                    return "Il y a quelques secondes";
                if ($diff < 3600)
                    return "Il y a " . floor($diff / 60) . " min";
                if ($diff < 86400)
                    return "Il y a " . floor($diff / 3600) . " h";
                if ($diff < 172800)
                    return "Hier";
                return "Il y a " . floor($diff / 86400) . " jours";
            }
            ?>

            <!-- Dernières activités -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-green-600"></i>
                    Activités récentes
                </h3>

                <?php if (empty($activites)): ?>
                    <p class="text-gray-500 text-sm italic">Aucune activité récente.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-100 text-sm text-gray-700">
                        <?php foreach ($activites as $a): ?>
                            <li class="py-3 flex items-center justify-between">
                                <span class="flex items-center">
                                    <?php if ($a['type'] === 'produit'): ?>
                                        <i class="fa-solid fa-plus-circle text-green-500 mr-2"></i>
                                        Nouveau produit ajouté : <strong class="ml-1"><?= htmlspecialchars($a['titre']) ?></strong>
                                    <?php elseif ($a['type'] === 'reservation'): ?>
                                        <i class="fa-solid fa-cart-shopping text-yellow-500 mr-2"></i>
                                        Nouvelle réservation (<?= htmlspecialchars($a['titre']) ?>)
                                    <?php elseif ($a['type'] === 'utilisateur'): ?>
                                        <i class="fa-solid fa-user-check text-blue-500 mr-2"></i>
                                        Nouvel utilisateur : <strong class="ml-1"><?= htmlspecialchars($a['titre']) ?></strong>
                                        <?php if ($a['statut'] === 'desactive'): ?>
                                            <span
                                                class="bg-red-100 text-red-800 px-2 py-0.5 rounded ml-2 text-xs font-medium">Désactivé</span>
                                        <?php else: ?>
                                            <span
                                                class="bg-green-100 text-green-800 px-2 py-0.5 rounded ml-2 text-xs font-medium">Actif</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                                <span class="text-gray-400 text-xs"><?= timeAgo($a['date_activite']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>


        </div>

        <!-- ==================== SECTION RAPPORTS ET TOP PRODUITS ==================== -->
        <div class="mt-10 grid grid-cols-1 lg:grid-cols-2 gap-6">

            <?php


            // Exemple : récupérer les 5 produits les plus vendus
// (On suppose que tu as un champ `ventes` dans la table `produits` ou que tu veux compter via une autre table comme `commandes`)
            $query = "SELECT nom, quantite, prix, devise FROM produits ORDER BY quantite DESC LIMIT 5";
            $result = $conn->query($query);

            $topProduits = [];
            if ($result && $result->num_rows > 0) {
                $topProduits = $result->fetch_all(MYSQLI_ASSOC);
            }
            ?>


            <!-- Top Produits -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-ranking-star text-yellow-500"></i>
                    Meilleures ventes
                </h3>

                <?php if (empty($topProduits)): ?>
                    <p class="text-gray-500 text-sm italic">Aucune donnée disponible pour le moment.</p>
                <?php else: ?>
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="py-2 px-3 font-medium">Produit</th>
                                <th class="py-2 px-3 font-medium">Quantité vendue</th>
                                <th class="py-2 px-3 font-medium">Prix</th>
                                <th class="py-2 px-3 font-medium">Devise</th>
                                <th class="py-2 px-3 font-medium text-right">Montant total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProduits as $p): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="py-2 px-3 font-medium text-gray-800"><?= htmlspecialchars($p['nom']) ?></td>
                                    <td class="py-2 px-3"><?= $p['quantite'] ?></td>
                                    <td class="py-2 px-3"><?= $p['prix'] ?></td>
                                    <td class="py-2 px-3"><?= $p['devise']?></td>
                                    <td class="py-2 px-3 text-right font-semibold text-gray-700">
                                        <?= number_format($p['prix'] * $p['quantite'], 2, ',', ' ') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>


            <!-- Rapport mensuel -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-file-lines text-red-600"></i>
                    Rapport mensuel
                </h3>
                <p class="text-gray-600 text-sm leading-relaxed mb-4">
                    Ce mois-ci, l'entreprise a enregistré une progression de <span
                        class="text-green-600 font-semibold">+18%</span> par rapport au mois précédent.
                    Le taux de fidélisation client reste stable, tandis que les ventes en ligne connaissent une hausse
                    continue.
                </p>
                <a href="#"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition">
                    <i class="fa-solid fa-download"></i> Télécharger le rapport PDF
                </a>
            </div>

        </div>
    </main>


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