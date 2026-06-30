<?php
require_once 'config.php';
global $conn;

// 1. Vérification : Si le panier est vide ou utilisateur non connecté, on bloque
if (!isset($_SESSION['user']) || empty($_SESSION['panier'])) {
    header('Location: produits.php');
    exit();
}

// 2. Traitement des données du formulaire venant de Valider.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_client = htmlspecialchars($_POST['nom']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $adresse = htmlspecialchars($_POST['adresse']);
    $paiement = htmlspecialchars($_POST['paiement']);
    
    $total_facture = 0;
    $details_articles = ""; 

    // 3. Boucle sur le panier : Calcul total + Mise à jour des stocks BDD
    foreach ($_SESSION['panier'] as $item) {
        $total_facture += ($item['price'] * $item['quantity']);
        $details_articles .= $item['name'] . " (x" . $item['quantity'] . "), ";
        
        // Mise à jour du stock dans la base de données
        $sql_stock = "UPDATE produits SET Quantité = Quantité - " . (int)$item['quantity'] . " WHERE id = " . (int)$item['id'];
        $conn->query($sql_stock);
    }

    // 4. SAUVEGARDE DE LA COMMANDE DANS LA BDD
    $sql_cmd = "INSERT INTO commandes (nom_client, details_produits, montant_total) VALUES ('$nom_client', '$details_articles', $total_facture)";
    $conn->query($sql_cmd);

    // 5. SAUVEGARDE POUR LA FACTURE
    // C'est ici que j'ajoute 'articles' avec tout le contenu de ton panier
    $_SESSION['derniere_facture'] = [
        'nom' => $nom_client,
        'paiement' => $paiement,
        'articles' => $_SESSION['panier'], 
        'total' => $total_facture,
        'date' => date('d/m/Y H:i')
    ];

    // 6. Nettoyage du panier après traitement
    $_SESSION['panier'] = []; 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commande validée</title>
    <style>
        body { margin:0; font-family: 'Poppins', Arial; background: linear-gradient(135deg, #e8f5e9, #f5f7fa); display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { background:white; padding:40px; border-radius:25px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.1); }
        h1 { color:#2e7d32; margin-bottom:10px; }
        p { color:#555; margin-bottom:20px; }
        .btn { display:inline-block; padding:12px 25px; border-radius:25px; text-decoration:none; margin:10px; color:white; font-weight:bold; }
        .facture { background:#4CAF50; }
        .home { background:#333; }
    </style>
</head>
<body>
    <div class="box">
        <h1>✅ Commande validée</h1>
        <p>Merci <b><?php echo htmlspecialchars($_SESSION['user']['nom'] ?? 'Client'); ?></b> pour votre achat !</p>
        
        <a href="Facture.php" class="btn facture">Voir la facture</a>
        <a href="index.php" class="btn home">Retour à l’accueil</a>
    </div>
</body>
</html>