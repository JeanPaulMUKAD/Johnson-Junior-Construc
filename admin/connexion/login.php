<?php
declare(strict_types=1);
session_start();

// --- Configuration de la base de données ---
$host = "127.0.0.1:3306";
$user = "u913148723_Johnsonjr";
$pass = "Johnsonjr2003";
$dbname = "u913148723_e_commerce_db";

// Connexion à la base de données
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$message = "";

// --- Traitement du formulaire de connexion ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $mot_de_passe = trim($_POST["mot_de_passe"]);

    if (!empty($email) && !empty($mot_de_passe)) {
        $stmt = $conn->prepare("SELECT id, nom, mot_de_passe, role FROM utilisateurs WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($mot_de_passe, $admin['mot_de_passe'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nom'] = $admin['nom'];
                $_SESSION['user_role'] = $admin['role'];

                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Mot de passe incorrect.";
            }
        } else {
            $message = "Aucun administrateur trouvé avec cet e-mail.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <!--Link of Montserrat Font-->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

</head>

<body class="flex min-h-screen bg-gray-100 font-sans items-center justify-center"
    style="font-family: 'Montserrat', sans-serif;">

    <div class="mx-auto w-full max-w-6xl flex shadow-2xl rounded-xl overflow-hidden **h-screen**">

        <!-- Section image avec overlay bleu -->
        <div class="hidden lg:flex lg:w-3/5 **h-full** relative">
            <div class="absolute inset-0 bg-blue-800 opacity-80 z-10"></div>
            <img src="https://images.ctfassets.net/dkzw0n5g7yr3/6wWxudnoveynxVeGHkgb7r/9ea1a9d2166dae51b4fa58029eb9d5b5/https-__cmicglobal.com_wp-content_uploads_2020_01_iStock-1129748859-1200x800Thumb.jpg"
                alt="Illustration Admin" class="object-cover w-full **h-full** min-h-full">
            <!-- Texte superposé sur l'image -->
            <div class="absolute inset-0 flex flex-col justify-center items-center text-white z-20 p-10 text-center">
                <h2 class="text-3xl font-bold mb-4">JOHNSON Jr CONSTRUCTION</h2>
                <p class="text-xl mb-6">Votre partenaire de confiance</p>
                <div class="w-16 h-1 bg-white mb-6"></div>
                <p class="text-lg opacity-90">Solutions de construction innovantes et durables</p>
            </div>
        </div>

        <div class="flex w-full lg:w-2/5 items-center justify-center bg-white p-10 lg:px-20 **h-full**">
            <div class="w-full">
                <h2 class="text-center text-xl font-bold" style="color: #673DE6;">JOHNSON Jr CONSTRUCTION</h2>
                <div class="text-center mb-6">
                    <!-- Icône de construction au lieu du logo image -->
                    <div
                        class="mx-auto w-20 h-20 mb-3 rounded-lg shadow-md flex items-center justify-center bg-indigo-100">
                        <i data-feather="home" class="text-indigo-700 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl lg:text-3xl font-bold" style="color:#2F1C6A;">Connexion Administrateur</h2>
                    <p class="text-gray-500 text-sm mt-1">Accédez à votre espace sécurisé</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="bg-red-100 text-red-600 p-3 rounded-lg mb-4 text-center">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Adresse email <strong class="text-red-700">*</strong>
                        </label>
                        <input type="email" name="email" id="email" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            placeholder="Entrer votre email.">
                    </div>

                    <div>
                        <label for="mot_de_passe" class="block text-sm font-medium text-gray-700 mb-1">
                            Mot de passe <strong class="text-red-700">*</strong>
                        </label>
                        <div class="relative">
                            <input type="password" name="mot_de_passe" id="mot_de_passe" required
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none pr-10"
                                placeholder="Entrer votre mot de passe.">
                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-500">
                                <i data-feather="eye" class="text-indigo-700 hover:text-indigo-500 transition"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full text-white py-2 rounded-lg font-semibold text-lg shadow-md transition duration-300 bg-[#673DE6] hover:bg-blue-800">
                        Connexion
                    </button>

                    <p class="text-center text-gray-500 text-sm mt-6">
                        © <?= date('Y') ?> Johnson Jr Construction Admin | Tous droits réservés.
                    </p>
            </div>
        </div>

    </div>
    <script>
        feather.replace();

        const togglePassword = document.querySelector("#togglePassword");
        const passwordInput = document.querySelector("#mot_de_passe");

        togglePassword.addEventListener("click", function () {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);

            this.innerHTML = type === "password"
                ? '<i data-feather="eye"></i>'
                : '<i data-feather="eye-off"></i>';
            feather.replace();
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

        // Variable de l'animation
        let anime;
    </script>

</body>

</html>