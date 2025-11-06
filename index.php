<?php
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
    <title>Johnson Construction</title>

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
            <a href="index.html" class="nav__brand"><span>Johnson</span> Construction</a>
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
                    <li class="nav__item">
                        <a href="#blog" class="nav__link">Actualités</a>
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
                                $quantite = htmlspecialchars($row['quantite']);
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

        <!--======================= Blog (Actualités) ============================-->
        <section id="blog" class="section blog">
            <div class="section__header">
                <span class="section__subtitle">Actualités</span>
                <h2 class="section__title">Dernières publications</h2>
            </div>
            <div class="d-grid blog__wrapper container">
                <!------- blog post 1 --------->
                <div class="blog__post">
                    <div class="blog__overlay"></div>
                    <img src="https://maformationbatiment.fr/wp-content/uploads/elementor/thumbs/AdobeStock_565303373-scaled-rb9nq4i0glvvj9oiqpd7adk6gtbaasvx8nugkb17rc.jpeg"
                        alt="article" class="blog__img">
                    <div class="blog__content">
                        <h3 class="blog__title">10 conseils pour votre construction durable</h3>
                        <a href="javascript:void(0)" class="blog__link">En savoir plus</a>
                    </div>
                </div>
                <!------- blog post 2 --------->
                <div class="blog__post">
                    <div class="blog__overlay"></div>
                    <img src="https://img.freepik.com/photos-premium/creer-communautes-prosperes-batiment-fois-photo-groupe-constructeurs-evaluant-progres-chantier-construction_590464-22489.jpg"
                        alt="article" class="blog__img">
                    <div class="blog__content">
                        <h3 class="blog__title">Planifier un chantier : étapes clés</h3>
                        <a href="javascript:void(0)" class="blog__link">En savoir plus</a>
                    </div>
                </div>
                <!------- blog post 3 --------->
                <div class="blog__post">
                    <div class="blog__overlay"></div>
                    <img src="https://media.istockphoto.com/id/977302930/fr/photo/ouvrier-du-b%C3%A2timent-utilisent-le-harnais-de-s%C3%A9curit%C3%A9-et-de-la-ligne-de-s%C3%A9curit%C3%A9-travaillant.jpg?s=612x612&w=0&k=20&c=7Dt_w7svq9XSaUYjwe4XJwPLIDzOruuNg12otP8Caiw="
                        alt="article" class="blog__img">
                    <div class="blog__content">
                        <h3 class="blog__title">Sécurité sur le chantier : bonnes pratiques</h3>
                        <a href="javascript:void(0)" class="blog__link">En savoir plus</a>
                    </div>
                </div>
            </div>

            <!--======================= Modale Blog ============================-->
            <div id="blogModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
                <div class="bg-white rounded-xl max-w-3xl mx-4 overflow-hidden shadow-lg">
                    <img id="modalImg" src="" alt="" class="w-full h-64 object-cover">
                    <div class="p-6">
                        <h3 id="modalTitle" class="text-2xl font-bold mb-4"></h3>
                        <p id="modalText" class="text-justify leading-relaxed mb-6"></p>
                        <div class="text-right">
                            <button onclick="closeBlogModal()"
                                class="bg-[#065f46] hover:bg-[#053d36] text-white font-semibold py-2 px-5 rounded-lg">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                const blogPosts = [
                    {
                        title: "10 conseils pour votre construction durable",
                        image: "https://maformationbatiment.fr/wp-content/uploads/elementor/thumbs/AdobeStock_565303373-scaled-rb9nq4i0glvvj9oiqpd7adk6gtbaasvx8nugkb17rc.jpeg",
                        text: `
                    1. **Choisir des matériaux durables** : privilégiez le bois certifié FSC, la brique écologique ou le béton recyclé.
                    2. **Optimiser l'isolation** : réduisez les pertes énergétiques grâce à des isolants performants et respectueux de l'environnement.
                    3. **Réutiliser et recycler** : réemployez les matériaux récupérés et recyclez les déchets de chantier.
                    4. **Énergie renouvelable** : intégrez panneaux solaires, pompes à chaleur et systèmes économes en énergie.
                    5. **Gestion de l'eau** : installez des récupérateurs d’eau de pluie et des sanitaires économes.
                    6. **Planification efficace** : réduisez les déplacements et optimisez le temps de chantier pour limiter l'empreinte carbone.
                    7. **Limiter l'impact du chantier** : minimisez le bruit, la poussière et la perturbation de la biodiversité locale.
                    8. **Choisir des peintures et colles écologiques** : évitez les composés organiques volatils (COV).
                    9. **Favoriser la lumière naturelle** : conception de bâtiments pour maximiser l’éclairage naturel et réduire la consommation électrique.
                    10. **Sensibiliser les équipes** : formez vos ouvriers aux pratiques durables et aux gestes écologiques quotidiens.
                    `
                    },
                    {
                        title: "Planifier un chantier : étapes clés",
                        image: "https://img.freepik.com/photos-premium/creer-communautes-prosperes-batiment-fois-photo-groupe-constructeurs-evaluant-progres-chantier-construction_590464-22489.jpg",
                        text: "La planification d’un chantier est essentielle pour respecter les délais et les budgets. Apprenez à coordonner les équipes et anticiper les imprévus."
                    },
                    {
                        title: "Sécurité sur le chantier : bonnes pratiques",
                        image: "https://media.istockphoto.com/id/977302930/fr/photo/ouvrier-du-b%C3%A2timent-utilisent-le-harnais-de-s%C3%A9curit%C3%A9-et-de-la-ligne-de-s%C3%A9curit%C3%A9-travaillant.jpg?s=612x612&w=0&k=20&c=7Dt_w7svq9XSaUYjwe4XJwPLIDzOruuNg12otP8Caiw=",
                        text: "Assurer la sécurité sur le chantier est primordial. Du port des équipements de protection à la signalisation, découvrez les meilleures pratiques pour protéger chaque ouvrier."
                    }
                ];

                const modal = document.getElementById("blogModal");
                const modalTitle = document.getElementById("modalTitle");
                const modalText = document.getElementById("modalText");
                const modalImg = document.getElementById("modalImg");

                const buttons = document.querySelectorAll(".blog__link");

                buttons.forEach((btn, index) => {
                    btn.addEventListener("click", () => {
                        modalTitle.textContent = blogPosts[index].title;
                        modalText.textContent = blogPosts[index].text;
                        modalImg.src = blogPosts[index].image;
                        modal.classList.remove("hidden");
                        modal.classList.add("flex");
                    });
                });

                function closeBlogModal() {
                    modal.classList.add("hidden");
                    modal.classList.remove("flex");
                }
            </script>
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
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                        required>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold  mb-1">Votre email</label>
                    <input type="email" placeholder="Entrez votre adresse email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                        required>
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
                        <a href="tel:0977199714"><strong >Téléphone :</strong> +243 977 199 714</a>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="ri-mail-line text-red-700 text-2xl"></i>
                        <a href="mailto:contact@johnsonconstruction.com"><strong>Email :</strong> contact@johnsonconstruction.com</a>
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




</body>

</html>