<?php
session_start();

    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "e_commerce_db";

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }

$message = "";

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $mot_de_passe = trim($_POST["mot_de_passe"]);

    if (!empty($email) && !empty($mot_de_passe)) {
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? AND role = 'admin' LIMIT 1");
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
</head>

<body class="flex min-h-screen bg-gray-100 font-sans">

    <!-- Section image -->
    <div class="hidden lg:flex w-1/2 h-screen">
        <img src="https://i.pinimg.com/1200x/28/85/bd/2885bdefd80677579a3d51c2d07b86bd.jpg"
            alt="Illustration Admin"
            class="object-cover w-full h-full">
    </div>

    <!-- Section formulaire -->
    <div class="flex w-full lg:w-1/2 items-center justify-center bg-white p-10">
        <div class="w-full max-w-md">
            <h2 class="text-center text-xl font-bold" style="color: #673DE6;">JOSHNSON Jr CONSTRUCTION</h2>
            <div class="text-center mb-6">
                <img src="https://previews.123rf.com/images/lightstudio/lightstudio1907/lightstudio190700204/126519016-real-estate-construction-logo-design-vector-template-house-and-building-with-blue-grey-color.jpg"
                    alt="Logo"
                    class="mx-auto w-24 h-24 mb-3 rounded-full shadow-md">
                <h2 class="text-3xl font-bold" style="color:#2F1C6A;">Connexion Administrateur</h2>
                <p class="text-gray-500 text-sm mt-1">Accédez à votre espace sécurisé</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="bg-red-100 text-red-600 p-3 rounded-lg mb-4 text-center">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <!-- Champ Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Adresse email <strong class="text-red-700">*</strong>
                    </label>
                    <input type="email" name="email" id="email" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="Entrer votre email.">
                </div>

                <!-- Champ Mot de passe -->
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

                <!-- Bouton Connexion -->
                <button type="submit"
                    class="w-full text-white py-2 rounded-lg font-semibold text-lg shadow-md transition duration-300"
                    style="background-color: #673DE6;">
                    Connexion
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-6">
                © <?= date('Y') ?> E-commerce Admin | Tous droits réservés.
            </p>
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