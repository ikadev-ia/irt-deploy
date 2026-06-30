<?php
// On inclut le fichier de configuration global
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$message_confirmation = "";

// Traitement de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_produit']) && $_POST['action_produit'] === 'ajouter') {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit();
    }

    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $image = $_POST['image'] ?? '';

    if (!empty($name)) {
        if (isset($_SESSION['panier'][$name])) {
            $_SESSION['panier'][$name]['quantity'] += 1;
        } else {
            $_SESSION['panier'][$name] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'quantity' => 1
            ];
        }
        $message_confirmation = "Le produit \"" . htmlspecialchars($name) . "\" a été ajouté au panier ! 🛒";
    }
}

// Récupération des produits depuis la BDD
$result_produits = $conn->query("SELECT * FROM produits");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nos Produits - Takami</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: url('images/Lumière.png') no-repeat center center fixed; background-size: cover; display: flex; flex-direction: column; align-items: center; }
        .container-main { width: 100%; display: flex; justify-content: center; align-items: center; padding: 40px 20px; flex-grow: 1; }
        .overlay { width: 100%; max-width: 1100px; }
        h1 { text-align: center; color: #2e7d32; font-size: 45px; margin-bottom: 10px; }
        p.subtitle { text-align: center; margin-bottom: 50px; color: black; }
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; width: 100%; }
        .card { background: white; border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: 0.3s; }
        .card:hover { transform: translateY(-10px); }
        .card img { width: 100%; height: 300px; object-fit: cover; border-radius: 15px; }
        .card h3 { margin: 15px 0 5px; }
        .price { color: #2e7d32; font-weight: bold; margin-bottom: 10px; }
        .stock-info { font-size: 0.9rem; color: #555; margin-bottom: 10px; }
        .btn { background: #2e7d32; color: white; padding: 10px 20px; border-radius: 25px; border: none; cursor: pointer; width: 100%; }
        .btn-disabled { background: #ccc; color: #666; padding: 10px 20px; border-radius: 25px; border: none; cursor: not-allowed; width: 100%; }
        .alert-panier { position: fixed; bottom: 30px; right: 30px; background: #2e7d32; color: white; padding: 15px 25px; border-radius: 10px; z-index: 10000; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-main">
    <div class="overlay">
        <h1>Nos Produits</h1>
        <p class="subtitle">Découvrez notre gamme de charbon écologique 🌿</p>

        <div class="products">
            <?php while ($p = $result_produits->fetch_assoc()): 
                $stock = (int)$p['Quantité'];
            ?>
                <div class="card">
                    <img src="images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                    <h3><?= htmlspecialchars($p['nom']) ?></h3>
                    <div class="price"><?= number_format($p['prix'], 0, ',', ' ') ?> FCFA</div>
                    
                    <?php if ($stock > 0): ?>
                        <p class="stock-info">En stock : <?= $stock ?></p>
                        <form action="" method="POST">
                            <input type="hidden" name="action_produit" value="ajouter">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($p['nom']) ?>">
                            <input type="hidden" name="price" value="<?= $p['prix'] ?>">
                            <input type="hidden" name="image" value="<?= htmlspecialchars($p['image']) ?>">
                            <button type="submit" class="btn">Ajouter</button>
                        </form>
                    <?php else: ?>
                        <p style="color:red; font-weight:bold; margin-bottom:10px;">RUPTURE</p>
                        <button type="button" class="btn-disabled" disabled>Indisponible</button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php if (!empty($message_confirmation)): ?>
    <div class="alert-panier"><?php echo $message_confirmation; ?></div>
<?php endif; ?>

</body>
</html>