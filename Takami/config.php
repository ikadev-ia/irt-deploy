<?php
// 1. On s'assure que la session est bien démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Connexion à la base de données (Variables d'environnement Docker)
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'takami_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// 3. Système d'authentification dynamique
if (!isset($_SESSION['liste_utilisateurs'])) {
    $_SESSION['liste_utilisateurs'] = [
        'admin' => ['password' => 'admin123', 'role' => 'admin', 'name' => 'Gestionnaire Takami'],
        'client@test.com' => ['password' => 'client123', 'role' => 'client', 'name' => 'Sidi Diallo']
    ];
}

// 4. Catalogue des produits (AVEC IMAGES)
// Pas de virgule après le dernier élément du tableau !
$catalogue_config = [
    1 => ['name' => 'Grain Jigin', 'stock' => 50, 'price' => 150, 'image' => 'images/250g.jpeg'],
    2 => ['name' => 'Dembaya', 'stock' => 35, 'price' => 3000, 'image' => 'images/50Kg.jpeg'],
    3 => ['name' => 'Nyènajè', 'stock' => 35, 'price' => 5500, 'image' => 'images/100Kg.jpeg']
];

if (!isset($_SESSION['stocks'])) {
    $_SESSION['stocks'] = $catalogue_config;
}

// 5. Initialisation du canal des commandes
if (!isset($_SESSION['commandes_recues'])) {
    $_SESSION['commandes_recues'] = [];
}
?>