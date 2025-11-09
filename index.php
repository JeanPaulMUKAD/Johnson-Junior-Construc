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
        <nav class="nav container">
            <a href="index.html" class="nav__brand"><span>Johnson</span> Jr Construction</a>
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
            <div class="d-grid about__wrapper container">
                <div class="about__content">
                    <span class="about__subtitle">À propos</span>
                    <h2 class="about__title">Expertise en construction depuis 1998</h2>
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

        <!--======================= Nos Produits ============================-->
        <section id="products" class="bg-gradient-to-b from-gray-50 to-white py-20">
            <div class="text-center mb-16">
                <span class="text-x font-semibold tracking-widest text-red-700 uppercase">Nos Produits</span>
                <h2 class="mt-2 text-4xl font-extrabold text-gray-800">Matériaux de Qualité</h2>
                <p class="mt-3 max-w-2xl mx-auto text-gray-500">
                    Découvrez notre sélection de matériaux fiables pour vos projets de construction, alliant durabilité
                    et performance.
                </p>
            </div>

            <div class="container mx-auto px-6 lg:px-12">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">

                    <!-- Ciment -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS04rsYgshTUCPELm9yrt1YXyASfJrBwGUZ2Q&s"
                                alt="Ciment"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Ciment</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Notre ciment de haute qualité pour tous vos
                                projets de construction.</p>
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

                    <!-- Gravier -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_uHhxesbL0X7U1sFuy5LH9frwGBaaWfCSwQ&s"
                                alt="Gravier"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Gravier</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Gravier de différentes tailles pour vos
                                besoins spécifiques.</p>
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

                    <!-- Carreaux -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://franceschini.fr/wp-content/uploads/2017/09/carreaux-ciment-1.jpg"
                                alt="Carreaux"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Carreaux</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Large gamme de carreaux pour sols et murs
                                intérieurs/extérieurs.</p>
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

                    <!-- Pavés -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTp31Y6W1qG-T7jhkCUMy8Dquyvy_8RByhHFA&s"
                                alt="Pavés"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Pavés</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Pavés décoratifs et fonctionnels pour vos
                                aménagements extérieurs.</p>
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

                    <!-- Gyproc -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://livios-images.imgix.net/livios/newsitems/17809/-0002-gyproc-plafond-maken.png?auto=format&ar=4%3A3&w=1300&s=741510ff3e3d389e151781d61b9cfdcf"
                                alt="Gyproc"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Gyproc</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Solutions complètes en plaques de plâtre pour
                                vos cloisons et plafonds.</p>
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

                    <!-- Omega -->
                    <div
                        class="group relative bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300">
                        <div class="h-64 overflow-hidden">
                            <img src="https://omegacosi.com/wp-content/uploads/2024/08/5953dfbb-09b3-4390-aad1-27858f5c2a2a-768x577.jpg"
                                alt="Omega"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-6">
                            <h3
                                class="text-2xl font-semibold text-gray-800 group-hover:text-red-700 transition-colors duration-300">
                                Omega</h3>
                            <p class="mt-2 text-gray-600 leading-relaxed">Profilés Omega pour vos installations de
                                plafonds suspendus.</p>
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
            </div>

            <div class="menu__filter">
                <span class="menu__item menu__item--active" data-filter=".all">Tous</span>
                <span class="menu__item" data-filter=".ciment">Ciment</span>
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
                    $produits = ['ciment', 'gravier', 'pave', 'carreaux', 'gyproc', 'omega', 'chanel'];

                    foreach ($produits as $produit) {
                        $stmt = $conn->prepare("SELECT nom, prix, quantite, image FROM produits WHERE nom LIKE :nom");
                        $searchTerm = '%' . $produit . '%';
                        $stmt->bindParam(':nom', $searchTerm);
                        $stmt->execute();

                        $classe = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $produit));
                        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($resultats) > 0) {
                            foreach ($resultats as $row) {
                                $nomProduit = htmlspecialchars($row['nom']);
                                $prix = htmlspecialchars($row['prix']);
                                $quantite = $row['quantite'];
                                $image = htmlspecialchars($row['image']);
                                ?>
                                <div class="menu__card <?php echo $classe; ?> all mix">
                                    <?php if (!empty($image)): ?>
                                        <div class="menu__img-wrapper">
                                            <img src="uploads/<?php echo $image; ?>" alt="<?php echo $nomProduit; ?>" class="menu__img">
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xl">Aucune image</span>
                                    <?php endif; ?>

                                    <div class="menu__card-body">
                                        <h3 class="menu__title"><?php echo $nomProduit; ?></h3>
                                        <div class="rating">
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-fill"></i></span>
                                            <span class="rating__star"><i class="ri-star-half-fill"></i></span>
                                        </div>
                                        <span class="menu__price">Prix: <?php echo $prix; ?> $</span>
                                        <span class="menu__quantity">Quantité disponible: <?php echo $quantite; ?></span>
                                        <button
                                            onclick="ajouterAuPanier('<?php echo $nomProduit; ?>', <?php echo $prix; ?>, <?php echo $quantite; ?>, '<?php echo $image; ?>')"
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
                        <span><strong>Adresse :</strong> L'shi, 15 rue des Constructeurs</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-phone-line text-red-700 text-2xl"></i>
                        <a href="tel:0977199714"><strong>Téléphone :</strong> +243 977 199 714</a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-mail-line text-red-700 text-2xl"></i>
                        <a href="mailto:contact@johnsonconstruction.com"><strong>Email :</strong>
                            contact@johnsonconstruction.com</a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-time-line text-red-700 text-2xl"></i>
                        <span><strong>Horaires :</strong> Lu–Ve 8:00 - 18:00</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>



    <!--======================= Footer ============================-->
    <footer class="footer">
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
                        <p class="font-bold">Total: <span id="panierTotal">0</span></p>
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

    qu'on recupere la bonne devise puis joue avec le design

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




</body>

</html>