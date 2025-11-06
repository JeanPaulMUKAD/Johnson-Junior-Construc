<?php
session_start();
include 'config/database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email']) && isset($_POST['mot_de_passe'])) {
        $email = htmlspecialchars($_POST['email']);
        $mot_de_passe = $_POST['mot_de_passe'];
        
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT id, nom, email, mot_de_passe, role FROM utilisateurs WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Mot de passe incorrect";
                }
            } else {
                $error = "Email non trouvé";
            }
        } catch(PDOException $e) {
            $error = "Erreur de connexion";
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Connexion - Jonson Construction">
    <meta name="keywords" content="construction, matériaux, connexion">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Connexion | Jonson Construction</title>

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-green-800 to-green-600 min-h-screen flex items-center justify-center" style="font-family: 'DM Sans', sans-serif;">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="bg-green-800 text-white text-center p-6 rounded-t-lg">
            <h2 class="text-2xl font-bold"><i class="fa fa-building"></i> Jonson Construction</h2>
            <p class="mt-2">Connexion à votre compte</p>
        </div>
        <div class="p-6">
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 border border-red-300 rounded p-4 mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Email *</label>
                    <input type="email" class="form-control border border-gray-300 rounded w-full p-3" id="email" name="email" required placeholder="Votre adresse email">
                </div>
                
                <div class="mb-4">
                    <label for="mot_de_passe" class="block text-gray-700 font-semibold mb-2">Mot de passe *</label>
                    <input type="password" class="form-control border border-gray-300 rounded w-full p-3" id="mot_de_passe" name="mot_de_passe" required placeholder="Votre mot de passe">
                </div>
                
                <button type="submit" class="bg-green-800 text-white font-semibold py-2 rounded w-full hover:bg-green-700 transition">Se connecter</button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-gray-600">Pas encore de compte ? <a href="inscription.php" class="text-green-800 font-semibold">Créer un compte</a></p>
                <p class="mt-2"><a href="index.php" class="text-green-800 font-semibold"><i class="fa fa-arrow-left"></i> Retour à l'accueil</a></p>
            </div>
        </div>
    </div>
</body>
</html>

