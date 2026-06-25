<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();

echo "<h2>Diagnostic de la base de données</h2>";

// 1. Voir tous les utilisateurs
$result = $conn->query("SELECT id, name, email, password, role FROM users");
echo "<h3>Liste des utilisateurs :</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Role</th><th>Mot de passe (hash)</th></tr>";

$users = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td style='font-size:11px;'>" . substr($row['password'], 0, 30) . "...</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Tester la connexion avec l'email admin
$admin_email = 'Fabiendiakariaadmin@gmail.com';
echo "<h3>Test avec l'email : " . $admin_email . "</h3>";

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bindValue(1, $admin_email, SQLITE3_TEXT);
$result = $stmt->execute();
$admin = $result->fetchArray(SQLITE3_ASSOC);

if ($admin) {
    echo "<p style='color:green;'>✓ L'utilisateur EXISTE dans la base !</p>";
    echo "<p>ID: " . $admin['id'] . "</p>";
    echo "<p>Nom: " . $admin['name'] . "</p>";
    echo "<p>Email: " . $admin['email'] . "</p>";
    echo "<p>Role: " . $admin['role'] . "</p>";
    
    // Tester un mot de passe (si vous connaissez le mot de passe)
    echo "<h3>Pour vous connecter :</h3>";
    echo "<p>Utilisez l'email : <strong>" . $admin_email . "</strong></p>";
    echo "<p>Avec le mot de passe que vous avez défini pour cet utilisateur</p>";
    
} else {
    echo "<p style='color:red;'>✗ L'utilisateur N'EXISTE PAS dans la base !</p>";
    echo "<p>Voici comment l'ajouter :</p>";
    
    // Proposer d'ajouter l'admin
    $default_password = password_hash("admin123", PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Administrateur', ?, ?, 'admin')");
    $insert->bindValue(1, $admin_email, SQLITE3_TEXT);
    $insert->bindValue(2, $default_password, SQLITE3_TEXT);
    
    if ($insert->execute()) {
        echo "<p style='color:green;'>✓ Admin ajouté avec succès !</p>";
        echo "<p>Email : <strong>" . $admin_email . "</strong></p>";
        echo "<p>Mot de passe : <strong>admin123</strong></p>";
        echo "<p><a href='login.php'>Aller à la page de connexion</a></p>";
    } else {
        echo "<p style='color:red;'>Erreur lors de l'ajout</p>";
    }
}

echo "<br><a href='login.php'>Retour à la connexion</a>";
?>