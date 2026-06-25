<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($token) || empty($action)) {
    die("Paramètres manquants.");
}

if ($action == 'confirm') {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE confirmation_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $update = $conn->prepare("UPDATE users SET status = 'active', confirmation_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        echo "<h2 style='color: green;'>✅ Inscription confirmée !</h2>";
        echo "<p>L'utilisateur <strong>" . htmlspecialchars($user['name']) . "</strong> peut maintenant se connecter.</p>";
    } else {
        echo "<h2 style='color: red;'>❌ Utilisateur non trouvé ou déjà activé</h2>";
    }
} elseif ($action == 'reject') {
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE confirmation_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete->execute([$user['id']]);
        echo "<h2 style='color: orange;'>❌ Inscription refusée</h2>";
        echo "<p>La demande d'inscription a été supprimée.</p>";
    } else {
        echo "<h2 style='color: red;'>❌ Aucune demande trouvée</h2>";
    }
} else {
    echo "<h2 style='color: red;'>❌ Action invalide</h2>";
}
?>

<br>
<a href="login.php">Retour à la page de connexion</a>