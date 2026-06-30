<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { header("Location: login.php"); exit(); }
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration Takami</title>
    <style>
        :root { --primary: #2e7d32; --bg: #f8f9fa; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); display: flex; margin: 0; }
        .sidebar { width: 260px; background: white; height: 100vh; border-right: 1px solid #ddd; padding: 20px; position: fixed; }
        .main { margin-left: 280px; padding: 30px; width: calc(100% - 340px); }
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn-nav { display: block; padding: 12px; margin-bottom: 10px; border-radius: 12px; text-decoration: none; color: #333; font-weight: 500; transition: 0.3s; }
        .btn-nav:hover { background: #f0f7f0; color: var(--primary); }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { color: #888; font-weight: 500; padding: 15px; }
        td { background: #fff; padding: 15px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        input { padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
        button { padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Takami Admin</h2>
        <a href="?page=liste_produit" class="btn-nav">📦 Liste des Produits</a>
        <a href="?page=liste_client" class="btn-nav">👥 Liste des Clients</a>
        <a href="?page=liste_commande" class="btn-nav">🛒 Liste des Commandes</a>
        <a href="?logout=1" class="btn-nav" style="color:red;">Déconnexion</a>
    </div>
    <div class="main">
        <?php
        $page = $_GET['page'] ?? 'liste_produit';
        $fichiers = ['liste_produit', 'liste_client', 'liste_commande'];
        if (in_array($page, $fichiers)) { include($page . ".php"); }
        else { echo "<div class='card'><h3>Bienvenue</h3><p>Sélectionnez une section.</p></div>"; }
        ?>
    </div>
</body>
</html>