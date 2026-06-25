<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Vérifier que l'utilisateur existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        $new_password = 'password123'; // ou générer aléatoire
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);
        $message = "Mot de passe réinitialisé à : <strong>$new_password</strong>. L'utilisateur devra le changer lors de la prochaine connexion.";
    } else {
        $message = "Utilisateur non trouvé ou administrateur.";
    }
} else {
    $message = "ID invalide.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation mot de passe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Inter',sans-serif;background:#f0f2f5;display:flex;justify-content:center;align-items:center;height:100vh;}
        .card{background:white;border-radius:24px;padding:30px;max-width:500px;text-align:center;}
        .btn{background:#f59e0b;color:white;padding:10px 20px;border-radius:30px;text-decoration:none;display:inline-block;margin-top:20px;}
    </style>
</head>
<body>
<div class="card">
    <h2>Réinitialisation</h2>
    <p><?php echo $message; ?></p>
    <a href="admin_users.php" class="btn">Retour à la gestion des utilisateurs</a>
</div>
</body>
</html>