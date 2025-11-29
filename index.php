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
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Johnson Jr Construction</title>

    <!--============== Google Fonts =============-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="assets/img/logo.jpg" type="image/x-icon">

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
        <nav class="nav container ">
            <a href="index.html" class="nav__brand p-4"><span>Johnson</span> Jr Construction</a>
            <div id="nav-menu" class="nav__menu">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="#home" class="nav__link">Accueil</a>
                    </li>
                    <li class="nav__item">
                        <a href="#feature" class="nav__link">Services</a>
                    </li>
                    <li class="nav__item">
                        <a href="#about" class="nav__link">À propos</a>
                    </li>
                    <li class="nav__item">
                        <a href="#menu" class="nav__link">Projets</a>
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
                                class="w-full bg-[#053d36] text-white py-3 rounded-md hover:bg-[#811313] transition">S’inscrire</button>
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
                        <span>Camps scout :avenue Lupopo/7/kassapa/ annexe</span>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Horaires :</span>
                        <span>Lundi–Vendredi 8:00 - 18:00</span>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Appelez-nous :</span>
                        <a href="tel0977199714">+243 975 413 369</a>
                    </li>
                    <li class="sidebar__item">
                        <span class="sidebar__subtitle">Email :</span>
                        <a href="mailto:contact@johnsonconstruction.com">johnson31@outlook.fr</a>
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

        <!--======================= Home =============================-->
        <section id="home" class="home">
            <div class="d-grid home__wrapper container">
                <div class="home__content">
                    <h1 class="home__title">Construisons l'avenir, ensemble</h1>
                    <p class="home__description">Johnson Construction fournit des solutions de construction sûres et
                        durables.
                        Nous réalisons des projets résidentiels, commerciaux et industriels avec un engagement qualité.
                    </p>
                    <button onclick="verifierConnexion()"
                        class="bg-[#b60c0c] hover:bg-[#053d36] rounded-lg text-white px-6 py-6 font-medium transition duration-300 cursor-pointer">
                        Voir mes commandes
                    </button>
                </div>
            </div>
        </section>

        <!-- Pop-up de connexion -->
        <div id="loginPopup"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div
                class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-auto transform scale-95 opacity-0 transition-all duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Connexion requise</h3>
                        <button onclick="fermerPopupConnexion()"
                            class="text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="flex items-center space-x-4 mb-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="ri-user-fill text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-gray-700 font-medium">Vous devez être connecté pour accéder à vos commandes.
                            </p>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start space-x-3">
                            <i class="ri-information-line text-blue-600 text-lg mt-0.5"></i>
                            <p class="text-blue-800 text-sm">
                                Connectez-vous pour consulter l'historique de vos commandes et suivre vos projets en
                                cours.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 justify-end">
                        <button onclick="fermerPopupConnexion()"
                            class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors duration-200">
                            Plus tard
                        </button>
                        <a href="#login-modal"
                            class="px-6 py-3 bg-[#b60c0c] hover:bg-[#053d36] text-white rounded-lg font-medium text-center transition-colors duration-200">
                            Se connecter
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Variable globale pour l'état de connexion (à remplacer par votre logique réelle)
            <?php if (isset($_SESSION['user_id'])): ?>
                const estConnecte = true;
            <?php else: ?>
                const estConnecte = false;
            <?php endif; ?>

            function verifierConnexion() {
                if (estConnecte) {
                    // Utilisateur connecté - redirection vers la page des commandes
                    window.location.href = 'mes-commandes.php';
                } else {
                    // Utilisateur non connecté - afficher le pop-up
                    afficherPopupConnexion();
                }
            }

            function afficherPopupConnexion() {
                const popup = document.getElementById('loginPopup');
                const popupContent = popup.querySelector('div.bg-white');

                popup.classList.remove('hidden');

                // Animation d'entrée
                setTimeout(() => {
                    popupContent.classList.remove('scale-95', 'opacity-0');
                    popupContent.classList.add('scale-100', 'opacity-100');
                }, 100);
            }

            function fermerPopupConnexion() {
                const popup = document.getElementById('loginPopup');
                const popupContent = popup.querySelector('div.bg-white');

                // Animation de sortie
                popupContent.classList.remove('scale-100', 'opacity-100');
                popupContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    popup.classList.add('hidden');
                }, 300);
            }

            // Fermer le pop-up en cliquant à l'extérieur
            document.getElementById('loginPopup').addEventListener('click', function (e) {
                if (e.target === this) {
                    fermerPopupConnexion();
                }
            });

            // Fermer avec la touche Échap
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    fermerPopupConnexion();
                }
            });
        </script>

        <!--======================= feature =============================-->
        <section id="feature" class="section feature">
            <div class="section__header">
                <span class="section__subtitle">Nos Atouts</span>
                <h2 class="section__title">Pourquoi nous choisir ?</h2>
            </div>
            <div class="d-grid feature__wrapper container">
                <!------- feature card 1 -------->
                <div class="feature__card">
                    <div class="feature__icon">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                    <h3 class="feature__title">Projets sur mesure</h3>
                    <p class="feature__description">Conception et exécution adaptées à vos besoins — plans, permis et
                        coordination totale.</p>
                </div>
                <!------- feature card 2 -------->
                <div class="feature__card">
                    <div class="feature__icon">
                        <i class="ri-takeaway-line"></i>
                    </div>
                    <h3 class="feature__title">Respect des délais</h3>
                    <p class="feature__description">Planification précise, gestion de chantier rigoureuse et livraison
                        dans les temps.</p>
                </div>
                <!------- feature card 3 -------->
                <div class="feature__card">
                    <div class="feature__icon">
                        <i class="ri-medal-2-line"></i>
                    </div>
                    <h3 class="feature__title">Qualité & sécurité</h3>
                    <p class="feature__description">Matériaux certifiés, équipes qualifiées et procédures de sécurité
                        strictes.</p>
                </div>
            </div>
        </section>

        <!--======================= About ============================-->
        <section id="about" class="section about">
            <div
                class="d-grid about__wrapper container mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center px-4">
                <div class="about__content">
                    <span class="about__subtitle">À propos</span>
                    <h2 class="about__title">Expertise en construction depuis 2010</h2>
                    <p class="about__description">Johnson Jr Construction est une entreprise familiale spécialisée dans
                        la
                        construction clé en main.
                        Nous combinons savoir-faire, innovation et respect des normes pour livrer des bâtiments durables
                        et performants.
                        <br><br>
                        Notre équipe s'engage à optimiser les coûts, réduire les délais et assurer une communication
                        claire à chaque étape.
                    </p>
                    <a href="javascript:void(0)" class="btn btn--primary">Notre histoire</a>
                </div>
                <img src="assets/img/logo.jpg" alt="équipe de construction" class="about__img">
            </div>
        </section>

        <!--======================= Nos Services de Location ============================-->
        <section id="location" class="bg-gradient-to-b from-gray-50 to-white py-20">
            <div class="text-center mb-16">
                <span class="text-x font-semibold tracking-widest text-red-700 uppercase">Nos Services</span>
                <h2 class="mt-2 text-4xl font-extrabold text-gray-800">Location de Bâtiments & Services</h2>
                <p class="mt-3 max-w-2xl mx-auto text-gray-500">
                    Découvrez notre gamme complète de services immobiliers et de construction pour tous vos projets.
                </p>
            </div>

            <div class="container mx-auto px-6 lg:px-12">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">

                    <!-- Bâtiment Industriel -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTUZykXWyPhpeEHDLC899dOeA0TK0INIPOijg&s"
                                alt="Bâtiment Industriel"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Bâtiment Industriel</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Locaux industriels adaptés à vos activités de
                                production, entreposage et logistique.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Bâtiment Résidentiel -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://img.freepik.com/photos-gratuite/facade-rangee-immeubles-appartements-contre-ciel-bleu-clair_181624-17998.jpg?semt=ais_incoming&w=740&q=80"
                                alt="Bâtiment Résidentiel"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Bâtiment Résidentiel</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Appartements, villas et maisons de standing
                                pour votre confort familial.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Bâtiment Commercial -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Bâtiment Commercial"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Bâtiment Commercial</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Espaces commerciaux stratégiques pour
                                développer votre activité.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Génie Civil -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Génie Civil"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Génie Civil</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Expertise en construction d'infrastructures et
                                ouvrages d'art.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Bureau d'Architecte -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Bureau d'Architecte"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Bureau d'Architecte</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Conception et design architectural sur mesure
                                pour vos projets.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Installation & Services -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.helloartisan.com/forward/file/0/6/3/7/30998e706237e1d005a256e842d9710bc67d7360/panneaux-photovoltaiques-aides-prix-installation-jpg.jpg"
                                alt="Installation & Services"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Installation & Services</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Services complets d'installation, maintenance
                                et dépannage pour tous vos besoins.</p>

                            <!-- Icônes des services -->
                            <div class="mt-4 grid grid-cols-3 gap-4 py-3">
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Photovoltaïque</span>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Maintenance</span>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Dépannage</span>
                                </div>
                            </div>

                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Services Ménagers -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Services Ménagers"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Services Ménagers</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Personnel qualifié pour tous vos besoins
                                domestiques et familiaux.</p>

                            <!-- Icônes des services ménagers -->
                            <div class="mt-4 grid grid-cols-2 gap-3 py-3">
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Garde</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Domestique</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Coursier</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Jardinier</span>
                                </div>
                                <div class="flex items-center space-x-2 col-span-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Nounou</span>
                                </div>
                            </div>

                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Vente et Achat -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Vente et Achat"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Vente et Achat</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Parcelles et concessions disponibles pour vos
                                projets immobiliers.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Soudure et Fabrication -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://matriceriasdelcentro.com/wp-content/uploads/2019/04/soldadura.jpg"
                                alt="Soudure et Fabrication"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Soudure et Fabrication</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Fabrication sur mesure de portes, fenêtres,
                                barrières et charpentes.</p>

                            <!-- Icônes des services de soudure -->
                            <div class="mt-4 grid grid-cols-2 gap-4 py-3">
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Porte</span>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Fenêtre</span>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Barrière</span>
                                </div>
                                <div class="flex flex-col items-center text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-700 mb-2"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">Charpente</span>
                                </div>
                            </div>

                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Équipement et Nettoyage -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Équipement et Nettoyage"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Équipement et Nettoyage</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Services d'équipement et de nettoyage pour
                                tous types de locaux.</p>

                            <!-- Icônes des services d'équipement -->
                            <div class="mt-4 grid grid-cols-2 gap-3 py-3">
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Bâtiment</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Industrie</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Bureau</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Nettoyage</span>
                                </div>
                            </div>

                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Maison et Location à Louer -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
                                alt="Maison et Location à Louer"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Maison et Location à Louer</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Appartements, locaux et maisons commerciales
                                disponibles à la location.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Mariage -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://administration.ouragan.cd/wp-content/uploads/2025/07/1000_F_68487456_xcCRBfnLaxiYPnY3G8rLDWexoRb5vTXi.jpg"
                                alt="Mariage"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Mariage</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Organisation complète de mariages et
                                événements spéciaux.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>
                    <!--Peinture-->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://media.istockphoto.com/id/519251233/fr/photo/3-d-blanc-interor-dorange-chambre-%C3%A0-coucher.jpg?s=612x612&w=0&k=20&c=GO8eOXKER22dvMaaT4uP1qv1h0Zq79mVBbIh_SjOMoA="
                                alt="Peinture"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Peinture</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Services de peinture intérieure et extérieure
                                pour embellir vos espaces.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Plafond -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://www.mon-platrier.fr/wp-content/uploads/2018/01/plafond_stuf-768x0-c-default.jpg"
                                alt="Équipement et Nettoyage"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Plafond</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Trouvez tout type d'équipements chez nous.</p>

                            <!-- Icônes de plafond -->
                            <div class="mt-4 grid grid-cols-2 gap-3 py-3">
                                <!-- Gyproc -->
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Gyproc</span>
                                </div>

                                <!-- Timerlite -->
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Timerlite</span>
                                </div>

                                <!-- Multiplex -->
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 3v18M15 3v18" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Multiplex</span>
                                </div>

                                <!-- En beton -->
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">En beton</span>
                                </div>
                            </div>

                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>

                    <!-- Carrelage -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQSGVhM9P8jMZ2c2uRZrjGYXxS-8JCeYH9zNQ&s"
                                alt="Carrelage moderne"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Carrelage</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Pose et rénovation de carrelage pour sols et
                                murs, avec un large choix de matériaux et de designs.</p>
                            <div class="mt-5">
                                <a href="details-produits.php"
                                    class="inline-flex items-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-2.5 rounded-lg font-medium shadow-md transition-all duration-300">
                                    Voir détails
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>


                </div>
            </div>
        </section>


        <!--======================= Menu (Projets) ============================-->
        <section id="menu" class="section menu bg-gradient-to-b from-gray-50 to-white py-20">
            <div class="text-center mb-16">
                <span class="text-x font-semibold tracking-widest text-red-700 uppercase">Nos Produits</span>
                <h2 class="mt-2 text-4xl font-extrabold text-gray-800">Matériaux de Construction</h2>
                <p class="mt-3 max-w-2xl mx-auto text-gray-500">
                    Cliquer sur un produit pour votre réservation
                </p>
            </div>

            <div class="menu__filter flex flex-wrap justify-center gap-3 mb-12 px-6">
                <span
                    class="menu__item menu__item--active bg-red-700 text-white px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-[#053d36]"
                    data-filter="all">Tous</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Ciment">Ciment</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Bloc-ciment">Bloc-ciment</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Gravier">Gravier</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Pavé">Pavé</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Carreaux">Carreaux</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Gyproc">Gyproc</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Omega">Omega</span>
                <span
                    class="menu__item bg-white text-gray-700 border border-gray-300 px-5 py-2.5 rounded-lg font-medium cursor-pointer transition-all duration-300 hover:bg-gray-50 hover:border-red-700"
                    data-filter="Chanel">Chanel</span>
            </div>

            <div class="container mx-auto px-6 lg:px-12">
                <?php
                try {
                    // Récupérer TOUS les produits simplement
                    $stmt = $conn->prepare("
                SELECT id, nom, description, prix, devise, poids, quantite, image, categorie
                FROM produits 
                WHERE quantite > 0
                ORDER BY id DESC
            ");
                    $stmt->execute();
                    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($produits) > 0) {
                        echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8" id="productsGrid">';

                        // Afficher tous les produits
                        foreach ($produits as $row) {
                            afficherProduit($row, $conn);
                        }

                        echo '</div>';

                        // Message général si aucun produit
                        echo '
                <div class="empty-all-message hidden text-center py-16">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucun produit disponible</h3>
                    <p class="text-gray-500">Les produits seront bientôt disponibles.</p>
                </div>
                ';
                    } else {
                        echo '
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucun produit disponible</h3>
                    <p class="text-gray-500">Les produits seront bientôt disponibles.</p>
                </div>
                ';
                    }
                } catch (PDOException $e) {
                    echo "
            <div class='text-center py-8'>
                <div class='bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto'>
                    <i class='fas fa-exclamation-triangle text-red-500 text-2xl mb-3'></i>
                    <h3 class='text-lg font-semibold text-red-800 mb-2'>Erreur de chargement</h3>
                    <p class='text-red-600 text-sm'>Impossible de charger les produits. Veuillez réessayer.</p>
                </div>
            </div>
            ";
                }

                // Fonction pour afficher un produit
                function afficherProduit($row, $conn)
                {
                    $nomProduit = htmlspecialchars($row['nom']);
                    $description = htmlspecialchars($row['description'] ?? '');
                    $prix = floatval($row['prix']);
                    $devise = htmlspecialchars($row['devise'] ?? 'USD');
                    $poids = htmlspecialchars($row['poids'] ?? '');
                    $quantite = intval($row['quantite']);
                    $image = htmlspecialchars($row['image'] ?? '');

                    // Déterminer le filtre basé sur le NOM EXACT du produit
                    $filtre = 'autre';
                    $filtres = ['Ciment', 'Bloc-ciment', 'Gravier', 'Pavé', 'Carreaux', 'Gyproc', 'Omega', 'Chanel'];
                    foreach ($filtres as $f) {
                        if (stripos($nomProduit, $f) !== false) {
                            $filtre = $f;
                            break;
                        }
                    }

                    // Formater le prix
                    $prixFormate = number_format($prix, $devise === 'USD' ? 2 : 0, ',', ' ');

                    // Gestion du chemin d'image
                    $imagePath = "";
                    if (!empty($image)) {
                        $possiblePaths = [
                            "admin/uploads/" . $image,
                            "admin/" . $image,
                            "../admin/uploads/" . $image,
                            "../admin/" . $image,
                            "uploads/" . $image,
                            $image
                        ];

                        foreach ($possiblePaths as $path) {
                            if (file_exists($path) && is_file($path)) {
                                $imagePath = $path;
                                break;
                            }
                        }
                    }
                    ?>
                    <div class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 product-item"
                        data-filter="<?php echo $filtre; ?>" data-name="<?php echo htmlspecialchars($nomProduit); ?>">

                        <?php if (!empty($imagePath) && file_exists($imagePath)): ?>
                            <div class="h-56 overflow-hidden">
                                <img src="<?php echo $imagePath; ?>" alt="<?php echo $nomProduit; ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            </div>
                        <?php else: ?>
                            <div class="h-56 bg-gray-100 flex items-center justify-center">
                                <div class="text-gray-400 text-center p-4">
                                    <i class="fas fa-box text-4xl mb-2"></i>
                                    <p class="text-sm font-medium"><?php echo $nomProduit; ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="p-6">
                            <h3
                                class="text-xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300 mb-3 line-clamp-2">
                                <?php echo $nomProduit; ?>
                            </h3>

                            <?php if (!empty($description)): ?>
                                <p class="text-gray-600 leading-relaxed mb-3 line-clamp-2 text-sm">
                                    <?php echo $description; ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($poids)): ?>
                                <div class="text-sm text-gray-600 mb-3 flex items-center gap-2">
                                    <i class="fas fa-weight-hanging text-red-700"></i>
                                    <span class="font-medium"><?php echo $poids; ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="rating flex items-center mb-4">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="rating__star text-yellow-400">
                                        <i class="ri-star-fill text-sm"></i>
                                    </span>
                                <?php endfor; ?>
                            </div>

                            <div class="flex justify-between items-center mb-4">
                                <span class="text-lg font-bold text-gray-900">
                                    <?php echo $prixFormate; ?>
                                    <span
                                        class="font-semibold <?php echo $devise === 'USD' ? 'text-green-600' : 'text-blue-600'; ?> text-sm">
                                        <?php echo $devise === 'USD' ? '$' : 'FC'; ?>
                                    </span>
                                </span>

                                <span
                                    class="text-sm px-3 py-1 rounded-full font-medium <?php echo $quantite > 10 ? 'bg-green-100 text-green-800' : ($quantite > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    Stock: <?php echo $quantite; ?>
                                </span>
                            </div>

                            <button
                                onclick="ajouterAuPanier('<?php echo addslashes($nomProduit); ?>', <?php echo $prix; ?>, '<?php echo $devise; ?>', '<?php echo addslashes($poids); ?>', <?php echo $quantite; ?>, '<?php echo addslashes($imagePath); ?>')"
                                class="w-full inline-flex items-center justify-center gap-2 bg-red-700 hover:bg-[#053d36] text-white px-5 py-3 rounded-lg font-medium shadow-md transition-all duration-300 group-hover:shadow-lg <?php echo $quantite === 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo $quantite === 0 ? 'disabled' : ''; ?>>
                                <i class="ri-shopping-cart-line"></i>
                                <?php echo $quantite === 0 ? 'Rupture de stock' : 'Commander'; ?>
                            </button>
                        </div>

                        <div
                            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-[#053d36] opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </section>

        <style>
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            /* Animation pour le filtrage */
            .product-item {
                display: block;
                animation: fadeIn 0.3s ease-in-out;
            }

            .product-item.hidden {
                display: none;
            }

            .empty-filter-message {
                display: none;
            }

            .empty-filter-message.show {
                display: block;
                animation: fadeIn 0.3s ease-in-out;
            }

            .empty-all-message {
                display: none;
            }

            .empty-all-message.show {
                display: block;
                animation: fadeIn 0.3s ease-in-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Adaptation responsive pour les petits écrans */
            @media (max-width: 640px) {
                .menu__filter {
                    gap: 0.5rem;
                }

                .menu__item {
                    padding: 0.5rem 1rem;
                    font-size: 0.875rem;
                }
            }

            @media (max-width: 480px) {
                .menu__filter {
                    gap: 0.25rem;
                }

                .menu__item {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.75rem;
                }
            }
        </style>

        <script>
            // Script de filtrage par NOM du produit - VERSION RAPIDE
            document.addEventListener('DOMContentLoaded', function () {
                const filterItems = document.querySelectorAll('.menu__item');
                const productItems = document.querySelectorAll('.product-item');
                const productsGrid = document.getElementById('productsGrid');
                const emptyAllMessage = document.querySelector('.empty-all-message');
                const menuWrapper = document.querySelector('.menu__wrapper');

                // Messages pour les filtres vides
                const filterMessages = {
                    'Ciment': 'Aucun produit au nom de "Ciment" trouvé',
                    'Bloc-ciment': 'Aucun produit au nom de "Bloc-ciment" trouvé',
                    'Gravier': 'Aucun produit au nom de "Gravier" trouvé',
                    'Pavé': 'Aucun produit au nom de "Pavé" trouvé',
                    'Carreaux': 'Aucun produit au nom de "Carreaux" trouvé',
                    'Gyproc': 'Aucun produit au nom de "Gyproc" trouvé',
                    'Omega': 'Aucun produit au nom de "Omega" trouvé',
                    'Chanel': 'Aucun produit au nom de "Chanel" trouvé'
                };

                function filterProducts(filterValue) {
                    // Effet de loading léger
                    if (menuWrapper) menuWrapper.classList.add('loading');

                    // Utiliser setTimeout pour permettre le rendu
                    setTimeout(() => {
                        let hasVisibleProducts = false;
                        let hasProductsInFilter = false;

                        // Cacher tous les produits d'abord
                        productItems.forEach(item => {
                            item.classList.add('hidden');
                        });

                        // Cacher les messages
                        if (emptyAllMessage) {
                            emptyAllMessage.classList.remove('show');
                        }

                        if (filterValue === 'all') {
                            // Afficher TOUS les produits
                            productItems.forEach(item => {
                                item.classList.remove('hidden');
                                hasVisibleProducts = true;
                            });

                            // Si aucun produit visible, afficher message général
                            if (!hasVisibleProducts && emptyAllMessage) {
                                emptyAllMessage.classList.add('show');
                            }
                        } else {
                            // Filtrer par NOM du produit
                            productItems.forEach(item => {
                                const itemFilter = item.getAttribute('data-filter');
                                if (itemFilter === filterValue) {
                                    item.classList.remove('hidden');
                                    hasVisibleProducts = true;
                                    hasProductsInFilter = true;
                                }
                            });

                            // Si aucun produit dans ce filtre, afficher message spécifique
                            if (!hasProductsInFilter) {
                                showEmptyFilterMessage(filterValue);
                            }
                        }

                        if (menuWrapper) menuWrapper.classList.remove('loading');
                    }, 50);
                }

                function showEmptyFilterMessage(filterValue) {
                    // Supprimer tout message existant
                    const existingMessage = document.querySelector('.empty-filter-message');
                    if (existingMessage) {
                        existingMessage.remove();
                    }

                    // Créer le nouveau message
                    const messageHTML = `
                <div class="empty-filter-message show text-center py-16 col-span-full">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">${filterMessages[filterValue] || 'Aucun produit trouvé'}</h3>
                    <p class="text-gray-500 mb-4">Veuillez essayer une autre catégorie</p>
                    <button onclick="showAllProducts()" class="bg-red-700 hover:bg-[#053d36] text-white px-6 py-2 rounded-lg transition">
                        Voir tous les produits
                    </button>
                </div>
            `;

                    if (productsGrid) {
                        productsGrid.insertAdjacentHTML('afterend', messageHTML);
                    }
                }

                // Fonction globale pour afficher tous les produits
                window.showAllProducts = function () {
                    filterItems.forEach(item => item.classList.remove('menu__item--active'));
                    document.querySelector('.menu__item[data-filter="all"]').classList.add('menu__item--active');
                    filterProducts('all');

                    // Supprimer le message de filtre vide
                    const emptyMessage = document.querySelector('.empty-filter-message');
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }
                };

                filterItems.forEach(item => {
                    item.addEventListener('click', function () {
                        // Retirer la classe active de tous les items
                        filterItems.forEach(i => {
                            i.classList.remove('menu__item--active');
                            i.classList.remove('bg-red-700', 'text-white');
                            i.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300');
                        });

                        // Ajouter la classe active à l'item cliqué
                        this.classList.add('menu__item--active');
                        this.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-300');
                        this.classList.add('bg-red-700', 'text-white');

                        const filterValue = this.getAttribute('data-filter');
                        filterProducts(filterValue);

                        // Supprimer tout message de filtre précédent
                        const existingMessage = document.querySelector('.empty-filter-message');
                        if (existingMessage && filterValue !== 'all') {
                            existingMessage.remove();
                        }
                    });
                });

                // Afficher tous les produits au chargement
                filterProducts('all');
            });

            // Fonction pour recharger les produits
            function reloadProducts() {
                const productItems = document.querySelectorAll('.product-item');
                const emptyMessages = document.querySelectorAll('.empty-filter-message, .empty-all-message');

                // Réinitialiser l'affichage
                productItems.forEach(item => {
                    item.classList.remove('hidden');
                });

                emptyMessages.forEach(message => {
                    message.classList.remove('show');
                });

                // Remettre le filtre "Tous" actif
                const filterItems = document.querySelectorAll('.menu__item');
                filterItems.forEach(i => {
                    i.classList.remove('menu__item--active');
                    i.classList.remove('bg-red-700', 'text-white');
                    i.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300');
                });

                document.querySelector('.menu__item[data-filter="all"]').classList.add('menu__item--active');
                document.querySelector('.menu__item[data-filter="all"]').classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-300');
                document.querySelector('.menu__item[data-filter="all"]').classList.add('bg-red-700', 'text-white');
            }
        </script>
        <!--======================= Testimonial ============================-->
        <section id="testimonial" class="section testimonial">
            <div class="section__header">
                <span class="section__subtitle">Témoignages</span>
                <h2 class="section__title">Clients satisfaits</h2>
            </div>
            <div class="d-grid testimonial__wrapper container">
                <!------- testimonial card 1 ------------>
                <div class="testimonial__card">
                    <div class="testimonial__header">
                        <img class="testimonial__img" alt="client"
                            src="https://static.vecteezy.com/system/resources/previews/046/798/040/non_2x/red-user-account-profile-flat-icon-for-apps-and-websites-free-vector.jpg">
                        <div class="rating">
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-half-fill"></i></span>
                        </div>
                    </div>
                    <div class="testimonial__body">
                        <p class="testimonial__quote">
                            Travail professionnel, respect des délais et suivi impeccable. Nous recommandons Johnson
                            Construction.
                        </p>
                        <h3 class="testimonial__name">Jean-Paul MUKAD</h3>
                    </div>
                </div>
                <!------- testimonial card 2 ------------>
                <div class="testimonial__card">
                    <div class="testimonial__header">
                        <img class="testimonial__img" alt="client"
                            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRjysSL0x7PSFdcai_Qte0uKUc079sdoTi4_Q&s">
                        <div class="rating">
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-half-fill"></i></span>
                        </div>
                    </div>
                    <div class="testimonial__body">
                        <p class="testimonial__quote">
                            Excellente gestion de chantier et finitions soignées. Très satisfaits du résultat final.
                        </p>
                        <h3 class="testimonial__name">Israel KASA</h3>
                    </div>
                </div>
                <!------- testimonial card 3 ------------>
                <div class="testimonial__card">
                    <div class="testimonial__header">
                        <img class="testimonial__img" alt="client"
                            src="https://us.123rf.com/450wm/siamimages/siamimages1701/siamimages170101635/70031699-personnes-user-icon.jpg?ver=6">
                        <div class="rating">
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                            <span class="rating__star"><i class="ri-star-half-fill"></i></span>
                        </div>
                    </div>
                    <div class="testimonial__body">
                        <p class="testimonial__quote">
                            Respect des normes, transparence des coûts et excellente communication tout au long du
                            projet.
                        </p>
                        <h3 class="testimonial__name">Claire Bernard</h3>
                    </div>
                </div>
            </div>
        </section>


    </main>
    <!--======================= Contact ============================-->
    <section id="contact" class="py-16 bg-gray-50">
        <div class="text-center mb-12">
            <span class="text-red-700 font-semibold text-lg uppercase tracking-wide">Contactez-nous</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mt-2">Prêt à démarrer votre projet ?</h2>
            <p class="mt-4 text-gray-600 max-w-2xl mx-auto">
                Contactez-nous directement pour discuter de votre projet et obtenir un devis personnalisé.
            </p>
        </div>

        <div class="container mx-auto grid md:grid-cols-2 gap-10 px-6 lg:px-20">
            <!-- Boutons de contact direct -->
            <div class="bg-white p-8 rounded-2xl shadow-lg">
                <h3 class="text-2xl font-semibold text-gray-800 mb-6">Contact direct</h3>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Prenez contact avec nous immédiatement via les canaux les plus rapides.
                    Notre équipe est disponible pour répondre à toutes vos questions.
                </p>

                <div class="space-y-4">
                    <!-- Appel téléphonique -->
                    <a href="tel:+243851653923"
                        class="w-full bg-[#128C7E] hover:bg-green-700 text-white py-4 px-6 rounded-xl font-semibold transition duration-300 flex items-center justify-center space-x-3 shadow-lg hover:shadow-xl">
                        <i class="ri-phone-fill text-xl"></i>
                        <span>Appeler maintenant</span>
                    </a>

                    <!-- WhatsApp -->
                    <a href="https://wa.me/243851653923?text=Bonjour, je suis intéressé par vos services de construction."
                        target="_blank"
                        class="w-full bg-[#128C7E] hover:bg-[#128C7E] text-white py-4 px-6 rounded-xl font-semibold transition duration-300 flex items-center justify-center space-x-3 shadow-lg hover:shadow-xl">
                        <i class="ri-whatsapp-fill text-xl"></i>
                        <span>WhatsApp</span>
                    </a>

                    <!-- Email -->
                    <a href="mailto:johnson31@outlook.fr?subject=Demande de devis&body=Bonjour, je souhaiterais obtenir un devis pour mon projet."
                        class="w-full bg-red-700 hover:bg-[#053d36] text-white py-4 px-6 rounded-xl font-semibold transition duration-300 flex items-center justify-center space-x-3 shadow-lg hover:shadow-xl">
                        <i class="ri-mail-fill text-xl"></i>
                        <span>Envoyer un email</span>
                    </a>

                    <!-- Visite sur place -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-6">
                        <div class="flex items-start space-x-3">
                            <i class="ri-map-pin-2-line text-blue-600 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-blue-800 mb-2">Visite sur place</h4>
                                <p class="text-blue-700 text-sm">
                                    Prenez rendez-vous pour une visite de nos installations ou pour une consultation sur
                                    votre chantier.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations de contact -->
            <div class="bg-white p-8 rounded-2xl shadow-lg flex flex-col justify-center">
                <h3 class="text-2xl font-semibold text-gray-800 mb-4">Informations de contact</h3>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    <strong>Johnson Construction</strong> — Experts en construction résidentielle et commerciale.
                    De la conception à la livraison, nous construisons avec rigueur et sécurité.
                </p>
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-center space-x-3">
                        <i class="ri-map-pin-2-line text-red-700 text-2xl"></i>
                        <span><strong>Adresse :</strong> Camps scout : avenue Lupopo/7/kassapa/ annexe</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-phone-line text-red-700 text-2xl"></i>
                        <a href="tel:+243975413369" class="hover:text-red-700 transition duration-200">
                            <strong>Téléphone :</strong> +243 975 413 369
                        </a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-whatsapp-line text-red-700 text-2xl"></i>
                        <a href="https://wa.me/243975413369" target="_blank"
                            class="hover:text-red-700 transition duration-200">
                            <strong>WhatsApp :</strong> +243 975 413 369
                        </a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-mail-line text-red-700 text-2xl"></i>
                        <a href="mailto:johnson31@outlook.fr" class="hover:text-red-700 transition duration-200">
                            <strong>Email :</strong> johnson31@outlook.fr
                        </a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-time-line text-red-700 text-2xl"></i>
                        <span><strong>Horaires :</strong> Lundi–Vendredi 8:00 - 18:00</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-calendar-line text-red-700 text-2xl"></i>
                        <span><strong>Week-end :</strong> Sur rendez-vous</span>
                    </li>
                </ul>

                <!-- Réseaux sociaux -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4">Suivez-nous</h4>
                    <div class="flex space-x-4">
                        <a href="javascript:void(0)"
                            class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center transition duration-200">
                            <i class="ri-facebook-fill"></i>
                        </a>
                        <a href="javascript:void(0)"
                            class="w-10 h-10 bg-blue-400 hover:bg-blue-500 text-white rounded-full flex items-center justify-center transition duration-200">
                            <i class="ri-twitter-fill"></i>
                        </a>
                        <a href="javascript:void(0)"
                            class="w-10 h-10 bg-blue-700 hover:bg-blue-800 text-white rounded-full flex items-center justify-center transition duration-200">
                            <i class="ri-linkedin-fill"></i>
                        </a>
                        <a href="javascript:void(0)"
                            class="w-10 h-10 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center transition duration-200">
                            <i class="ri-instagram-fill"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }
    </style>

    <script>
        // ==================== PARTIE JAVASCRIPT ====================
        document.addEventListener('DOMContentLoaded', function () {
            const contactForm = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const formMessages = document.getElementById('formMessages');

            // Éléments de validation
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const messageInput = document.getElementById('message');
            const nameError = document.getElementById('nameError');
            const emailError = document.getElementById('emailError');
            const messageError = document.getElementById('messageError');

            // Fonction pour afficher les messages
            function showMessage(message, type = 'success') {
                formMessages.innerHTML = '';

                const messageDiv = document.createElement('div');
                messageDiv.className = `p-4 rounded-lg fade-in ${type === 'success'
                    ? 'bg-green-100 border-green-400 text-green-700'
                    : 'bg-red-100 border-red-400 text-red-700'
                    } border`;

                messageDiv.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="ri-${type === 'success' ? 'checkbox-circle-line' : 'error-warning-line'}"></i>
                    <span>${message}</span>
                </div>
            `;

                formMessages.appendChild(messageDiv);

                // Scroll vers le message
                messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Supprimer après 5 secondes pour les succès
                if (type === 'success') {
                    setTimeout(() => {
                        messageDiv.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                        setTimeout(() => {
                            if (messageDiv.parentNode) {
                                messageDiv.parentNode.removeChild(messageDiv);
                            }
                        }, 300);
                    }, 5000);
                }
            }

            // Fonction de validation
            function validateForm() {
                let isValid = true;

                // Réinitialiser les erreurs
                [nameError, emailError, messageError].forEach(error => {
                    error.classList.add('hidden');
                });

                // Validation du nom
                if (!nameInput.value.trim()) {
                    nameError.classList.remove('hidden');
                    nameInput.classList.add('shake');
                    isValid = false;
                    setTimeout(() => nameInput.classList.remove('shake'), 500);
                } else {
                    nameInput.classList.remove('border-red-500');
                }

                // Validation de l'email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailInput.value.trim() || !emailRegex.test(emailInput.value)) {
                    emailError.classList.remove('hidden');
                    emailInput.classList.add('shake');
                    isValid = false;
                    setTimeout(() => emailInput.classList.remove('shake'), 500);
                } else {
                    emailInput.classList.remove('border-red-500');
                }

                // Validation du message
                if (!messageInput.value.trim()) {
                    messageError.classList.remove('hidden');
                    messageInput.classList.add('shake');
                    isValid = false;
                    setTimeout(() => messageInput.classList.remove('shake'), 500);
                } else {
                    messageInput.classList.remove('border-red-500');
                }

                return isValid;
            }

            // Fonction pour afficher le chargement
            function setLoadingState(isLoading) {
                if (isLoading) {
                    submitBtn.disabled = true;
                    submitText.textContent = 'Envoi en cours...';
                    loadingSpinner.classList.remove('hidden');
                } else {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Envoyer';
                    loadingSpinner.classList.add('hidden');
                }
            }

            // Validation en temps réel
            nameInput.addEventListener('input', function () {
                if (this.value.trim()) {
                    nameError.classList.add('hidden');
                    this.classList.remove('border-red-500', 'shake');
                }
            });

            emailInput.addEventListener('input', function () {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value.trim() && emailRegex.test(this.value)) {
                    emailError.classList.add('hidden');
                    this.classList.remove('border-red-500', 'shake');
                }
            });

            messageInput.addEventListener('input', function () {
                if (this.value.trim()) {
                    messageError.classList.add('hidden');
                    this.classList.remove('border-red-500', 'shake');
                }
            });

            // Validation au blur (quand on quitte le champ)
            nameInput.addEventListener('blur', function () {
                if (!this.value.trim()) {
                    this.classList.add('border-red-500');
                }
            });

            emailInput.addEventListener('blur', function () {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!this.value.trim() || !emailRegex.test(this.value)) {
                    this.classList.add('border-red-500');
                }
            });

            messageInput.addEventListener('blur', function () {
                if (!this.value.trim()) {
                    this.classList.add('border-red-500');
                }
            });

            // Événement de soumission avec AJAX
            contactForm.addEventListener('submit', async function (e) {
                e.preventDefault(); // Empêcher le rechargement de la page

                if (!validateForm()) {
                    // Scroll vers la première erreur
                    const firstError = document.querySelector('[id$="Error"]:not(.hidden)');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                    return;
                }

                setLoadingState(true);
                formMessages.innerHTML = '';

                // Préparer les données du formulaire
                const formData = new FormData();
                formData.append('name', nameInput.value.trim());
                formData.append('email', emailInput.value.trim());
                formData.append('message', messageInput.value.trim());
                formData.append('contact_submit', 'true');

                try {
                    // Envoyer les données via AJAX
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });

                    const text = await response.text();

                    // Extraire la réponse du PHP (simulation)
                    // Dans une vraie application, vous utiliseriez une API dédiée
                    // Pour cette démo, on simule l'envoi réussi

                    // Simulation d'envoi réussi
                    setTimeout(() => {
                        setLoadingState(false);
                        showMessage('Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.', 'success');

                        // Réinitialiser le formulaire
                        contactForm.reset();
                    }, 1000);

                } catch (error) {
                    setLoadingState(false);
                    showMessage('Une erreur est survenue lors de l\'envoi. Veuillez réessayer plus tard.', 'error');
                    console.error('Erreur:', error);
                }
            });

            // Réinitialiser le loading state
            setLoadingState(false);
        });
    </script>

    <?php
    // ==================== PARTIE PHP ====================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
        // Récupérer et nettoyer les données du formulaire
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars(trim($_POST['message']));
        $timestamp = date('d/m/Y H:i:s');

        // Validation des données
        $errors = [];

        if (empty($name)) {
            $errors[] = "Le nom est requis";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email est invalide";
        }

        if (empty($message)) {
            $errors[] = "Le message est requis";
        }

        // Si pas d'erreurs, envoyer l'email
        if (empty($errors)) {
            $to = "johnson31@outlook.fr";
            $subject = "Nouveau message de $name - Johnson Construction";

            // Construction du message
            $email_message = "
Nouveau message depuis le formulaire de contact Johnson Construction

Nom: $name
Email: $email
Date: $timestamp

Message:
$message

---
Cet email a été envoyé depuis le formulaire de contact du site web Johnson Construction.
        ";

            // En-têtes de l'email
            $headers = "From: $email\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Tentative d'envoi
            if (mail($to, $subject, $email_message, $headers)) {
                // Succès - ne rien afficher car le JavaScript gère l'affichage
            } else {
                // Erreur d'envoi
                error_log("Erreur d'envoi d'email pour le contact: $name, $email");
            }
        }
    }
    ?>



    <!--======================= Footer ============================-->
    <footer class="footer">
        <div class="d-grid footer__wrapper container p-6">
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
                        <a href="javascript:void(0)" class="footer__link">Autres</a>
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
                        <a href="https://wa.me/243851653923?text=Bonjour, Bonjour, j'aimerais prendre rendez-vous."
                            class="footer__link" target="_blank">Contact</a>
                    </li>

                </ul>
            </div>
        </div>
        <p class="footer__copyright">&copy; 2025 Johnson Construction. Tous droits réservés</p>
    </footer>

    <!-- Modal Panier -->
    <div id="panierModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[90%] max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- En-tête du panier -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Votre Panier</h3>
                    <button id="fermerPanierBtn" class="text-gray-500 hover:text-gray-700 transition">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>

                <!-- Conteneur messages -->
                <div id="panierMessage" class="mb-4"></div>

                <!-- Contenu du panier -->
                <div class="max-h-96 overflow-y-auto border rounded-lg p-3 bg-gray-50" id="panierContenu">
                    <!-- Le contenu du panier sera injecté ici -->
                    <div class="text-center py-8 text-gray-500">
                        <i class="ri-shopping-cart-line text-4xl mb-2"></i>
                        <p>Votre panier est vide</p>
                    </div>
                </div>

                <!-- Total et boutons -->
                <div class="mt-4 border-t pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-lg font-semibold text-gray-800">Total:</span>
                        <div class="flex items-center gap-1">
                            <span id="panierTotal" class="text-lg font-bold text-blue-600">0</span>
                            <span id="panierTotalDevise" class="text-lg font-bold text-blue-600">$</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button id="btnCommander"
                            class="bg-red-800 hover:bg-[#053d36] text-white transition px-4 py-3 rounded-lg font-semibold w-full flex items-center justify-center gap-2">
                            <i class="ri-shopping-bag-line"></i>
                            Passer la Commande
                        </button>

                        <button id="continuerAchatsBtn"
                            class="bg-gray-500 hover:bg-gray-600 text-white transition px-4 py-3 rounded-lg font-semibold w-full flex items-center justify-center gap-2">
                            <i class="ri-arrow-left-line"></i>
                            Continuer les Achats
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--=================== ScrollReveal ==================-->
    <script src="assets/js/scrollreveal.min.js"></script>

    <!--=================== Mixitup  ====================-->
    <script src="assets/js/mixitup.min.js"></script>

    <!--=================== Main JS ====================-->
    <script src="assets/js/main.js"> </script>


    <!--=================== Search JS ====================-->
    <script src="assets/js/search.js"> </script>

    <!--=================== Panier JS Intégré ====================-->
    <script>
        (function () {
            // Variable globale pour l'état de connexion
            <?php if (isset($_SESSION['user_id'])): ?>
                const estConnecte = true;
                const userId = <?php echo $_SESSION['user_id']; ?>;
            <?php else: ?>
                const estConnecte = false;
                const userId = null;
            <?php endif; ?>

            const state = {
                panier: []
            };

            function miseAJourCompteur() {
                const nombreArticles = state.panier.reduce((t, it) => t + it.quantite, 0);
                const badges = document.querySelectorAll('.shop__number');
                badges.forEach(badge => {
                    if (badge) badge.textContent = nombreArticles;
                });
            }

            function getImagePath(image) {
                if (!image) return '';

                if (image.startsWith('uploads/')) {
                    return "admin/" + image;
                } else {
                    return "admin/uploads/" + image;
                }
            }

            function mettreAJourPanier() {
                const contenu = document.getElementById('panierContenu');
                if (!contenu) return;

                contenu.innerHTML = '';
                let total = 0;

                if (state.panier.length === 0) {
                    contenu.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="ri-shopping-cart-line text-4xl mb-2"></i>
                        <p>Votre panier est vide</p>
                    </div>
                `;
                } else {
                    state.panier.forEach(item => {
                        const sousTotal = item.prix * item.quantite;
                        total += sousTotal;

                        const imagePath = getImagePath(item.image);
                        // Échappement sécurisé pour les noms de produits
                        const nomEchappe = item.nom.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/`/g, '\\`');

                        contenu.innerHTML += `
                        <div class="flex items-center justify-between mb-4 border-b pb-4">
                            <div class="flex items-center space-x-3 flex-1">
                                ${imagePath ? `
                                    <img src="${imagePath}" alt="${item.nom.replace(/"/g, '&quot;')}" 
                                         class="w-16 h-16 object-cover rounded border"
                                         onerror="this.style.display='none'">
                                    <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center" style="display: none;">
                                        <i class="ri-image-line text-gray-400"></i>
                                    </div>
                                ` : `
                                    <div class="w-16 h-16 bg-gray-200 rounded border flex items-center justify-center">
                                        <i class="ri-image-line text-gray-400"></i>
                                    </div>
                                `}
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800">${item.nom}</h4>
                                    ${item.poids ? `<p class="text-sm text-gray-600">Poids: ${item.poids}</p>` : ''}
                                    <p class="text-gray-700">
                                        ${item.prix} 
                                        <span class="font-semibold ${item.devise === 'USD' ? 'text-green-600' : 'text-red-600'}">
                                            ${item.devise === 'USD' ? '$' : 'FC'}
                                        </span>
                                        × 
                                        <input type="number" value="${item.quantite}" 
                                               min="1" max="${item.quantiteMax}" 
                                               onchange="changerQuantite('${nomEchappe}', this.value)"
                                               class="w-16 border rounded px-2 py-1 text-center">
                                    </p>
                                    <p class="text-sm font-semibold text-blue-600">
                                        Sous-total: ${sousTotal.toFixed(2)} ${item.devise === 'USD' ? '$' : 'FC'}
                                    </p>
                                </div>
                            </div>
                            <button onclick="supprimerDuPanier('${nomEchappe}')" 
                                    class="text-red-500 hover:text-red-700 ml-2 p-2 rounded-full hover:bg-red-50 transition-colors">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    `;
                    });
                }

                const totalEl = document.getElementById('panierTotal');
                if (totalEl) totalEl.textContent = total.toFixed(2);

                const totalDevise = document.getElementById('panierTotalDevise');
                if (totalDevise && state.panier.length > 0) {
                    const devise = state.panier[0].devise || 'USD';
                    totalDevise.textContent = devise === 'USD' ? '$' : 'FC';
                }

                miseAJourCompteur();
            }

            function showStyledAlert(message, type = 'warning') {
                // Supprimer les alertes existantes
                const existingAlerts = document.querySelectorAll('.custom-alert');
                existingAlerts.forEach(alert => alert.remove());

                // Créer l'overlay de fond
                const overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
                overlay.id = 'alertOverlay';

                const alert = document.createElement('div');
                const styles = {
                    warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
                    error: 'bg-red-50 border-red-400 text-red-800',
                    info: 'bg-blue-50 border-blue-400 text-blue-800',
                    success: 'bg-green-50 border-green-400 text-green-800'
                };

                const icons = {
                    warning: 'ri-alert-line text-yellow-600 text-2xl',
                    error: 'ri-close-circle-line text-red-600 text-2xl',
                    info: 'ri-information-line text-blue-600 text-2xl',
                    success: 'ri-checkbox-circle-line text-green-600 text-2xl'
                };

                const titles = {
                    warning: 'Attention',
                    error: 'Erreur',
                    info: 'Information',
                    success: 'Succès'
                };

                alert.className = `custom-alert ${styles[type]} border rounded-xl shadow-2xl max-w-md w-full mx-auto transform scale-95 opacity-0 transition-all duration-300`;

                alert.innerHTML = `
                <div class="p-6">
                    <div class="flex items-center space-x-4 mb-4">
                        <i class="${icons[type]}"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg mb-1">${titles[type]}</h3>
                            <p class="text-gray-700">${message}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button onclick="closeStyledAlert()" 
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors duration-200">
                            Fermer
                        </button>
                    </div>
                </div>
            `;

                overlay.appendChild(alert);
                document.body.appendChild(overlay);

                // Animation d'entrée
                setTimeout(() => {
                    alert.classList.remove('scale-95', 'opacity-0');
                    alert.classList.add('scale-100', 'opacity-100');
                }, 100);

                // Auto-suppression après 5 secondes
                setTimeout(() => {
                    closeStyledAlert();
                }, 5000);
            }

            function closeStyledAlert() {
                const overlay = document.getElementById('alertOverlay');
                if (overlay) {
                    const alert = overlay.querySelector('.custom-alert');
                    if (alert) {
                        alert.classList.remove('scale-100', 'opacity-100');
                        alert.classList.add('scale-95', 'opacity-0');
                    }

                    setTimeout(() => {
                        if (document.body.contains(overlay)) {
                            document.body.removeChild(overlay);
                        }
                    }, 300);
                }
            }

            function ajouterAuPanier(nom, prix, devise, poids, quantiteMax, image) {
                // Vérification de connexion utilisant la variable globale
                if (!estConnecte) {
                    showStyledAlert('Veuillez vous connecter pour ajouter des produits au panier.', 'warning');
                    return;
                }

                if (quantiteMax <= 0) {
                    showStyledAlert('Ce produit est actuellement en rupture de stock.', 'error');
                    return;
                }

                let produit = state.panier.find(p => p.nom === nom);
                if (produit) {
                    if (produit.quantite < produit.quantiteMax) {
                        produit.quantite++;
                    } else {
                        showStyledAlert('Quantité maximum atteinte pour ce produit', 'warning');
                        return;
                    }
                } else {
                    state.panier.push({
                        nom,
                        prix: parseFloat(prix),
                        devise: devise || 'USD',
                        poids: poids || '',
                        quantite: 1,
                        quantiteMax: parseInt(quantiteMax),
                        image: image || ''
                    });
                }
                mettreAJourPanier();
                showNotification('Produit ajouté au panier avec succès !', 'success');
            }

            function changerQuantite(nom, nouvelleQuantite) {
                const produit = state.panier.find(p => p.nom === nom);
                if (!produit) return;

                const q = parseInt(nouvelleQuantite) || 1;
                if (q >= 1 && q <= produit.quantiteMax) {
                    produit.quantite = q;
                    mettreAJourPanier();
                    showNotification('Quantité mise à jour', 'info');
                } else {
                    showStyledAlert(`Quantité invalide. Veuillez choisir entre 1 et ${produit.quantiteMax}.`, 'warning');
                    setTimeout(() => mettreAJourPanier(), 100);
                }
            }

            function supprimerDuPanier(nom) {
                state.panier = state.panier.filter(p => p.nom !== nom);
                mettreAJourPanier();
                showNotification('Produit retiré du panier', 'info');
            }

            function showNotification(message, type = 'success') {
                // Supprimer les notifications existantes
                const existingNotifications = document.querySelectorAll('.custom-notification');
                existingNotifications.forEach(notif => notif.remove());

                // Créer l'overlay pour les notifications aussi
                const overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
                overlay.id = 'notificationOverlay';

                const notification = document.createElement('div');
                const styles = {
                    success: 'bg-green-50 border-green-400 text-green-800',
                    error: 'bg-red-50 border-red-400 text-red-800',
                    warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
                    info: 'bg-blue-50 border-blue-400 text-blue-800'
                };

                const icons = {
                    success: 'ri-checkbox-circle-line text-green-600 text-2xl',
                    error: 'ri-close-circle-line text-red-600 text-2xl',
                    warning: 'ri-alert-line text-yellow-600 text-2xl',
                    info: 'ri-information-line text-blue-600 text-2xl'
                };

                notification.className = `custom-notification ${styles[type]} border rounded-xl shadow-2xl max-w-md w-full mx-auto transform scale-95 opacity-0 transition-all duration-300`;

                notification.innerHTML = `
                <div class="p-6">
                    <div class="flex items-center space-x-4">
                        <i class="${icons[type]}"></i>
                        <div class="flex-1">
                            <p class="font-medium">${message}</p>
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button onclick="closeNotification()" 
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors duration-200">
                            OK
                        </button>
                    </div>
                </div>
            `;

                overlay.appendChild(notification);
                document.body.appendChild(overlay);

                // Animation d'entrée
                setTimeout(() => {
                    notification.classList.remove('scale-95', 'opacity-0');
                    notification.classList.add('scale-100', 'opacity-100');
                }, 100);

                // Auto-suppression après 3 secondes
                setTimeout(() => {
                    closeNotification();
                }, 3000);
            }

            function closeNotification() {
                const overlay = document.getElementById('notificationOverlay');
                if (overlay) {
                    const notification = overlay.querySelector('.custom-notification');
                    if (notification) {
                        notification.classList.remove('scale-100', 'opacity-100');
                        notification.classList.add('scale-95', 'opacity-0');
                    }

                    setTimeout(() => {
                        if (document.body.contains(overlay)) {
                            document.body.removeChild(overlay);
                        }
                    }, 300);
                }
            }

            function showPanierMessage(message, type = 'error') {
                const messageContainer = document.getElementById('panierMessage');
                if (!messageContainer) return;

                // Supprimer les messages existants
                messageContainer.innerHTML = '';

                const styles = {
                    error: 'bg-red-100 border-red-300 text-red-700',
                    warning: 'bg-yellow-100 border-yellow-300 text-yellow-700',
                    success: 'bg-green-100 border-green-300 text-green-700',
                    info: 'bg-blue-100 border-blue-300 text-blue-700'
                };

                const icons = {
                    error: 'ri-error-warning-line',
                    warning: 'ri-alert-line',
                    success: 'ri-checkbox-circle-line',
                    info: 'ri-information-line'
                };

                const messageEl = document.createElement('div');
                messageEl.className = `mt-3 p-4 rounded-lg border ${styles[type]} flex items-center space-x-3 animate-fade-in`;

                messageEl.innerHTML = `
                <i class="${icons[type]} text-xl"></i>
                <div class="flex-1">
                    <p class="font-medium">${message}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="ri-close-line"></i>
                </button>
            `;

                messageContainer.appendChild(messageEl);

                // Auto-suppression après 5 secondes
                setTimeout(() => {
                    if (messageEl.parentNode) {
                        messageEl.style.opacity = '0';
                        messageEl.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            if (messageEl.parentNode) messageEl.remove();
                        }, 300);
                    }
                }, 5000);
            }

            function ouvrirPanier() {
                const modal = document.getElementById('panierModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }

            function fermerPanier() {
                const modal = document.getElementById('panierModal');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            }

            // Fonctions globales
            window.ajouterAuPanier = ajouterAuPanier;
            window.changerQuantite = changerQuantite;
            window.supprimerDuPanier = supprimerDuPanier;
            window.ouvrirPanier = ouvrirPanier;
            window.fermerPanier = fermerPanier;
            window.showStyledAlert = showStyledAlert;
            window.showPanierMessage = showPanierMessage;
            window.closeStyledAlert = closeStyledAlert;
            window.closeNotification = closeNotification;

            document.addEventListener('DOMContentLoaded', function () {
                console.log('Panier intégré chargé - Utilisateur connecté:', estConnecte);

                // Récupération des éléments
                const shopIcon = document.querySelector('.shop__icon');
                const fermerBtn = document.getElementById('fermerPanierBtn');
                const continuerAchatsBtn = document.getElementById('continuerAchatsBtn');
                const commanderBtn = document.getElementById('btnCommander');

                // Événements
                if (shopIcon) {
                    shopIcon.addEventListener('click', ouvrirPanier);
                }

                if (fermerBtn) {
                    fermerBtn.addEventListener('click', fermerPanier);
                }

                if (continuerAchatsBtn) {
                    continuerAchatsBtn.addEventListener('click', fermerPanier);
                }

                if (commanderBtn) {
                    commanderBtn.addEventListener('click', function () {
                        const messageContainer = document.getElementById('panierMessage');

                        if (messageContainer) messageContainer.innerHTML = '';

                        if (state.panier.length === 0) {
                            showPanierMessage('Votre panier est vide. Ajoutez au moins un produit avant de commander.', 'warning');
                            return;
                        }

                        // Vérifier à nouveau la connexion
                        if (!estConnecte) {
                            showStyledAlert('Veuillez vous connecter pour passer commande.', 'warning');
                            return;
                        }

                        // MODIFICATION IMPORTANTE : Vérifier s'il y a un produit dans le panier
                        if (state.panier.length > 0) {
                            // Prendre le premier produit du panier
                            const produit = state.panier[0];

                            // Rediriger vers details-commandes.php avec le nom du produit
                            window.location.href = `details-commandes.php?nom=${encodeURIComponent(produit.nom)}`;
                        } else {
                            showPanierMessage('Aucun produit dans le panier.', 'warning');
                        }
                    });
                }

                // Fermeture au clic sur le fond
                const modal = document.getElementById('panierModal');
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) fermerPanier();
                    });
                }

                // Chargement du panier
                const panierSauvegarde = localStorage.getItem('panier');
                if (panierSauvegarde) {
                    try {
                        state.panier = JSON.parse(panierSauvegarde);
                        mettreAJourPanier();
                    } catch (e) {
                        console.error('Erreur lors du chargement du panier:', e);
                        state.panier = [];
                    }
                }

                miseAJourCompteur();
            });
        })();
    </script>

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
    <style>
        /* Styles pour améliorer l'affichage */
        .menu__price {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .menu__card {
            transition: all 0.3s ease;
        }

        .menu__card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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