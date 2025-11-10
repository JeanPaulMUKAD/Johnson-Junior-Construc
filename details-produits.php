<?php declare(strict_types=1);

session_start();
include 'config/database.php'; // Inclut la fonction getConnection()

// Initialiser la connexion PDO
try {
    $conn = getConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base : " . $e->getMessage());
}

$error_login = '';
$error_register = '';
$success_register = '';
$success_login = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ====================== Connexion ======================
    if (isset($_POST['login'])) {
        $email = htmlspecialchars($_POST['email']);
        $mot_de_passe = $_POST['mot_de_passe'];

        if (empty($email) || empty($mot_de_passe)) {
            $error_login = "Tous les champs sont obligatoires";
        } else {
            try {
                $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $success_login = "Connexion réussie !";
                } else {
                    $error_login = "Email ou mot de passe incorrect";
                }
            } catch (PDOException $e) {
                $error_login = "Erreur lors de la connexion : " . $e->getMessage();
            }
        }
    }

    // ====================== Inscription ======================
    if (isset($_POST['register'])) {
        $nom = htmlspecialchars($_POST['nom']);
        $email = htmlspecialchars($_POST['email']);
        $mot_de_passe = $_POST['mot_de_passe'];
        $confirmation = $_POST['confirmation'];

        if (empty($nom) || empty($email) || empty($mot_de_passe) || empty($confirmation)) {
            $error_register = "Tous les champs sont obligatoires";
        } elseif ($mot_de_passe != $confirmation) {
            $error_register = "Les mots de passe ne correspondent pas";
        } elseif (strlen($mot_de_passe) < 6) {
            $error_register = "Le mot de passe doit contenir au moins 6 caractères";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $error_register = "Cette adresse email est déjà utilisée";
                } else {
                    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                    $role = 'client';
                    $date_creation = date('Y-m-d H:i:s');
                    $date_modification = date('Y-m-d H:i:s');

                    $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, date_creation, date_modification) 
                                           VALUES (:nom, :email, :mot_de_passe, :role, :date_creation, :date_modification)");
                    $stmt->bindParam(':nom', $nom);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':mot_de_passe', $hashed_password);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':date_creation', $date_creation);
                    $stmt->bindParam(':date_modification', $date_modification);

                    if ($stmt->execute()) {
                        $success_register = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    } else {
                        $error_register = "Erreur lors de l'inscription";
                    }
                }
            } catch (PDOException $e) {
                $error_register = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}

// Récupérer les informations du produit depuis l'URL
$produit_id = $_GET['id'] ?? 1;
$produits = [

    1 => [
        'nom' => 'Gravier Premium',
        'description' => 'Notre gravier premium est soigneusement sélectionné pour offrir une qualité exceptionnelle dans tous vos projets de construction. Disponible en différentes granulométries, il est parfait pour le béton, le drainage, les allées et les aménagements paysagers.',
        'prix' => '8,500 FC',
        'images' => [
            'main' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_uHhxesbL0X7U1sFuy5LH9frwGBaaWfCSwQ&s',
            'thumbnails' => [
                'https://kingmateriaux.com/wp-content/uploads/2021/10/gravier-gris-de-marbre-concasse-nevada-8-12-mm.jpg',
                'https://cdn.prod.website-files.com/66b1e093a38b6999bd091025/66c0af4b012b87214c7dd217_1164%20(20).webp',
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTUO2hAn6qaKTdAP4ux-KzIyLH-APWaAHNFyA&s'
            ]
        ],
        'caracteristiques' => [
            'Granulométrie variée disponible',
            'Excellente qualité de drainage',
            'Idéal pour béton et aménagement'
        ]
    ],
    2 => [
        'nom' => 'Carreaux Céramique',
        'description' => 'Découvrez notre collection de carreaux céramique haut de gamme, parfaits pour embellir vos sols et murs. Disponibles dans une large gamme de couleurs, textures et finitions, ces carreaux allient esthétique et durabilité pour transformer vos espaces intérieurs et extérieurs.',
        'prix' => '25,000 FC',
        'images' => [
            'main' => 'https://franceschini.fr/wp-content/uploads/2017/09/carreaux-ciment-1.jpg',
            'thumbnails' => [
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrxXvkMJDcXvHXQQGf7-5nbHAy5bnjHe2NbQ&s',
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT2scEuX_v4r6P0cTU2jIB7M-4lKj3ckb-NEy3dN4SNyAZ5DQqxiNLUrl035MVDOxC3zgw&usqp=CAU',
                'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Résistance à l\'usure exceptionnelle',
            'Facile d\'entretien et nettoyage',
            'Design moderne et intemporel'
        ]
    ],
    3 => [
        'nom' => 'Pavés Autobloquants',
        'description' => 'Nos pavés autobloquants offrent une solution esthétique et durable pour vos aménagements extérieurs. Conçus pour résister aux conditions climatiques extrêmes et aux charges lourdes, ils sont parfaits pour les allées, les terrasses, les parkings et les places publiques.',
        'prix' => '12,000 FC',
        'images' => [
            'main' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTp31Y6W1qG-T7jhkCUMy8Dquyvy_8RByhHFA&s',
            'thumbnails' => [
                'https://pavesconcept.ca/cdn/shop/files/AVANTAGES-Pave-en-beton-Interpave-5_555x.jpg?v=1686924317',
                'https://pavesconcept.ca/cdn/shop/products/permeable-cobblestone-paver-pure-paves-permeables-a00416_05_212-hdr_ppt_330x.jpg?v=1616167251',
                'https://www.lesmateriaux.fr/uploads/products/thumbnail/2884-hb-pave-capri.jpg'
            ]
        ],
        'caracteristiques' => [
            'Installation facile et rapide',
            'Grande résistance mécanique',
            'Diverses couleurs et formes'
        ]
    ],
    4 => [
        'nom' => 'Ciment de Haute Qualité',
        'description' => 'Notre ciment de haute qualité est conçu pour garantir la durabilité et la solidité de vos constructions. Idéal pour les fondations, les murs porteurs, les dalles et bien plus encore. Sa composition assure une excellente résistance mécanique et une prise régulière, même dans des conditions climatiques difficiles.',
        'prix' => '15,000 FC',
        'images' => [
            'main' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS04rsYgshTUCPELm9yrt1YXyASfJrBwGUZ2Q&s',
            'thumbnails' => [
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSMahHUZncqrhCl_DBJUQMCikxiX1NTFyiesg&s',
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTTKxe-nNzyM5DzNORnfABqJI0WwtYNrwlclQ&s',
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSDBVH3ykxmZ3XFRkPLwyueOO2pSblt90da8Q&s'
            ]
        ],
        'caracteristiques' => [
            'Haute résistance à la compression',
            'Convient à tous types de chantiers',
            'Conforme aux normes internationales'
        ]
    ]
];

$produit_actuel = $produits[$produit_id] ?? $produits[1];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des produits - <?= $produit_actuel['nom'] ?></title>

    <!--============== Google Fonts =============-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!--============== Remixicons  =============-->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">

    <!--============== Main CSS  =============-->
    <link rel="stylesheet" href="assets/css/main.css">

    <!--============== Tailwind css Link =====-->
    <script src="https://cdn.tailwindcss.com"></script>

    <!--============= Font Awesome (icônes) =====-->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>

<body>

    <!--======================= Header =============================-->
    <header id="header" class="header">
        <nav class="nav container">
            <a href="index.html" class="nav__brand"><span>Johnson</span> Jr Construction</a>
            <div id="nav-menu" class="nav__menu">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Accueil</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Services</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">À propos</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Projets</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Actualités</a>
                    </li>
                </ul>
            </div>

            <div class="nav__buttons">
                <!-- Connexion -->
                <div class="nav__icon nav__auth">
                    <a href="#" class="nav__link" aria-label="Connexion" title="Connexion">
                        <i class="ri-login-box-line"></i>
                    </a>
                </div>

                <!-- Inscription -->
                <div class="nav__icon nav__auth">
                    <a href="#" class="nav__link" aria-label="Inscription" title="Inscription">
                        <i class="ri-user-add-line"></i>
                    </a>
                </div>


                <!--======================= Formulaire Connexion ===================-->
                <div id="login-modal" class="modal <?php if ($error_login || isset($_POST['login']))
                    echo '';
                else
                    echo 'hidden'; ?> fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white p-8 rounded-lg w-full max-w-md md:max-w-lg lg:max-w-xl">
                        <h2 class="text-2xl font-bold mb-6 uppercase">Connexion</h2>

                        <?php if ($error_login): ?>
                            <p class="text-red-600 mb-4"><?= $error_login ?></p>
                        <?php elseif (isset($success_login)): ?>
                            <p class="text-green-600 mb-4"><?= $success_login ?></p>
                        <?php endif; ?>

                        <form method="POST" action="" class="space-y-4">
                            <input type="email" name="email" placeholder="Email" class="w-full p-3 border rounded-md">
                            <input type="password" name="mot_de_passe" placeholder="Mot de passe"
                                class="w-full p-3 border rounded-md">
                            <button type="submit" name="login"
                                class="w-full bg-[#053d36] text-white py-3 rounded-md hover:bg-[#811313] transition">Se
                                connecter</button>
                        </form>
                        <button class="mt-4 text-gray-500 hover:text-red-700"
                            onclick="closeModal('login-modal')">Fermer</button>
                    </div>
                </div>

                <!--======================= Formulaire Inscription ===================-->
                <div id="register-modal" class="modal <?php if ($error_register || $success_register)
                    echo '';
                else
                    echo 'hidden'; ?> fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white p-8 rounded-lg w-full max-w-md md:max-w-lg lg:max-w-xl">
                        <h2 class="text-2xl font-bold mb-6 uppercase">Inscription</h2>

                        <?php if ($error_register): ?>
                            <p class="text-red-600 mb-4"><?= $error_register ?></p>
                        <?php elseif ($success_register): ?>
                            <p class="text-green-600 mb-4"><?= $success_register ?></p>
                        <?php endif; ?>

                        <form method="POST" action="" class="space-y-4">
                            <input type="text" name="nom" placeholder="Nom complet"
                                class="w-full p-3 border rounded-md">
                            <input type="email" name="email" placeholder="Email" class="w-full p-3 border rounded-md">
                            <input type="password" name="mot_de_passe" placeholder="Mot de passe"
                                class="w-full p-3 border rounded-md">
                            <input type="password" name="confirmation" placeholder="Confirmer mot de passe"
                                class="w-full p-3 border rounded-md">
                            <button type="submit" name="register"
                                class="w-full bg-[#053d36] text-white py-3 rounded-md hover:bg-[#811313] transition">S'inscrire</button>
                        </form>
                        <button class="mt-4 text-gray-500 hover:text-red-700"
                            onclick="closeModal('register-modal')">Fermer</button>
                    </div>
                </div>


                <script>
                    // ouvrir modales
                    document.querySelectorAll('.ri-login-box-line, .ri-user-add-line').forEach(btn => {
                        btn.addEventListener('click', e => {
                            e.preventDefault(); // éviter le saut de page
                            if (btn.classList.contains('ri-login-box-line')) {
                                document.getElementById('login-modal').classList.remove('hidden');
                            } else {
                                document.getElementById('register-modal').classList.remove('hidden');
                            }
                        });
                    });

                    function closeModal(id) {
                        document.getElementById(id).classList.add('hidden');
                    }
                </script>



                <!-- Panier -->
                <div class="nav__icon shop__icon">
                    <i class="ri-shopping-bag-line"></i>
                    <span class="shop__number">0</span>
                </div>

                <!-- Toggle menu -->
                <div class="nav__icon nav__toggle">
                    <i id="nav-toggle" class="ri-menu-3-line"></i>
                </div>

                <!-- Toggle sidebar -->
                <div class="nav__icon sidebar__toggle">
                    <i id="sidebar-toggle" class="ri-more-2-fill"></i>
                </div>
            </div>
        </nav>
    </header>

    <main class="main">
        <!--======================= Sidebar =============================-->
        <div id="sidebar" class="sidebar">
            <div class="sidebar__header">
                <h2 class="sidebar__title">Contact</h2>
                <p class="sidebar__description">Johnson Construction — Experts en construction résidentielle et
                    commerciale.
                    De la conception à la livraison, nous construisons avec rigueur et sécurité.</p>
            </div>
            <div class="sidebar__content">
                <ul class="sidebar__list">
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Adresse :</span>
                        <span>L'shi, 15 rue des Constructeurs</span>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Horaires :</span>
                        <span>Lu–Ve 8:00 - 18:00</span>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Appelez-nous :</span>
                        <a href="tel0977199714">+243 977 199 714</a>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Email :</span>
                        <a href="mailto:contact@johnsonconstruction.com">contact@johnsonconstruction.com</a>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Suivez-nous :</span>
                        <ul class="social__list">
                            <li class="social__item">
                                <a href="javascript:void(0)" class="social__link">
                                    <i class="ri-facebook-fill"></i>
                                </a>
                            </li>
                            <li class="social__item">
                                <a href="javascript:void(0)" class="social__link">
                                    <i class="ri-linkedin-fill"></i>
                                </a>
                            </li>
                            <li class="social__item">
                                <a href="javascript:void(0)" class="social__link">
                                    <i class="ri-twitter-fill"></i>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>




        <!--======================= Section Détails Produit ============================-->
        <section class="container mx-auto px-6 lg:px-16 py-16 mt-32">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                <!-- Galerie Images -->
                <div class="space-y-6">
                    <div class="rounded-2xl overflow-hidden shadow-xl">
                        <img src="<?= $produit_actuel['images']['main'] ?>" alt="<?= $produit_actuel['nom'] ?>"
                            class="w-full h-96 object-cover hover:scale-105 transition-transform duration-500">
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <?php foreach ($produit_actuel['images']['thumbnails'] as $thumbnail): ?>
                            <img src="<?= $thumbnail ?>" alt="<?= $produit_actuel['nom'] ?>"
                                class="rounded-lg h-32 w-full object-cover hover:scale-105 transition-transform duration-300 cursor-pointer">
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Détails Texte -->
                <div>
                    <h2 class="text-4xl font-extrabold text-[#053d36] mb-4"><?= $produit_actuel['nom'] ?></h2>
                    <p class="text-gray-700 text-xl leading-relaxed mb-6">
                        <?= $produit_actuel['description'] ?>
                    </p>

                    <ul class="space-y-2 mb-8 text-gray-600">
                        <?php foreach ($produit_actuel['caracteristiques'] as $caracteristique): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <?= $caracteristique ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="flex items-center gap-6">
                        <span class="text-3xl font-bold text-red-700"><?= $produit_actuel['prix'] ?></span>
                        <a href="#"
                            class="bg-red-700 hover:bg-[#053d36] text-white px-8 py-3 rounded-lg font-semibold shadow-md transition">
                            Commander maintenant
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!--======================= Produits similaires ============================-->
        <section class="bg-gray-100 py-16">
            <div class="text-center mb-10">
                <h3 class="text-3xl font-bold text-gray-800">Produits Similaires</h3>
                <p class="text-gray-500 mt-2">Découvrez d'autres matériaux de la même catégorie</p>
            </div>

            <div class="container mx-auto px-6 lg:px-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

                <!-- Produit Similaire 1 - Gravier -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_uHhxesbL0X7U1sFuy5LH9frwGBaaWfCSwQ&s"
                        alt="Gravier" class="w-full h-56 object-cover">
                    <div class="p-5">
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">Gravier Premium</h4>
                        <p class="text-gray-600 mb-4 text-xl">Gravier de différentes tailles pour vos besoins
                            spécifiques.</p>
                        <a href="details-produits.php?id=1"
                            class="inline-block bg-red-700 hover:bg-[#053d36] text-white px-5 py-2 rounded-lg font-medium transition">
                            Voir détails
                        </a>
                    </div>
                </div>

                <!-- Produit Similaire 2 - Carreaux -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <img src="https://franceschini.fr/wp-content/uploads/2017/09/carreaux-ciment-1.jpg" alt="Carreaux"
                        class="w-full h-56 object-cover">
                    <div class="p-5">
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">Carreaux Céramique</h4>
                        <p class="text-gray-600 mb-4 text-xl">Carreaux pour sols et murs intérieurs/extérieurs.</p>
                        <a href="details-produits.php?id=2"
                            class="inline-block bg-red-700 hover:bg-[#053d36] text-white px-5 py-2 rounded-lg font-medium transition">
                            Voir détails
                        </a>
                    </div>
                </div>

                <!-- Produit Similaire 3 - Pavés -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTp31Y6W1qG-T7jhkCUMy8Dquyvy_8RByhHFA&s"
                        alt="Pavés" class="w-full h-56 object-cover">
                    <div class="p-5">
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">Pavés Autobloquants</h4>
                        <p class="text-gray-600 mb-4 text-xl">Pavés décoratifs pour vos aménagements extérieurs.</p>
                        <a href="details-produits.php?id=3"
                            class="inline-block bg-red-700 hover:bg-[#053d36] text-white px-5 py-2 rounded-lg font-medium transition">
                            Voir détails
                        </a>
                    </div>
                </div>

                <!-- Produit Similaire 4 - Ciment -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <img src="https://www.constructionlabrique.com/wp-content/uploads/2016/12/coulee-ciment-labrique.jpeg"
                        alt="Pavés" class="w-full h-56 object-cover">
                    <div class="p-5">
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">Ciments</h4>
                        <p class="text-gray-600 mb-4 text-xl">Ciment décoratifs pour vos aménagements extérieurs.</p>
                        <a href="details-produits.php?id=4"
                            class="inline-block bg-red-700 hover:bg-[#053d36] text-white px-5 py-2 rounded-lg font-medium transition">
                            Voir détails
                        </a>
                    </div>
                </div>

            </div>
        </section>




        <!--======================= Footer ============================-->
        <footer class="footer mt-16">
            <div class="d-grid footer__wrapper container">
                <div class="footer__content">
                    <h4 class="footer__brand"><span>Johnson</span> Construction</h4>
                    <p class="footer__description">Des constructions durables, réalisées avec professionnalisme et
                        transparence.</p>
                    <ul class="social__list footer__list">
                        <li class="social__item">
                            <a href="javascript:void(0)" class="social__link">
                                <i class="ri-facebook-fill"></i>
                            </a>
                        </li>
                        <li class="social__item">
                            <a href="javascript:void(0)" class="social__link">
                                <i class="ri-linkedin-fill"></i>
                            </a>
                        </li>
                        <li class="social__item">
                            <a href="javascript:void(0)" class="social__link">
                                <i class="ri-twitter-fill"></i>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="footer__content">
                    <h4 class="footer__title">Nos Services</h4>
                    <ul class="footer__list">
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Résidentiel</a>
                        </li>
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Commercial</a>
                        </li>
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Industriel</a>
                        </li>
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Rénovation</a>
                        </li>
                    </ul>
                </div>
                <div class="footer__content">
                    <h4 class="footer__title">Liens rapides</h4>
                    <ul class="footer__list">
                        <li class="footer__item">
                            <a href="#feature" class="footer__link">Services</a>
                        </li>
                        <li class="footer__item">
                            <a href="#about" class="footer__link">À propos</a>
                        </li>
                        <li class="footer__item">
                            <a href="#testimonial" class="footer__link">Témoignages</a>
                        </li>
                        <li class="footer__item">
                            <a href="#blog" class="footer__link">Actualités</a>
                        </li>
                    </ul>
                </div>
                <div class="footer__content">
                    <h4 class="footer__title">Support</h4>
                    <ul class="footer__list">
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Contact</a>
                        </li>
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Conditions</a>
                        </li>
                        <li class="footer__item">
                            <a href="javascript:void(0)" class="footer__link">Vie privée</a>
                        </li>
                    </ul>
                </div>
            </div>
            <p class="footer__copyright">&copy; 2025 Johnson Construction. Tous droits réservés</p>
        </footer>


        <!--=================== ScrollReveal ==================-->
        <script src="assets/js/scrollreveal.min.js"></script>

        <!--=================== Mixitup  ====================-->
        <script src="assets/js/mixitup.min.js"></script>

        <!--=================== Main JS ====================-->
        <script src="assets/js/main.js"> </script>

        <!--=================== Panier JS ====================-->
        <script src="assets/js/panier.js"></script>
        <!--=================== Search JS ====================-->
        <script src="assets/js/search.js"> </script>

        <!-- Modal Panier -->
        <div id="panierModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full"
            style="z-index:1000;">
            <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-xl font-bold leading-6 font-medium text-gray-900">Panier</h3>

                    <!-- Conteneur messages -->
                    <div id="panierMessage"></div>

                    <div class="mt-2 px-7 py-3" id="panierContenu">
                        <!-- Le contenu du panier sera injecté ici -->
                    </div>

                    <div class="mt-4 flex flex-col gap-3 border-t pt-4">
                        <div class="flex justify-between items-center">
                            <p class="font-bold">Total: <span id="panierTotal">0</span> €</p>
                            <div class="flex gap-2">
                                <button id="btnCommander"
                                    class="bg-green-600 hover:bg-green-700 transition text-white px-4 py-2 rounded-lg">
                                    Commander
                                </button>
                                <button id="fermerPanier"
                                    class="bg-red-600 hover:bg-[#053d36] transition text-white px-4 py-2 rounded-lg">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Animation fadeIn -->
        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-5px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fadeIn {
                animation: fadeIn 0.4s ease-out;
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