<?php declare(strict_types=1);
session_start();
require_once "../configs/database.php";

// Vérifie si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fonction de conversion USD vers FC (Francs Congolais)
function convertirUsdVersFc($prixUsd)
{
    $tauxChange = 2500; // 1 USD = 2,500 FC
    return $prixUsd * $tauxChange;
}

// Fonction de conversion FC vers USD
function convertirFcVersUsd($prixFc)
{
    $tauxChange = 2500; // 1 USD = 2,500 FC
    return $prixFc / $tauxChange;
}

function formaterPrix($prix)
{
    // Conversion en float pour s'assurer que c'est un nombre
    $prix = floatval($prix);
    return number_format($prix, 0, ',', ' ');
}

// Vérifie si le dossier uploads existe
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

$message = "";

// === AJOUT D'UN PRODUIT ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ajouter'])) {
    $nom = trim($_POST["nom"]);
    $description = trim($_POST["description"]);
    $prix = floatval($_POST["prix"]);
    $devise = $_POST["devise"] ?? 'USD';
    $poids = trim($_POST["poids"] ?? '');

    // Stocker le prix TEL QUEL avec sa devise
    $quantite = intval($_POST["quantite"]);
    $categorie = trim($_POST["categorie"]);

    $image = null;
    if (!empty($_FILES["image"]["name"])) {
        $image = "uploads/" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    $stmt = $conn->prepare("INSERT INTO produits (nom, description, prix, devise, poids, quantite, categorie, image, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssdssdss", $nom, $description, $prix, $devise, $poids, $quantite, $categorie, $image);

    if ($stmt->execute()) {
        $message = "✅ Produit ajouté avec succès.";
    } else {
        $message = "❌ Erreur lors de l'ajout du produit.";
    }
}

// === MODIFICATION D'UN PRODUIT ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['modifier'])) {
    $id = intval($_POST["id"]);
    $nom = trim($_POST["nom"]);
    $description = trim($_POST["description"]);
    $prix = floatval($_POST["prix"]);
    $devise = $_POST["devise"] ?? 'USD';
    $poids = trim($_POST["poids"] ?? '');

    // Stocker le prix TEL QUEL avec sa devise
    $quantite = intval($_POST["quantite"]);
    $categorie = trim($_POST["categorie"]);

    $image = $_POST["image_actuelle"];
    if (!empty($_FILES["image"]["name"])) {
        $image = "uploads/" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image);
    }

    $stmt = $conn->prepare("UPDATE produits SET nom=?, description=?, prix=?, devise=?, poids=?, quantite=?, categorie=?, image=?, date_modification=NOW() WHERE id=?");
    $stmt->bind_param("ssdssdssi", $nom, $description, $prix, $devise, $poids, $quantite, $categorie, $image, $id);

    if ($stmt->execute()) {
        $message = "✅ Produit modifié avec succès.";
    } else {
        $message = "❌ Erreur lors de la modification du produit.";
    }
}

// === SUPPRESSION D'UN PRODUIT ===
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $conn->query("DELETE FROM produits WHERE id=$id");
    $message = "🗑️ Produit supprimé avec succès.";
}

// === LISTE DES PRODUITS ===
$result = $conn->query("SELECT * FROM produits ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Produits - Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a2d9b2e6c1.js" crossorigin="anonymous"></script>
</head>

<body class="flex bg-gray-100" style="font-family: 'Arial', sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <div class="flex-1 p-6">
        <h1 class="text-2xl font-bold mb-4 text-blue-700">Gestion des produits</h1>

        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $message ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout -->
        <form method="POST" enctype="multipart/form-data"
            class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 mb-6">
            <h2 class="text-xl font-bold mb-6 text-blue-800 flex items-center gap-2">
                <i class="fas fa-plus-circle text-blue-600"></i>
                Ajouter un produit au stock
            </h2>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Sélection du produit existant -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-box mr-2 text-blue-500"></i>
                        Sélectionner un produit existant
                    </label>
                    <select name="produit_existant" id="produit_existant"
                        class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 bg-white">
                        <option value="">-- Choisir un produit --</option>
                        <option value="ciment">Ciment</option>
                        <option value="gravier">Gravier</option>
                        <option value="pave">Pavé</option>
                        <option value="carreaux">Carreaux</option>
                        <option value="gyproc">Gyproc</option>
                        <option value="omega">Omega</option>
                        <option value="chanel">Chanel</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        Sélectionnez un produit existant ou remplissez les champs ci-dessous pour un nouveau produit
                    </p>
                </div>

                <!-- OU Séparateur -->
                <div class="md:col-span-2 flex items-center my-4">
                    <div class="flex-1 border-t border-gray-200"></div>
                    <span class="mx-4 text-sm font-semibold text-gray-500">OU</span>
                    <div class="flex-1 border-t border-gray-200"></div>
                </div>

                <!-- Champs pour nouveau produit -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2 text-blue-500"></i>
                        Nom du produit
                    </label>
                    <input type="text" name="nom" placeholder="Ex: Ciment 32.5R"
                        class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200"
                        id="nom_produit">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-layer-group mr-2 text-blue-500"></i>
                        Catégorie
                    </label>
                    <select name="categorie"
                        class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200">
                        <option value="">Choisir une catégorie</option>
                        <option value="ciment">Ciment</option>
                        <option value="granulats">Granulats</option>
                        <option value="carrelage">Carrelage</option>
                        <option value="pavage">Pavage</option>
                        <option value="plaque">Plaques & Cloisons</option>
                        <option value="profil">Profils Métalliques</option>
                        <option value="quincaillerie">Quincaillerie</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-2 text-blue-500"></i>
                        Description
                    </label>
                    <textarea name="description" placeholder="Décrivez le produit en détail..." rows="4"
                        class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 resize-none"></textarea>
                </div>

                <!-- Prix et Devise -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-dollar-sign mr-2 text-blue-500"></i>
                        Prix et Devise
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <select name="devise" id="devise"
                                class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200"
                                onchange="updatePricePlaceholder()">
                                <option value="USD">USD ($)</option>
                                <option value="FC">Francs Congolais (FC)</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <input type="number" min="0" step="0.01" name="prix" id="prix" placeholder="Prix en USD"
                                class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200"
                                required>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div id="conversion-info" class="text-sm text-blue-600 bg-blue-50 p-3 rounded-lg">
                            <i class="fas fa-info-circle mr-2"></i>
                            Le prix sera enregistré en <span class="font-semibold">Dollars (USD)</span>
                        </div>
                        <div id="conversion-display" class="text-sm text-green-600 bg-green-50 p-3 rounded-lg hidden">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            <span id="conversion-text"></span>
                        </div>
                    </div>
                </div>

                <!-- Poids et Quantité -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-weight-hanging mr-2 text-blue-500"></i>
                        Poids / Dimension
                    </label>
                    <input type="text" name="poids" placeholder="Ex: 50kg, 8-12mm, 30x30cm"
                        class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200">
                    <p class="text-xs text-gray-500 mt-2">
                        Exemples: 50kg, 25kg, 8-12mm, 30x30cm, 10x20cm, 12mm
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-cubes mr-2 text-blue-500"></i>
                        Quantité en stock
                    </label>
                    <input type="number" min="0" name="quantite" placeholder="Ex: 100"
                        class="w-full border-2 border-gray-200 rounded-xl p-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200"
                        required>
                </div>

                <!-- Image -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-image mr-2 text-blue-500"></i>
                        Image du produit
                    </label>
                    <div
                        class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-blue-400 transition duration-200">
                        <input type="file" name="image" class="hidden" id="fileInput" accept="image/*">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-600 mb-2">Glissez-déposez ou cliquez pour uploader</p>
                            <button type="button" onclick="document.getElementById('fileInput').click()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-upload mr-2"></i>
                                Choisir un fichier
                            </button>
                        </div>
                        <div id="fileName" class="text-sm text-green-600 mt-2 hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Bouton de soumission -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <button type="submit" name="ajouter"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-3">
                    <i class="fas fa-plus-circle text-xl"></i>
                    <span class="text-lg">Publier le produit</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>

        <!-- Liste des produits -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100">
            <h2 class="text-xl font-bold mb-6 text-blue-800 flex items-center gap-2">
                <i class="fas fa-boxes-stacked text-blue-600"></i>
                Liste des produits
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead class="bg-gradient-to-r from-blue-500 to-blue-200 text-white ">
                        <tr>
                            <th class="p-4 border text-left font-semibold rounded">ID</th>
                            <th class="p-4 border text-left font-semibold rounded">Nom</th>
                            <th class="p-4 border text-left font-semibold rounded">Prix</th>
                            <th class="p-4 border text-left font-semibold rounded">Devise</th>
                            <th class="p-4 border text-left font-semibold rounded">Poids</th>
                            <th class="p-4 border text-left font-semibold rounded">Qté</th>
                            <th class="p-4 border text-left font-semibold rounded">Catégorie</th>
                            <th class="p-4 border text-left font-semibold rounded">Image</th>
                            <th class="p-4 border text-left font-semibold rounded">Créé le</th>
                            <th class="p-4 border text-left font-semibold rounded">Modifié le</th>
                            <th class="p-4 border text-center font-semibold rounded">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-blue-50 transition duration-200">
                                <td class="p-4 border text-gray-700 font-medium"><?= $p['id'] ?></td>
                                <td class="p-4 border font-semibold text-gray-800"><?= htmlspecialchars($p['nom']) ?></td>
                                <td class="p-4 border">
                                    <div class="space-y-1">
                                        <!-- Afficher le prix stocké -->
                                        <div class="font-bold text-green-600 text-lg">
                                            <?= formaterPrix($p['prix']) ?>
                                            <?= $p['devise'] === 'USD' ? '$' : 'FC' ?>
                                        </div>
                                        <!-- Afficher la conversion -->
                                        <div class="text-sm text-gray-500">
                                            <?php if ($p['devise'] === 'USD'): ?>
                                                <?= formaterPrix(convertirUsdVersFc($p['prix'])) ?> FC
                                            <?php else: ?>
                                                <?= formaterPrix(convertirFcVersUsd($p['prix'])) ?> $
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 border">
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-semibold <?= $p['devise'] === 'USD' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-blue-100 text-blue-800 border border-blue-200' ?>">
                                        <?= $p['devise'] ?>
                                    </span>
                                </td>
                                <td class="p-4 border text-gray-600">
                                    <?= $p['poids'] ? htmlspecialchars($p['poids']) : '<span class="text-gray-400">-</span>' ?>
                                </td>
                                <td class="p-4 border">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?= $p['quantite'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $p['quantite'] ?>
                                    </span>
                                </td>
                                <td class="p-4 border">
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                                        <?= htmlspecialchars($p['categorie']) ?>
                                    </span>
                                </td>
                                <td class="p-4 border text-center">
                                    <?php if ($p['image']): ?>
                                        <img src="<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['nom']) ?>"
                                            class="w-12 h-12 object-cover mx-auto rounded-lg border border-gray-200">
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">Aucune</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 border text-sm text-gray-500"><?= $p['date_creation'] ?></td>
                                <td class="p-4 border text-sm text-gray-500"><?= $p['date_modification'] ?: '-' ?></td>
                                <td class="p-4 border text-center">
                                    <div class="flex justify-center space-x-3">
                                        <a href="?edit=<?= $p['id'] ?>"
                                            class="text-blue-600 hover:text-blue-800 transition duration-200 transform hover:scale-110"
                                            title="Modifier">
                                            <i class="fas fa-edit text-lg"></i>
                                        </a>
                                        <button onclick="openDeleteModal(<?= $p['id'] ?>)"
                                            class="text-red-600 hover:text-red-800 transition duration-200 transform hover:scale-110"
                                            title="Supprimer">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <?php if (isset($_GET['edit'])):
            $id = intval($_GET['edit']);
            $prod = $conn->query("SELECT * FROM produits WHERE id=$id")->fetch_assoc();
            ?>
            <div
                class="mt-8 bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-200 p-8 rounded-2xl shadow-lg">
                <h2 class="text-2xl font-bold mb-6 text-yellow-800 flex items-center gap-3">
                    <i class="fas fa-edit text-yellow-600"></i>
                    Modifier le produit
                </h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                    <input type="hidden" name="image_actuelle" value="<?= $prod['image'] ?>">

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tag mr-2 text-yellow-500"></i>
                                Nom du produit
                            </label>
                            <input type="text" name="nom" value="<?= htmlspecialchars($prod['nom']) ?>"
                                class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-layer-group mr-2 text-yellow-500"></i>
                                Catégorie
                            </label>
                            <select name="categorie"
                                class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200">
                                <option value="ciment" <?= $prod['categorie'] === 'ciment' ? 'selected' : '' ?>>Ciment</option>
                                <option value="granulats" <?= $prod['categorie'] === 'granulats' ? 'selected' : '' ?>>Granulats
                                </option>
                                <option value="carrelage" <?= $prod['categorie'] === 'carrelage' ? 'selected' : '' ?>>Carrelage
                                </option>
                                <option value="pavage" <?= $prod['categorie'] === 'pavage' ? 'selected' : '' ?>>Pavage</option>
                                <option value="plaque" <?= $prod['categorie'] === 'plaque' ? 'selected' : '' ?>>Plaques &
                                    Cloisons</option>
                                <option value="profil" <?= $prod['categorie'] === 'profil' ? 'selected' : '' ?>>Profils
                                    Métalliques</option>
                                <option value="quincaillerie" <?= $prod['categorie'] === 'quincaillerie' ? 'selected' : '' ?>>
                                    Quincaillerie</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-align-left mr-2 text-yellow-500"></i>
                                Description
                            </label>
                            <textarea name="description" rows="4"
                                class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200 resize-none"><?= htmlspecialchars($prod['description']) ?></textarea>
                        </div>

                        <!-- Prix et Devise -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">
                                <i class="fas fa-dollar-sign mr-2 text-yellow-500"></i>
                                Prix et Devise
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-1">
                                    <select name="devise" id="devise-edit"
                                        class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200"
                                        onchange="updatePricePlaceholderEdit()">
                                        <option value="USD" <?= $prod['devise'] === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                                        <option value="FC" <?= $prod['devise'] === 'FC' ? 'selected' : '' ?>>Francs Congolais
                                            (FC)</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <input type="number" min="0" step="0.01" name="prix" id="prix-edit"
                                        value="<?= floatval($prod['prix']) ?>"
                                        class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200"
                                        required>
                                </div>
                            </div>
                            <div class="mt-3 space-y-2">
                                <div class="text-sm text-yellow-700 bg-yellow-50 p-3 rounded-lg">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <?php if ($prod['devise'] === 'USD'): ?>
                                        Stocké : <span class="font-semibold"><?= formaterPrix($prod['prix']) ?> $</span>
                                        | Conversion : <?= formaterPrix(convertirUsdVersFc($prod['prix'])) ?> FC
                                    <?php else: ?>
                                        Stocké : <span class="font-semibold"><?= formaterPrix($prod['prix']) ?> FC</span>
                                        | Conversion : <?= formaterPrix(convertirFcVersUsd($prod['prix'])) ?> $
                                    <?php endif; ?>
                                </div>
                                <div id="conversion-display-edit"
                                    class="text-sm text-green-600 bg-green-50 p-3 rounded-lg hidden">
                                    <i class="fas fa-exchange-alt mr-2"></i>
                                    <span id="conversion-text-edit"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Poids et Quantité -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-weight-hanging mr-2 text-yellow-500"></i>
                                Poids / Dimension
                            </label>
                            <input type="text" name="poids" value="<?= htmlspecialchars($prod['poids'] ?? '') ?>"
                                placeholder="Ex: 50kg, 8-12mm, 30x30cm"
                                class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-cubes mr-2 text-yellow-500"></i>
                                Quantité en stock
                            </label>
                            <input type="number" min="0" name="quantite" value="<?= $prod['quantite'] ?>"
                                class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200"
                                required>
                        </div>

                        <!-- Image -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image mr-2 text-yellow-500"></i>
                                Image du produit
                            </label>
                            <?php if ($prod['image']): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600 mb-2">Image actuelle :</p>
                                    <img src="<?= $prod['image'] ?>" alt="<?= htmlspecialchars($prod['nom']) ?>"
                                        class="w-32 h-32 object-cover rounded-lg border border-yellow-200">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image"
                                class="w-full border-2 border-yellow-200 rounded-xl p-3 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition duration-200">
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-yellow-200">
                        <button type="submit" name="modifier"
                            class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center gap-3">
                            <i class="fas fa-save"></i>
                            <span>Mettre à jour</span>
                        </button>
                        <a href="produits.php"
                            class="text-gray-600 hover:text-gray-800 transition duration-200 flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            <span>Annuler</span>
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <!-- 🗑️ Modale de confirmation de suppression -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all duration-300 scale-95">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Confirmation</h2>
                <p class="text-gray-600 mb-6">Voulez-vous vraiment supprimer ce produit ? Cette action est irréversible.
                </p>

                <div class="flex justify-center space-x-4">
                    <button onclick="closeDeleteModal()"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl text-gray-700 font-medium transition duration-200 flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <a id="confirmDeleteLink" href="#"
                        class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-xl text-white font-semibold transition duration-200 flex items-center gap-2">
                        <i class="fas fa-trash"></i>
                        Supprimer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script -->
    <script>
        function openDeleteModal(id) {
            const modal = document.getElementById("deleteModal");
            const link = document.getElementById("confirmDeleteLink");
            link.href = "?supprimer=" + id;
            modal.classList.remove("hidden");
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById("deleteModal");
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add("hidden");
            }, 300);
        }

        // Mise à jour du placeholder du prix
        function updatePricePlaceholder() {
            const devise = document.getElementById('devise').value;
            const prixInput = document.getElementById('prix');
            const conversionInfo = document.getElementById('conversion-info');

            if (devise === 'USD') {
                prixInput.placeholder = 'Prix en USD';
                conversionInfo.innerHTML = '<i class="fas fa-info-circle mr-2"></i>Le prix sera enregistré en <span class="font-semibold">Dollars (USD)</span>';
            } else {
                prixInput.placeholder = 'Prix en Francs Congolais';
                conversionInfo.innerHTML = '<i class="fas fa-info-circle mr-2"></i>Le prix sera enregistré en <span class="font-semibold">Francs Congolais (FC)</span>';
            }
        }

        // Gestion de la sélection du produit existant
        document.getElementById('produit_existant').addEventListener('change', function () {
            const produitSelect = this.value;
            const nomInput = document.getElementById('nom_produit');
            const categorieSelect = document.querySelector('select[name="categorie"]');
            const poidsInput = document.querySelector('input[name="poids"]');

            const produitsData = {
                'ciment': { nom: 'Ciment', categorie: 'ciment', poids: '50kg' },
                'gravier': { nom: 'Gravier', categorie: 'granulats', poids: '8-12mm' },
                'pave': { nom: 'Pavé', categorie: 'pavage', poids: '10x20cm' },
                'carreaux': { nom: 'Carreaux', categorie: 'carrelage', poids: '30x30cm' },
                'gyproc': { nom: 'gyproc', categorie: 'plaque', poids: '12.5mm' },
                'omega': { nom: 'Omega', categorie: 'profil', poids: '6m' },
                'chanel': { nom: 'Chanel', categorie: 'profil', poids: '6m' }
            };

            if (produitSelect && produitsData[produitSelect]) {
                nomInput.value = produitsData[produitSelect].nom;
                categorieSelect.value = produitsData[produitSelect].categorie;
                poidsInput.value = produitsData[produitSelect].poids;
                nomInput.focus();
            }
        });

        // Gestion de l'upload de fichier
        document.getElementById('fileInput').addEventListener('change', function (e) {
            const fileNameDiv = document.getElementById('fileName');
            if (this.files.length > 0) {
                fileNameDiv.textContent = 'Fichier sélectionné : ' + this.files[0].name;
                fileNameDiv.classList.remove('hidden');
            } else {
                fileNameDiv.classList.add('hidden');
            }
        });

        // Conversion en temps réel
        const TAUX_CHANGE = 2500; // 1 USD = 2,500 FC

        function convertPrice(inputId, textId, displayId) {
            const prixInput = document.getElementById(inputId);
            const devise = document.getElementById(inputId === 'prix' ? 'devise' : 'devise-edit').value;
            const prix = parseFloat(prixInput.value);
            const conversionText = document.getElementById(textId);
            const conversionDisplay = document.getElementById(displayId);

            if (isNaN(prix) || prix <= 0) {
                conversionDisplay.classList.add('hidden');
                return;
            }

            let convertedPrice;
            let convertedCurrency;

            if (devise === 'USD') {
                convertedPrice = prix * TAUX_CHANGE;
                convertedCurrency = 'FC';
            } else {
                convertedPrice = prix / TAUX_CHANGE;
                convertedCurrency = 'USD';
            }

            conversionText.textContent = `≈ ${formatNumber(convertedPrice)} ${convertedCurrency}`;
            conversionDisplay.classList.remove('hidden');
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(num));
        }

        function updatePricePlaceholderEdit() {
            const devise = document.getElementById('devise-edit').value;
            const prixInput = document.getElementById('prix-edit');

            if (devise === 'USD') {
                prixInput.placeholder = 'Prix en USD';
            } else {
                prixInput.placeholder = 'Prix en Francs Congolais';
            }

            // Ajouter l'événement de conversion en temps réel
            prixInput.oninput = function () {
                convertPrice('prix-edit', 'conversion-text-edit', 'conversion-display-edit');
            };
        }

        // Initialiser les événements au chargement de la page
        document.addEventListener('DOMContentLoaded', function () {
            updatePricePlaceholder();
            if (document.getElementById('devise-edit')) {
                updatePricePlaceholderEdit();
            }
        });
    </script>



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