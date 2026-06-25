<?php
// install.php - Script d'installation automatique
echo "<h1>Installation de PoultryTracker</h1>";

$host = 'localhost';
$port = 3306;
$username = 'root';
$password = '';

try {
    // Connexion à MySQL sans base de données
    $conn = new PDO("mysql:host=$host;port=$port", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (isset($_GET['action']) && $_GET['action'] == 'create_db') {
        // Créer la base de données
        $conn->exec("CREATE DATABASE IF NOT EXISTS poultrytracker");
        echo "<p style='color:green'>✓ Base de données 'poultrytracker' créée avec succès !</p>";
        
        // Se connecter à la nouvelle base
        $conn->exec("USE poultrytracker");
        
        // Lire et exécuter le fichier SQL
        $sqlFile = file_get_contents('sql/database.sql');
        $conn->exec($sqlFile);
        echo "<p style='color:green'>✓ Tables créées avec succès !</p>";
        echo "<p>✅ Installation terminée !</p>";
        echo "<p><a href='login.php'>Aller à la page de connexion →</a></p>";
        
    } elseif (isset($_GET['action']) && $_GET['action'] == 'create_tables') {
        $conn->exec("USE poultrytracker");
        $sqlFile = file_get_contents('sql/database.sql');
        $conn->exec($sqlFile);
        echo "<p style='color:green'>✓ Tables créées avec succès !</p>";
        echo "<p><a href='login.php'>Aller à la page de connexion →</a></p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Erreur : " . $e->getMessage() . "</p>";
}
?>