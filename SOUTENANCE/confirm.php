<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();

$token = $_GET['token'] ?? '';
$message = '';
$status = '';

if (empty($token)) {
    $message = "Token invalide";
    $status = "error";
} else {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE confirmation_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $update = $conn->prepare("UPDATE users SET status = 'active', confirmation_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        
        $message = "✅ Votre compte a été activé avec succès ! Vous pouvez maintenant vous connecter.";
        $status = "success";
    } else {
        $message = "❌ Lien invalide ou compte déjà activé";
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - Poulplume</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #14B53A, #0d8a2f);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .success { color: #16a34a; font-size: 3rem; margin-bottom: 20px; }
        .error { color: #dc2626; font-size: 3rem; margin-bottom: 20px; }
        h2 { margin-bottom: 15px; color: #1e293b; }
        p { color: #475569; margin-bottom: 20px; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #14B53A;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn:hover { background: #0d8a2f; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($status == 'success'): ?>
            <div class="success">✓</div>
            <h2>Confirmation réussie !</h2>
            <p><?php echo $message; ?></p>
            <a href="login.php" class="btn">Se connecter</a>
        <?php else: ?>
            <div class="error">✗</div>
            <h2>Erreur</h2>
            <p><?php echo $message; ?></p>
            <a href="login.php" class="btn">Retour à la connexion</a>
        <?php endif; ?>
    </div>
</body>
</html>