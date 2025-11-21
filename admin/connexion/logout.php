<?php
declare(strict_types=1);

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

session_start();

// Empêcher la mise en cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Récupérer les informations de l'administrateur avant de détruire la session
$adminName = $_SESSION['admin_nom'] ?? 'Administrateur';
$adminRole = $_SESSION['user_role'] ?? 'Administrateur';

// Traitement de la déconnexion
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) || (isset($_GET['confirm']) && $_GET['confirm'] === 'true')) {
    
    // Journaliser la déconnexion
    if (isset($_SESSION['admin_id'])) {
        try {
            // Vérifier si le fichier de config existe
            if (!file_exists('config/database.php')) {
                throw new Exception("Fichier config/database.php introuvable");
            }
            
            require_once 'config/database.php';
            
            $host = "127.0.0.1:3306";
            $user = "u913148723_Johnsonjr";
            $pass = "Johnsonjr2003";
            $dbname = "u913148723_e_commerce_db";
            
            $conn = new mysqli($host, $user, $pass, $dbname);
            
            if ($conn->connect_error) {
                throw new Exception("Erreur de connexion: " . $conn->connect_error);
            }
            
            // Créer la table de logs si elle n'existe pas
            $createTable = "CREATE TABLE IF NOT EXISTS admin_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$conn->query($createTable)) {
                throw new Exception("Erreur création table: " . $conn->error);
            }
            
            // Insérer le log de déconnexion
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent) 
                                   VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Erreur préparation requête: " . $conn->error);
            }
            
            $action = 'DECONNEXION';
            $details = 'Administrateur déconnecté avec succès - Johnson Jr Construction';
            $stmt->bind_param("issss", $_SESSION['admin_id'], $action, $details, $ip, $userAgent);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur exécution requête: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            error_log("Erreur de journalisation déconnexion: " . $e->getMessage());
            // Continuer même si la journalisation échoue
        }
    }
    
    // Détruire la session
    session_unset();
    session_destroy();
    session_write_close();
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // REDIRECTION SIMPLIFIÉE
    header('Location: login.php?logout=success');
    exit;
}

// Si annulation, rediriger vers le dashboard
if (isset($_POST['cancel'])) {
    header('Location: ../dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Johnson Jr Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .construction-primary { background-color: #673DE6; }
        .construction-primary:hover { background-color: #5a34d4; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
        <!-- En-tête avec couleur de la marque -->
        <div class="bg-gradient-to-r from-[#673DE6] to-[#2F1C6A] p-6 text-center">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i data-feather="home" class="text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white">Johnson Jr Construction</h1>
            <p class="text-white text-opacity-90 mt-2">Panel Administrateur</p>
        </div>

        <div class="p-8">
            <!-- Icone de déconnexion -->
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center border-2 border-red-200">
                    <i data-feather="log-out" class="text-red-600 text-3xl"></i>
                </div>
            </div>

            <!-- Titre et message -->
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Déconnexion</h2>
                <p class="text-gray-600">
                    Êtes-vous sûr de vouloir vous déconnecter de votre session administrateur ?
                </p>
            </div>

            <!-- Informations de session -->
            <div class="bg-indigo-50 rounded-lg p-4 mb-6 border border-indigo-100">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm border border-indigo-200">
                        <i data-feather="user" class="text-[#673DE6]"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($adminName); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($adminRole); ?> • Johnson Jr Construction</p>
                        <div class="flex items-center mt-1 text-xs text-gray-500">
                            <i data-feather="clock" class="w-3 h-3 mr-1"></i>
                            <span>Dernière activité: <?php echo date('H:i'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Avertissement de sécurité -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6">
                <div class="flex items-start">
                    <i data-feather="alert-triangle" class="text-yellow-600 mr-2 mt-0.5 flex-shrink-0"></i>
                    <p class="text-sm text-yellow-800">
                        <strong>Attention:</strong> Vous serez redirigé vers la page de connexion et devrez vous reconnecter pour accéder au panel d'administration.
                    </p>
                </div>
            </div>

            <!-- Boutons d'action -->
            <form method="POST" class="flex space-x-4">
                <button type="submit" name="cancel"
                        class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors duration-200 flex items-center justify-center">
                    <i data-feather="x" class="mr-2 w-4 h-4"></i>
                    Annuler
                </button>
                <button type="submit" name="logout"
                        class="flex-1 bg-red-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-red-700 transition-colors duration-200 flex items-center justify-center">
                    <i data-feather="log-out" class="mr-2 w-4 h-4"></i>
                    Se déconnecter
                </button>
            </form>

            <!-- Lien de déconnexion rapide -->
            <div class="mt-4 text-center">
                <p class="text-xs text-gray-500 mb-2">Déconnexion rapide</p>
                <a href="?confirm=true" 
                   class="inline-flex items-center text-xs text-red-600 hover:text-red-800 underline transition-colors">
                    <i data-feather="power" class="w-3 h-3 mr-1"></i>
                    Déconnexion immédiate
                </a>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
            <div class="flex items-center justify-center text-gray-500">
                <i data-feather="shield" class="w-4 h-4 mr-2"></i>
                <p class="text-xs text-center">
                    Pour la sécurité de votre compte, veuillez vous déconnecter après chaque utilisation.
                </p>
            </div>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>