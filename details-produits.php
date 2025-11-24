<?php declare(strict_types=1);

session_start();
include 'config/database.php';

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

// Base de données des pièces disponibles par caractéristique
$pieces_disponibles = [
    'Halls de production sur mesure' => [
        'Hangar industriel standard - 500m²',
        'Hangar avec hauteur sous plafond 8m - 750m²',
        'Hall de production modulaire - 1000m²',
        'Espace production avec bureaux intégrés - 600m²'
    ],
    'Zones de stockage optimisées' => [
        'Entrepôt racking dynamique - 300m²',
        'Zone stockage palettes - 400m²',
        'Cellule logistique réfrigérée - 200m²',
        'Plateforme de distribution - 800m²'
    ],
    'Maisons individuelles clé en main' => [
        'Villa 4 pièces - 120m²',
        'Maison 5 pièces avec jardin - 150m²',
        'Pavillon 3 chambres - 90m²',
        'Maison contemporaine - 180m²'
    ],
    'Appartements modernes et fonctionnels' => [
        'Studio meublé - 25m²',
        'Appartement T2 - 45m²',
        'Duplex T3 - 75m²',
        'Appartement standing T4 - 95m²'
    ],
    'Villas de standing personnalisées' => [
        'Villa prestige 6 pièces - 250m²',
        'Maison maître avec piscine - 300m²',
        'Villa contemporaine - 200m²',
        'Propriété de charme - 350m²'
    ],
    'Locaux commerciaux sur mesure' => [
        'Boutique vitrine - 80m²',
        'Espace commercial centre-ville - 120m²',
        'Local activité - 60m²',
        'Showroom professionnel - 200m²'
    ],
    'Centres commerciaux' => [
        'Galerie marchande - 5000m²',
        'Centre commercial de proximité - 2000m²',
        'Espace retail - 1500m²'
    ],
    'Showrooms et espaces d\'exposition' => [
        'Showroom automobile - 800m²',
        'Espace exposition meubles - 600m²',
        'Galerie d\'art - 300m²'
    ],
    'Infrastructures routières et ferroviaires' => [
        'Pont routier 4 voies',
        'Échangeur autoroutier',
        'Ligne ferroviaire 10km',
        'Tunnel routier 2km'
    ],
    'Ouvrages d\'art et ponts' => [
        'Pont suspendu 500m',
        'Viaduc autoroutier',
        'Passerelle piétonne',
        'Pont en arc 200m'
    ],
    'Travaux hydrauliques' => [
        'Station d\'épuration',
        'Réseau d\'irrigation',
        'Barrage hydroélectrique',
        'Canal de navigation'
    ],
    'Conception architecturale innovante' => [
        'Étude de faisabilité',
        'Plans d\'exécution',
        'Maquette 3D numérique',
        'Dossier de permis de construire'
    ],
    'Plans et études techniques' => [
        'Plans architecturaux',
        'Étude structure béton',
        'Calculs de stabilité',
        'Détails d\'exécution'
    ],
    'Suivi de chantier personnalisé' => [
        'Coordination de chantier',
        'Contrôle qualité mensuel',
        'Reporting hebdomadaire',
        'Réunions de chantier'
    ],
    'Installation photovoltaïque' => [
        'Kit solaire 3kW - Maison individuelle',
        'Centrale 10kW - PME',
        'Installation industrielle 50kW',
        'Système autonome avec batterie'
    ],
    'Maintenance préventive et curative' => [
        'Contrat maintenance annuel',
        'Intervention d\'urgence',
        'Révision semestrielle',
        'Diagnostic complet'
    ],
    'Dépannage urgent 24h/24' => [
        'Intervention express 2h',
        'Service weekends et fériés',
        'Assistance téléphonique',
        'Déplacement urgent'
    ],
    'Garde et sécurité' => [
        'Agent de sécurité résidentiel',
        'Surveillance électronique',
        'Gardiens de propriété',
        'Service de rondes'
    ],
    'Services domestiques complets' => [
        'Ménage hebdomadaire - 3h',
        'Ménage mensuel complet - 6h',
        'Nettoyage printanier',
        'Entretien régulier'
    ],
    'Coursier et livraison' => [
        'Livraison express intra-muros',
        'Service coursier à vélo',
        'Livraison colis jusqu\'à 25kg',
        'Course quotidienne'
    ],
    'Jardinage et entretien' => [
        'Tonte pelouse - jusqu\'à 500m²',
        'Taille haies et arbustes',
        'Entretien massifs fleuris',
        'Élagage arbres'
    ],
    'Garde d\'enfants' => [
        'Baby-sitting occasionnel',
        'Garde régulière après école',
        'Nounou à domicile',
        'Garde weekends'
    ],
    'Parcelles viabilisées' => [
        'Parcelle 500m² - Viabilisée',
        'Terrain 1000m² - Lotissement',
        'Parcelle constructible 750m²',
        'Terrain avec vue - 600m²'
    ],
    'Concessions sécurisées' => [
        'Concession commerciale 10 ans',
        'Bail emphytéotique 30 ans',
        'Droit de superficie',
        'Location-gérance'
    ],
    'Portes sur mesure' => [
        'Porte blindée standard',
        'Porte d\'entrée design',
        'Porte coupe-feu',
        'Porte acoustique'
    ],
    'Fenêtres métalliques' => [
        'Fenêtre PVC double vitrage',
        'Baie vitrée aluminium',
        'Fenêtre coulissante',
        'Véranda sur mesure'
    ],
    'Barrières de sécurité' => [
        'Barrière automatique 4m',
        'Portail battant motorisé',
        'Grille de sécurité',
        'Barrière levante'
    ],
    'Charpentes industrielles' => [
        'Charpente métallique 20x50m',
        'Structure acier galvanisé',
        'Ossature bâtiment industriel',
        'Charpente atelier'
    ],
    'Nettoyage de bâtiments' => [
        'Nettoyage fin de chantier',
        'Entretien immeuble bureaux',
        'Nettoyage vitres hautes',
        'Décapage sols industriels'
    ],
    'Entretien industriel' => [
        'Nettoyage usine 2000m²',
        'Dégraissage machines',
        'Nettoyage atelier',
        'Entretien zones techniques'
    ],
    'Nettoyage de bureaux' => [
        'Nettoyage quotidien bureaux',
        'Entretien espaces communs',
        'Nettoyage sanitaires',
        'Aspiration moquettes'
    ],
    'Équipements professionnels' => [
        'Matériel nettoyage industriel',
        'Aspirateurs professionnels',
        'Autolaveuses sols',
        'Équipement sécurité'
    ],
    'Appartements meublés' => [
        'Studio meublé centre-ville',
        'Appartement T2 équipé',
        'Duplex standing meublé',
        'Loft contemporain'
    ],
    'Locaux commerciaux' => [
        'Local boutique 60m²',
        'Espace commercial 120m²',
        'Bureau-commerce 80m²',
        'Local artisanal 200m²'
    ],
    'Maisons commerciales' => [
        'Maison de ville commerciale',
        'Pavillon avec local professionnel',
        'Immeuble mixte habitation/commerce',
        'Propriété avec activité'
    ],
    'Décoration de la dot et véhicule' => [
        'Kit décoration complète véhicule marié',
        'Décoration florale pour dot',
        'Rubans et accessoires premium',
        'Décoration personnalisée selon thème'
    ],
    'Protocolat et organisation' => [
        'Coordinateur de mariage dédié',
        'Planning détaillé de la cérémonie',
        'Gestion des invités et placement',
        'Coordination des prestataires'
    ],
    'Animation et cavalier' => [
        'DJ professionnel avec matériel complet',
        'Animateur de cérémonie',
        'Cavalier pour entrée mariés',
        'Animation musicale sur mesure'
    ],
    'Conception et impression des invitations' => [
        'Invitation design personnalisé',
        'Cartes de remerciement',
        'Programme de cérémonie',
        'Faire-part luxe avec enveloppes'
    ],
    'Accompagnement cadeau' => [
        'Création de liste de mariage',
        'Service de collecte des cadeaux',
        'Emballage cadeaux premium',
        'Livraison des cadeaux à domicile'
    ],

    // Pièces pour Peinture
    'Peinture de maison intérieure et extérieure' => [
        'Peinture acrylique murale intérieure',
        'Peinture façade extérieure hydrofuge',
        'Peinture plafonds et moulures',
        'Préparation et ponçage des surfaces'
    ],
    'Peinture de clôture et portails' => [
        'Peinture anticorrosion pour métal',
        'Peinture bois extérieur durable',
        'Protection anti-UV et intempéries',
        'Finition mate, satinée ou brillante'
    ],
    'Décoration murale et effets spéciaux' => [
        'Faux-finis et patines',
        'Peinture effets métallisés',
        'Décoration trompe-l\'œil',
        'Stries et motifs personnalisés'
    ],
    'Rénovation et rafraîchissement' => [
        'Nettoyage et préparation surfaces',
        'Rebouchage fissures et imperfections',
        'Application primaire d\'accrochage',
        'Finition professionnelle 2 couches'
    ]
];

$services = [
    1 => [
        'nom' => 'Bâtiment Industriel',
        'description' => 'Construction et aménagement de bâtiments industriels adaptés à vos activités de production, entreposage et logistique.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                'https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
            ]
        ],
        'caracteristiques' => [
            'Halls de production sur mesure',
            'Zones de stockage optimisées',
            'Conformité aux normes industrielles'
        ]
    ],
    2 => [
        'nom' => 'Bâtiment Résidentiel',
        'description' => 'Construction de maisons individuelles, appartements et villas de standing pour votre confort familial.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1518780664697-55e3ad937233?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Maisons individuelles clé en main',
            'Appartements modernes et fonctionnels',
            'Villas de standing personnalisées'
        ]
    ],
    3 => [
        'nom' => 'Bâtiment Commercial',
        'description' => 'Conception et construction d\'espaces commerciaux stratégiques pour développer votre activité.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1497366811353-6870744d04b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Locaux commerciaux sur mesure',
            'Centres commerciaux',
            'Showrooms et espaces d\'exposition'
        ]
    ],
    4 => [
        'nom' => 'Génie Civil',
        'description' => 'Expertise en construction d\'infrastructures et ouvrages d\'art avec une maîtrise technique éprouvée.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1504307651254-35680f356dfd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Infrastructures routières et ferroviaires',
            'Ouvrages d\'art et ponts',
            'Travaux hydrauliques'
        ]
    ],
    5 => [
        'nom' => 'Bureau d\'Architecte',
        'description' => 'Conception et design architectural sur mesure pour vos projets avec créativité et expertise technique.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
            ]
        ],
        'caracteristiques' => [
            'Conception architecturale innovante',
            'Plans et études techniques',
            'Suivi de chantier personnalisé'
        ]
    ],
    6 => [
        'nom' => 'Installation & Services',
        'description' => 'Services complets d\'installation, maintenance et dépannage pour tous vos équipements.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.helloartisan.com/forward/file/0/6/3/7/30998e706237e1d005a256e842d9710bc67d7360/panneaux-photovoltaiques-aides-prix-installation-jpg.jpg',
            'thumbnails' => [
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFynjgSEJtcb0JsaWDL3bh-_ZV_KL0R8DawpoCoZxg20jrXfBVv_Ua8RuxKZsNJMtSXlE&usqp=CAU',
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFynjgSEJtcb0JsaWDL3bh-_ZV_KL0R8DawpoCoZxg20jrXfBVv_Ua8RuxKZsNJMtSXlE&usqp=CAU'
            ]
        ],
        'caracteristiques' => [
            'Installation photovoltaïque',
            'Maintenance préventive et curative',
            'Dépannage urgent 24h/24'
        ]
    ],
    7 => [
        'nom' => 'Services Ménagers',
        'description' => 'Services domestiques complets incluant garde, ménage, coursier, jardinage et nounou pour votre confort quotidien.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
                'https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'
            ]
        ],
        'caracteristiques' => [
            'Garde et sécurité',
            'Services domestiques complets',
            'Coursier et livraison',
            'Jardinage et entretien',
            'Garde d\'enfants'
        ]
    ],
    8 => [
        'nom' => 'Vente et Achat',
        'description' => 'Transactions immobilières sécurisées pour parcelles et concessions avec accompagnement personnalisé.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Parcelles viabilisées',
            'Concessions sécurisées',
            'Accompagnement juridique',
            'Transactions transparentes'
        ]
    ],
    9 => [
        'nom' => 'Soudure et Fabrication',
        'description' => 'Fabrication sur mesure de portes, fenêtres, barrières et charpentes métalliques de haute qualité.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://matriceriasdelcentro.com/wp-content/uploads/2019/04/soldadura.jpg',
            'thumbnails' => [
                'https://matriceriasdelcentro.com/wp-content/uploads/2019/04/soldadura.jpg',
                'https://matriceriasdelcentro.com/wp-content/uploads/2019/04/soldadura.jpg'
            ]
        ],
        'caracteristiques' => [
            'Portes sur mesure',
            'Fenêtres métalliques',
            'Barrières de sécurité',
            'Charpentes industrielles'
        ]
    ],
    10 => [
        'nom' => 'Équipement et Nettoyage',
        'description' => 'Services professionnels d\'équipement et de nettoyage pour bâtiments, industries et bureaux.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Nettoyage de bâtiments',
            'Entretien industriel',
            'Nettoyage de bureaux',
            'Équipements professionnels'
        ]
    ],
    11 => [
        'nom' => 'Location Immobilière',
        'description' => 'Large choix d\'appartements, locaux commerciaux et maisons commerciales disponibles à la location.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Appartements meublés',
            'Locaux commerciaux',
            'Maisons commerciales',
            'Contrats flexibles'
        ]
        ],

     12 => [
        'nom' => 'Services de Mariage',
        'description' => 'Organisation complète de votre mariage avec une équipe professionnelle pour un jour inoubliable.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1465495976277-4387d4b0e4a6?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1447877085163-3cce903855cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Décoration de la dot et véhicule',
            'Protocolat et organisation',
            'Animation et cavalier',
            'Conception et impression des invitations',
            'Accompagnement cadeau'
        ]
    ],
    13 => [
        'nom' => 'Services de Peinture',
        'description' => 'Services professionnels de peinture pour embellir et protéger vos espaces intérieurs et extérieurs.',
        'prix' => 'Sur devis',
        'images' => [
            'main' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'thumbnails' => [
                'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60',
                'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'
            ]
        ],
        'caracteristiques' => [
            'Peinture de maison intérieure et extérieure',
            'Peinture de clôture et portails',
            'Décoration murale et effets spéciaux',
            'Rénovation et rafraîchissement'
        ]
    ]
];

$service_actuel = $services[$_GET['id'] ?? 1] ?? $services[1];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des services - <?= $service_actuel['nom'] ?></title>

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

    <style>
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .image-zoom {
            transition: transform 0.5s ease;
        }

        .image-zoom:hover {
            transform: scale(1.05);
        }

        .feature-icon {
            background: #f8f9fa;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
        }

        .service-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .service-item:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Styles pour les caractéristiques déroulantes */
        .caracteristique-item {
            cursor: pointer;
            padding: 12px 16px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #811313;
            transition: all 0.3s ease;
            position: relative;
        }

        .caracteristique-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .caracteristique-item.active {
            background: #e3f2fd;
            border-left-color: #053d36;
        }

        .caracteristique-item .icon {
            transition: transform 0.3s ease;
        }

        .caracteristique-item.active .icon {
            transform: rotate(180deg);
        }

        .pieces-disponibles {
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s ease;
            background: white;
            border-radius: 8px;
            margin-top: 8px;
        }

        .pieces-disponibles.show {
            max-height: 500px;
            padding: 16px;
            border: 1px solid #e9ecef;
        }

        .piece-item {
            padding: 8px 12px;
            margin: 4px 0;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #053d36;
            transition: all 0.2s ease;
        }

        .piece-item:hover {
            background: #e9ecef;
            transform: translateX(3px);
        }

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
</head>

<body>

    <!--======================= Header Original =============================-->
    <header id="header" class="header">
        <nav class="nav container">
            <a href="index.html" class="nav__brand p-4"><span>Johnson</span> Jr Construction</a>
            <div id="nav-menu" class="nav__menu">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Accueil</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Services</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">À propos</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Projets</a>
                    </li>
                    <li class="nav__item">
                        <a href="index.php" class="nav__link">Actualités</a>
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
                                class="w-full bg-[#053d36] text-white py-3 rounded-md hover:bg-[#811313] transition">S'inscrire</button>
                        </form>
                        <button class="mt-4 text-gray-500 hover:text-red-700"
                            onclick="closeModal('register-modal')">Fermer</button>
                    </div>
                </div>

                <script>
                    document.querySelectorAll('.ri-login-box-line, .ri-user-add-line').forEach(btn => {
                        btn.addEventListener('click', e => {
                            e.preventDefault();
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

        <!--======================= Section Détails Service ============================-->
        <section class="container mx-auto px-6 lg:px-16 py-16 mt-32">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

                <!-- Galerie Images -->
                <div class="space-y-6">
                    <!-- Image Principale -->
                    <div class="rounded-2xl overflow-hidden shadow-lg bg-white p-4">
                        <img src="<?= $service_actuel['images']['main'] ?>" alt="<?= $service_actuel['nom'] ?>"
                            class="w-full h-96 object-cover image-zoom rounded-xl" id="mainImage">
                    </div>

                    <!-- Miniatures -->
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($service_actuel['images']['thumbnails'] as $thumbnail): ?>
                            <div
                                class="rounded-xl overflow-hidden border border-gray-200 hover:border-red-500 transition cursor-pointer">
                                <img src="<?= $thumbnail ?>" alt="<?= $service_actuel['nom'] ?>"
                                    class="w-full h-28 object-cover hover:scale-110 transition duration-300 thumbnail">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Détails Service -->
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <!-- En-tête -->
                    <div class="mb-6">
                        <span
                            class="inline-block bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium mb-3">
                            Service Professionnel
                        </span>
                        <h1 class="text-4xl font-bold text-gray-900 mb-4"><?= $service_actuel['nom'] ?></h1>
                        <div class="flex items-center space-x-4 mb-4">
                            <span
                                class="bg-[#811313] text-white px-4 py-2 rounded-full text-lg font-semibold"><?= $service_actuel['prix'] ?></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <p class="text-gray-700 text-[14px] leading-relaxed mb-8">
                        <?= $service_actuel['description'] ?>
                    </p>

                    <!-- Caractéristiques avec pièces déroulantes -->
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Caractéristiques principales</h3>
                        <div class="space-y-3" id="caracteristiques-container">
                            <?php foreach ($service_actuel['caracteristiques'] as $index => $caracteristique): ?>
                                <div class="caracteristique-item" data-index="<?= $index ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="feature-icon">
                                                <i class="ri-check-line text-[#053d36] text-lg"></i>
                                            </div>
                                            <span class="text-gray-700 font-medium"><?= $caracteristique ?></span>
                                        </div>
                                        <i class="ri-arrow-down-s-line icon text-gray-500"></i>
                                    </div>

                                    <!-- Section des pièces disponibles -->
                                    <?php if (isset($pieces_disponibles[$caracteristique])): ?>
                                        <div class="pieces-disponibles" id="pieces-<?= $index ?>">
                                            <h4 class="font-semibold text-gray-800 mb-3 text-sm">Pièces disponibles :</h4>
                                            <div class="space-y-2">
                                                <?php foreach ($pieces_disponibles[$caracteristique] as $piece): ?>
                                                    <div class="piece-item text-[14px]">
                                                        <?= htmlspecialchars($piece) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="mailto:johnson31@outlook.fr?subject=Demande de rendez-vous&body=Bonjour, je souhaite prendre rendez-vous pour le service suivant : <?= $service_actuel['nom'] ?>"
                                target="_blank"
                                class="flex-1 bg-[#811313] hover:bg-[#053d36] text-white py-4 px-6 rounded-xl font-semibold transition duration-300 flex items-center justify-center space-x-2 no-underline">
                                <i class="ri-calendar-line"></i>
                                <span>Prendre rendez-vous</span>
                            </a>
                            <a href="https://wa.me/243851653923?text=Bonjour, je suis intéressé(e) par le service suivant : <?= urlencode($service_actuel['nom']) ?>"
                                target="_blank"
                                class="flex-1 bg-[#053d36] hover:bg-[#811313] text-white py-4 px-6 rounded-xl font-semibold transition duration-300 flex items-center justify-center space-x-2 no-underline">
                                <i class="ri-phone-line"></i>
                                <span>Nous contacter</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--======================= Tous Nos Services ============================-->
        <section class="bg-gray-50 py-16">
            <div class="container mx-auto px-6 lg:px-12">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">Tous Nos Services</h2>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        Découvrez l'ensemble de nos prestations pour tous vos projets
                    </p>
                </div>

                <!-- Services Principaux avec Images -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                    <?php foreach ($services as $id => $service): ?>
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                            <div class="relative overflow-hidden">
                                <img src="<?= $service['images']['main'] ?>" alt="<?= $service['nom'] ?>"
                                    class="w-full h-48 object-cover image-zoom">
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-semibold text-gray-900 mb-2"><?= $service['nom'] ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-2"><?= $service['description'] ?></p>
                                <a href="details-produits.php?id=<?= $id ?>"
                                    class="inline-block bg-[#811313] hover:bg-[#053d36] text-white px-4 py-2 rounded-lg font-medium transition">
                                    Découvrir
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!--======================= Footer Original ============================-->
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
            <div class="relative top-20 mx-auto p-5 border w-[300px] shadow-lg rounded-md bg-white">
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

        <script>
            // Gestion des caractéristiques déroulantes
            document.querySelectorAll('.caracteristique-item').forEach(item => {
                item.addEventListener('click', function () {
                    const piecesSection = this.querySelector('.pieces-disponibles');
                    if (piecesSection) {
                        const isActive = this.classList.contains('active');

                        // Fermer toutes les autres sections
                        document.querySelectorAll('.caracteristique-item').forEach(otherItem => {
                            if (otherItem !== this) {
                                otherItem.classList.remove('active');
                                const otherPieces = otherItem.querySelector('.pieces-disponibles');
                                if (otherPieces) {
                                    otherPieces.classList.remove('show');
                                }
                            }
                        });

                        // Basculer l'état actuel
                        if (!isActive) {
                            this.classList.add('active');
                            piecesSection.classList.add('show');
                        } else {
                            this.classList.remove('active');
                            piecesSection.classList.remove('show');
                        }
                    }
                });
            });

            // Gestion des miniatures
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.addEventListener('click', function () {
                    const mainImage = document.getElementById('mainImage');
                    mainImage.src = this.src;

                    // Ajouter un effet de transition
                    mainImage.style.opacity = '0';
                    setTimeout(() => {
                        mainImage.style.opacity = '1';
                    }, 200);
                });
            });

            // Animation au scroll
            document.addEventListener('DOMContentLoaded', function () {
                const cards = document.querySelectorAll('.card-hover');

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, { threshold: 0.1 });

                cards.forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    observer.observe(card);
                });
            });

            // Gestion des modales
            function closeModal(id) {
                document.getElementById(id).classList.add('hidden');
            }

            // Fermer les modales en cliquant à l'extérieur
            window.addEventListener('click', function (event) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (event.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            });
        </script>
    </main>

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