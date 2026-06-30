<?php
// 1. Initialisation
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$error = '';

// 2. Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ACCÈS ADMIN RAPIDE
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user'] = ['nom' => 'Administrateur', 'role' => 'admin'];
        header('Location: admin_dashboard.php');
        exit();
    }

    // ACCÈS CLIENTS (Recherche dans la base de données)
    $email = $conn->real_escape_string($username);
    $sql = "SELECT * FROM clients WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            // --- MISE À JOUR DE LA DATE ICI ---
            // On utilise NOW() pour enregistrer la date et l'heure actuelles
            $conn->query("UPDATE clients SET derniere_connexion = NOW() WHERE email = '$email'");
            
            $_SESSION['user'] = $user;

            // Redirection selon le rôle
            if (isset($user['role']) && $user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: produits.php');
            }
            exit();
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Aucun compte trouvé avec cet email.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - TAKAMI</title>
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
        .register-link { margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Connexion TAKAMI</h2>
        <?php if(!empty($error)): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur ou Email</label>
                <input type="text" name="username" placeholder="ex: client@test.com" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Se connecter</button>
        </form>
        
        <div class="register-link">
            <p>Pas encore de compte ? <a href="creer_un_compte.php">Inscrivez-vous ici</a></p>
        </div>
    </div>
</body>
</html>