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
                    <a href="mes-commandes.php"
                        class="bg-[#b60c0c] hover:bg-[#053d36] rounded-lg text-white px-6 py-6  font-medium transition duration-300 ">Voir
                        mes commandes</a>
                </div>

            </div>
        </section>

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
                    <p class="about__description">Johnson Construction est une entreprise familiale spécialisée dans la
                        construction clé en main.
                        Nous combinons savoir-faire, innovation et respect des normes pour livrer des bâtiments durables
                        et performants.
                        <br><br>
                        Notre équipe s'engage à optimiser les coûts, réduire les délais et assurer une communication
                        claire à chaque étape.
                    </p>
                    <a href="javascript:void(0)" class="btn btn--primary">Notre histoire</a>
                </div>
                <img src="https://static.vecteezy.com/system/resources/previews/059/709/783/non_2x/the-red-house-logo-design-real-estate-houses-house-logo-building-logos-business-concept-inspirations-template-element-vector.jpg"
                    alt="équipe de construction" class="about__img">
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
                            <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
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
                            <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80"
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

                </div>
            </div>
        </section>


        <!--======================= Menu (Projets) ============================-->
        <section id="menu" class="section menu">
            <div class="section__header">
                <span class="section__subtitle">Nos Produits</span>
                <h2 class="section__title">Matériaux de Construction</h2>
                <p>Cliquer sur un produit pour votre réservation</p>
            </div>

            <div class="menu__filter">
                <span class="menu__item menu__item--active" data-filter=".all">Tous</span>
                <span class="menu__item" data-filter=".ciment">Ciment</span>
                <span class="menu_item" data-filter=".bloc-ciment">Bloc-ciment</span>
                <span class="menu__item" data-filter=".gravier">Gravier</span>
                <span class="menu__item" data-filter=".pave">Pavé</span>
                <span class="menu__item" data-filter=".carreaux">Carreaux</span>
                <span class="menu__item" data-filter=".gyproc">Gyproc</span>
                <span class="menu__item" data-filter=".omega">Omega</span>
                <span class="menu__item" data-filter=".chanel">Chanel</span>
            </div>

            <div class="d-grid menu__wrapper container">
                <?php
                try {
                    $produits = ['ciment', 'bloc-ciment', 'gravier', 'pave', 'carreaux', 'gyproc', 'omega', 'chanel'];

                    foreach ($produits as $produit) {
                        $stmt = $conn->prepare("SELECT nom, prix, devise, poids, quantite, image FROM produits WHERE LOWER(nom) LIKE :nom OR nom LIKE :nom2");
                        $searchTerm1 = '%' . strtolower($produit) . '%';
                        $searchTerm2 = '%' . ucfirst($produit) . '%';
                        $stmt->bindParam(':nom', $searchTerm1);
                        $stmt->bindParam(':nom2', $searchTerm2);
                        $stmt->execute();

                        $classe = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $produit));
                        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($resultats) > 0) {
                            foreach ($resultats as $row) {
                                $nomProduit = htmlspecialchars($row['nom']);
                                $prix = htmlspecialchars($row['prix']);
                                $devise = htmlspecialchars($row['devise'] ?? 'USD');
                                $poids = htmlspecialchars($row['poids'] ?? '');
                                $quantite = $row['quantite'];
                                $image = htmlspecialchars($row['image']);
                                ?>
                                <div class="menu__card <?php echo $classe; ?> all mix">
                                    <?php if (!empty($image)): ?>
                                        <div class="menu__img-wrapper">
                                            <?php
                                            // CORRECTION : N'ajoutez pas "admin/uploads/" si le chemin contient déjà "uploads/"
                                            if (strpos($image, 'uploads/') === 0) {
                                                // Le chemin commence déjà par "uploads/"
                                                $imagePath = "admin/" . $image;
                                            } else {
                                                // Le chemin ne commence pas par "uploads/"
                                                $imagePath = "admin/uploads/" . $image;
                                            }

                                            // Vérifier si le fichier existe
                                            if (file_exists($imagePath)): ?>
                                                <img src="<?php echo $imagePath; ?>" alt="<?php echo $nomProduit; ?>" class="menu__img">
                                            <?php else: ?>
                                                <div class="text-gray-400 text-center p-4">
                                                    <i class="fas fa-image text-4xl mb-2"></i>
                                                    <p>Image non trouvée</p>
                                                    <!-- Debug info corrigé -->
                                                    <small class="text-xs block mt-2">
                                                        Nom: <?php echo $image; ?><br>
                                                        Chemin testé: <?php echo $imagePath; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-gray-400 text-center p-4">
                                            <i class="fas fa-image text-4xl mb-2"></i>
                                            <p>Aucune image enregistrée</p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="menu__card-body">
                                        <h3 class="menu__title"><?php echo $nomProduit; ?></h3>

                                        <?php if (!empty($poids)): ?>
                                            <div class="text-sm text-gray-600 mb-2 flex items-center gap-1 text-xl">
                                                <i class="fas fa-weight-hanging text-blue-500"></i>
                                                <span class="font-bold">Poids: <?php echo $poids; ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="rating">
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-half-fill"></i></span>
                                        </div>

                                        <span class="menu__price">
                                            Prix: <?php echo $prix; ?>
                                            <span
                                                class="font-semibold <?php echo $devise === 'USD' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $devise === 'USD' ? '$' : 'FC'; ?>
                                            </span>
                                        </span>

                                        <span class="menu__quantity">Quantité disponible: <?php echo $quantite; ?></span>

                                        <button
                                            onclick="ajouterAuPanier('<?php echo $nomProduit; ?>', <?php echo $prix; ?>, '<?php echo $devise; ?>', '<?php echo $poids; ?>', <?php echo $quantite; ?>, '<?php echo $image; ?>')"
                                            class="bg-red-800 hover:bg-[#053d36] text-white transition px-4 py-2 rounded-lg mt-2 w-full">
                                            Commander
                                        </button>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '
                    <div class="menu__card all mix ' . $classe . ' col-span-full text-center text-gray-500 py-10">
                        Aucun produit trouvé dans la catégorie <strong>' . ucfirst($produit) . '</strong>.
                    </div>
                    ';
                        }
                    }
                } catch (PDOException $e) {
                    echo "<div class='text-red-600 text-center'>Erreur de connexion : " . $e->getMessage() . "</div>";
                }
                ?>
            </div>
        </section>





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
        </div>

        <div class="container mx-auto grid md:grid-cols-2 gap-10 px-6 lg:px-20">
            <!-- Formulaire -->
            <form action="javascript:void(0)" class="bg-white p-8 rounded-2xl shadow-lg space-y-5">
                <div>
                    <label class="block text-gray-700 font-semibold  mb-1">Votre nom</label>
                    <input type="text" placeholder="Entrez votre nom"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none" required>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold  mb-1">Votre email</label>
                    <input type="email" placeholder="Entrez votre adresse email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none" required>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold  mb-1">Votre message</label>
                    <textarea rows="5" placeholder="Décrivez votre projet..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                        required></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-red-700 hover:bg-[#053d36] text-white  py-3 rounded-lg transition duration-300 flex items-center justify-center space-x-2">
                    <i class="ri-send-plane-line text-xl"></i>
                    <span>Envoyer</span>
                </button>
            </form>

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
                        <span><strong>Adresse :</strong> Camps scout :avenue Lupopo/7/kassapa/ annexe</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-phone-line text-red-700 text-2xl"></i>
                        <a href="tel:0975413369"><strong>Téléphone :</strong> +243 975 413 369</a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-mail-line text-red-700 text-2xl"></i>
                        <a href="mailto:contact@johnsonconstruction.com"><strong>Email :</strong>
                            johnson31@outlook.fr</a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-time-line text-red-700 text-2xl"></i>
                        <span><strong>Horaires :</strong> Lundi–Vendredi 8:00 - 18:00</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>



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

    <!--=================== Panier JS ====================-->
    <script src="assets/js/panier.js"></script>
    <!--=================== Search JS ====================-->
    <script src="assets/js/search.js"> </script>

    <script>
        // MODIFICATION : Mise à jour de la fonction ajouterAuPanier pour inclure devise, poids et image
        function ajouterAuPanier(nom, prix, devise, poids, quantite, image) {
            // Vérifier si l'utilisateur est connecté
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Veuillez vous connecter pour ajouter des produits au panier.');
                return;
            <?php endif; ?>

            // Vérifier la disponibilité
            if (quantite <= 0) {
                alert('Ce produit est actuellement en rupture de stock.');
                return;
            }

            // Récupérer le panier existant ou en créer un nouveau
            let panier = JSON.parse(localStorage.getItem('panier')) || [];

            // Vérifier si le produit est déjà dans le panier
            const produitExistant = panier.find(item => item.nom === nom);

            if (produitExistant) {
                if (produitExistant.quantitePanier >= quantite) {
                    alert('Quantité maximale disponible atteinte pour ce produit.');
                    return;
                }
                produitExistant.quantitePanier += 1;
            } else {
                // MODIFICATION : Ajout de devise, poids et image dans l'objet produit
                panier.push({
                    nom: nom,
                    prix: prix,
                    devise: devise,
                    poids: poids,
                    quantiteDisponible: quantite,
                    quantitePanier: 1,
                    image: image // L'image est maintenant correctement incluse
                });
            }

            // Sauvegarder le panier
            localStorage.setItem('panier', JSON.stringify(panier));

            // Mettre à jour le compteur du panier
            mettreAJourCompteurPanier();

            // Afficher un message de confirmation
            showNotification('Produit ajouté au panier avec succès !');

            // Debug: Afficher le panier dans la console
            console.log('Panier actuel:', panier);
        }

        function mettreAJourCompteurPanier() {
            const panier = JSON.parse(localStorage.getItem('panier')) || [];
            const totalItems = panier.reduce((sum, item) => sum + item.quantitePanier, 0);

            // Sélectionner tous les éléments avec la classe shop__number
            const compteursPanier = document.querySelectorAll('.shop__number');
            compteursPanier.forEach(compteur => {
                compteur.textContent = totalItems;
            });
        }

        function showNotification(message) {
            // Créer une notification toast
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
            notification.textContent = message;

            document.body.appendChild(notification);

            // Animation d'entrée
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Animation de sortie après 3 secondes
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Fonction pour afficher le contenu du panier (utile pour le débogage)
        function afficherPanierConsole() {
            const panier = JSON.parse(localStorage.getItem('panier')) || [];
            console.log('Contenu du panier:', panier);
            panier.forEach((produit, index) => {
                console.log(`Produit ${index + 1}:`, {
                    nom: produit.nom,
                    prix: produit.prix,
                    devise: produit.devise,
                    poids: produit.poids,
                    quantitePanier: produit.quantitePanier,
                    image: produit.image
                });
            });
        }

        // Initialiser le compteur du panier au chargement de la page
        document.addEventListener('DOMContentLoaded', function () {
            mettreAJourCompteurPanier();

            // Afficher le panier dans la console pour débogage
            afficherPanierConsole();
        });
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