<?php
session_start();
require_once "../config/database.php";


$message = "";

// Vérifie si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $nouveau_mot_de_passe = trim($_POST["nouveau_mot_de_passe"]);
    $confirmation = trim($_POST["confirmation"]);

    if (empty($email) || empty($nouveau_mot_de_passe) || empty($confirmation)) {
        $message = "⚠️ Veuillez remplir tous les champs.";
    } elseif ($nouveau_mot_de_passe !== $confirmation) {
        $message = "❌ Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'utilisateur existe
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Met à jour le mot de passe haché
            $hashed = password_hash($nouveau_mot_de_passe, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);

            if ($update->execute()) {
                $message = "✅ Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            } else {
                $message = "❌ Une erreur s'est produite lors de la mise à jour.";
            }
        } else {
            $message = "⚠️ Aucun compte trouvé avec cet e-mail.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen" style="font-family: DM Sans, sans-serif; background-color: #F4F5FF;">

    <div class="bg-white shadow-xl rounded-2xl p-8 w-full max-w-md">
        <div>
            <img src="https://cdn-icons-png.flaticon.com/512/2910/2910768.png"
                 alt="Mot de passe oublié" class="mx-auto mb-4 w-24 h-24">
        </div>

        <h2 class="text-2xl font-bold text-center mb-6 text-green-700">Réinitialisation du mot de passe</h2>

        <?php if (!empty($message)): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-center">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail <span class="text-red-600">*</span></label>
                <input type="email" name="email" id="email" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none"
                    placeholder="Entrez votre e-mail">
            </div>

            <!-- Nouveau mot de passe -->
            <div>
                <label for="nouveau_mot_de_passe" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe <span class="text-red-600">*</span></label>
                <div class="relative">
                    <input type="password" name="nouveau_mot_de_passe" id="nouveau_mot_de_passe" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none pr-10"
                        placeholder="Nouveau mot de passe">
                    <button type="button" id="toggleNew" class="absolute inset-y-0 right-3 flex items-center text-gray-500">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>

            <!-- Confirmation -->
            <div>
                <label for="confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmez le mot de passe <span class="text-red-600">*</span></label>
                <div class="relative">
                    <input type="password" name="confirmation" id="confirmation" required
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none pr-10"
                        placeholder="Confirmez le mot de passe">
                    <button type="button" id="toggleConfirm" class="absolute inset-y-0 right-3 flex items-center text-gray-500">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>

            <!-- Bouton -->
            <button type="submit"
                class="w-full text-white py-2 rounded-lg transition duration-300"
                style="background-color: #2F855A;">
                Réinitialiser le mot de passe
            </button>

            <div class="text-center mt-3">
                <a href="../admin/login.php" class="text-sm text-indigo-600 hover:underline">
                    🔙 Retour à la connexion
                </a>
            </div>
        </form>
    </div>

    <script>
        feather.replace();

        // Fonction pour basculer la visibilité du mot de passe
        function toggleVisibility(buttonId, inputId) {
            const button = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            button.addEventListener("click", function () {
                const type = input.getAttribute("type") === "password" ? "text" : "password";
                input.setAttribute("type", type);
                this.innerHTML = type === "password"
                    ? '<i data-feather="eye"></i>'
                    : '<i data-feather="eye-off"></i>';
                feather.replace();
            });
        }

        toggleVisibility("toggleNew", "nouveau_mot_de_passe");
        toggleVisibility("toggleConfirm", "confirmation");
    </script>

</body>
</html>
