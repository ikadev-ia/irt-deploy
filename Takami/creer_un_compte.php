<?php
// 1. Connexion à la base de données
require_once 'config.php';

// Initialisation de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Sécurisé
    $numero = $conn->real_escape_string($_POST['numero']);

    // Vérifier si l'utilisateur existe déjà
    $check = $conn->query("SELECT * FROM clients WHERE email = '$username'");
    
    if ($check->num_rows > 0) {
        $error = "Cet email est déjà utilisé.";
    } else {
        // Enregistrement dans la table 'clients' avec TOUTES les colonnes
        $sql = "INSERT INTO clients (nom, email, password, numero) VALUES ('$name', '$username', '$password', '$numero')";
        
        if ($conn->query($sql)) {
            // Connexion automatique après inscription
            $_SESSION['user'] = ['name' => $name, 'username' => $username];
            header('Location: produits.php');
            exit();
        } else {
            $error = "Erreur lors de l'inscription : " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - TAKAMI</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: url('images/panier2.jpeg') no-repeat center center fixed; background-size: cover; height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; }
        body::before { content: ''; position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1; }
        .login-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); padding: 40px; border-radius: 25px; width: 90%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); z-index: 2; text-align: center; }
        h2 { color: #2e7d32; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-size: 14px; color: #333; font-weight: 500; }
        input { width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #ccc; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 12px; border: none; background: #2e7d32; color: white; border-radius: 25px; font-weight: 600; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .btn-login:hover { background: #1b5e20; }
        .error { color: #dc3545; font-size: 14px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Créer un compte</h2>
        <?php if(!empty($error)): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" name="name" required>
            </div>
              <div class="form-group">
                <label>Numero</label>
                <input type="text" name="numero" placeholder="Votre numero" required>
            </div>
            <div class="form-group">
                <label>Email (Nom d'utilisateur)</label>
                <input type="email" name="username" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">S'inscrire</button>
        </form>
    </div>
</body>
</html>