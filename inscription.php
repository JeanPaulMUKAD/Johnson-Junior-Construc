<?php
session_start();
include 'config/database.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmation = $_POST['confirmation'];
    
    // Validation
    if (empty($nom) || empty($email) || empty($mot_de_passe) || empty($confirmation)) {
        $error = "Tous les champs sont obligatoires";
    } elseif ($mot_de_passe != $confirmation) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($mot_de_passe) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } else {
        try {
            $conn = getConnection();
            
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Cette adresse email est déjà utilisée";
            } else {
                // Hacher le mot de passe
                $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $role = 'client'; // Rôle fixe pour tous les clients
                $date_creation = date('Y-m-d H:i:s');
                $date_modification = date('Y-m-d H:i:s');
                
                // Insérer l'utilisateur
                $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, date_creation, date_modification) 
                                       VALUES (:nom, :email, :mot_de_passe, :role, :date_creation, :date_modification)");
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':mot_de_passe', $hashed_password);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':date_creation', $date_creation);
                $stmt->bindParam(':date_modification', $date_modification);
                
                if ($stmt->execute()) {
                    $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    // Optionnel: connecter automatiquement l'utilisateur
                    $last_id = $conn->lastInsertId();
                    $_SESSION['user_id'] = $last_id;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $role;
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Erreur lors de l'inscription";
                }
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de l'inscription: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Inscription - Jonson Construction">
    <meta name="keywords" content="construction, matériaux, inscription">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Inscription | Jonson Construction</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #004aad 0%, #0066cc 100%);
            padding: 50px 0;
        }
        .login-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: #004aad;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 700;
        }
        .login-body {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #004aad;
            box-shadow: 0 0 0 3px rgba(0, 74, 173, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #004aad;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        .btn-login:hover {
            background: #0066cc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 74, 173, 0.3);
        }
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .signup-link a {
            color: #004aad;
            text-decoration: none;
            font-weight: 600;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
        .password-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h2><i class="fa fa-user-plus"></i> Jonson Construction</h2>
                <p style="margin: 10px 0 0 0;">Créer votre compte</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom">Nom complet *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required placeholder="Votre nom complet">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Votre adresse email">
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe *</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required placeholder="Minimum 6 caractères">
                        <div class="password-info">Le mot de passe doit contenir au moins 6 caractères</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmation">Confirmer le mot de passe *</label>
                        <input type="password" class="form-control" id="confirmation" name="confirmation" required placeholder="Confirmez votre mot de passe">
                    </div>
                    
                    <button type="submit" class="btn-login">S'inscrire</button>
                </form>
                
                <div class="signup-link">
                    <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
                </div>
                
                <div class="signup-link">
                    <a href="index.php"><i class="fa fa-arrow-left"></i> Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


