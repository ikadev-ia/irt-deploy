<?php
// Fichier de test pour diagnostiquer les problèmes de base de données
echo "<h1>Diagnostic Base de données PoultryTracker</h1>";

// 1. Vérifier si MySQL est accessible
echo "<h2>1. Test de connexion à MySQL</h2>";
try {
    $host = 'localhost';
    $port = 3306;
    $username = 'root';
    $password = '';
    
    // Test de connexion sans base de données spécifique
    $testConn = new PDO("mysql:host=$host;port=$port", $username, $password);
    $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✓ Connexion à MySQL réussie !</p>";
    
    // 2. Vérifier si la base de données existe
    echo "<h2>2. Vérification de la base de données</h2>";
    $stmt = $testConn->query("SHOW DATABASES LIKE 'poultrytracker'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ La base de données 'poultrytracker' existe</p>";
        
        // 3. Se connecter à la base de données
        $conn = new PDO("mysql:host=$host;port=$port;dbname=poultrytracker", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 4. Vérifier les tables
        echo "<h2>3. Vérification des tables</h2>";
        $tables = ['users', 'batches', 'daily_tracking', 'vaccines', 'batch_vaccines', 'notifications'];
        $allTablesExist = true;
        
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color:green'>✓ Table '$table' existe</p>";
            } else {
                echo "<p style='color:red'>✗ Table '$table' manquante</p>";
                $allTablesExist = false;
            }
        }
        
        // 5. Compter les utilisateurs
        echo "<h2>4. Données existantes</h2>";
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<p>👥 Nombre d'utilisateurs : " . $result['count'] . "</p>";
        
        if ($result['count'] == 0) {
            echo "<p style='color:orange'>⚠ Aucun utilisateur trouvé. Veuillez importer database.sql</p>";
        } else {
            $stmt = $conn->query("SELECT id, name, email, role FROM users");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th></tr>";
            while ($user = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['name'] . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // 6. Si des tables manquent, proposer de les créer
        if (!$allTablesExist) {
            echo "<h2 style='color:red'>⚠ Tables manquantes !</h2>";
            echo "<p>Veuillez importer le fichier SQL suivant dans phpMyAdmin :</p>";
            echo "<pre>sql/database.sql</pre>";
            echo "<p>Ou exécutez ce script de création :</p>";
            echo "<button onclick='createTables()'>Créer les tables automatiquement</button>";
        }
        
    } else {
        echo "<p style='color:red'>✗ La base de données 'poultrytracker' n'existe pas</p>";
        echo "<p>Veuillez créer la base de données 'poultrytracker' dans phpMyAdmin</p>";
        echo "<button onclick='createDatabase()'>Créer la base de données</button>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>✗ Erreur MySQL : " . $e->getMessage() . "</p>";
    echo "<p>Solutions possibles :</p>";
    echo "<ul>";
    echo "<li>Démarrez MySQL dans XAMPP Control Panel</li>";
    echo "<li>Vérifiez que le port 3306 n'est pas utilisé</li>";
    echo "<li>Redémarrez XAMPP</li>";
    echo "</ul>";
}

// Scripts JavaScript pour créer la base de données
echo "<script>
function createDatabase() {
    window.location.href = 'install.php?action=create_db';
}

function createTables() {
    window.location.href = 'install.php?action=create_tables';
}
</script>";
?>