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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Produits - Takami</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: url('images/Lumière.png') no-repeat center center fixed; background-size: cover; display: flex; flex-direction: column; align-items: center; }
        .container-main { width: 100%; display: flex; justify-content: center; align-items: center; padding: 40px 20px; flex-grow: 1; }
        .overlay { width: 100%; max-width: 1100px; }
        h1 { text-align: center; color: #2e7d32; font-size: 45px; margin-bottom: 10px; }
        p.subtitle { text-align: center; margin-bottom: 50px; color: black; }
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; width: 100%; }
        
        .card { background: white; border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: 0.4s; position: relative; overflow: hidden; }
        .card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        
        .card img { width: 100%; height: 250px; object-fit: contain; background: #fff; border-radius: 15px; margin-bottom: 15px; }
        
        /* Description professionnelle sous le titre */
        .desc-text { 
            font-size: 0.85rem; color: #666; height: 0; opacity: 0; overflow: hidden; 
            transition: all 0.4s ease; margin-bottom: 10px; line-height: 1.4;
        }
        .card:hover .desc-text { height: 60px; opacity: 1; }

        .card h3 { margin-bottom: 10px; }
        .price { color: #2e7d32; font-weight: bold; margin-bottom: 10px; }
        .stock-info { font-size: 0.9rem; color: #555; margin-bottom: 10px; }
        
        .btn { background: #2e7d32; color: white; padding: 10px 20px; border-radius: 25px; border: none; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn:hover { background: #1b5e20; }
        .btn-disabled { background: #ccc; color: #666; padding: 10px 20px; border-radius: 25px; border: none; cursor: not-allowed; width: 100%; }
        .alert-panier { position: fixed; bottom: 30px; right: 30px; background: #2e7d32; color: white; padding: 15px 25px; border-radius: 10px; z-index: 10000; }

        /* RESPONSIVITÉ AJOUTÉE */
        @media (max-width: 600px) {
            h1 { font-size: 32px; }
            .products { grid-template-columns: 1fr; }
            .card { width: 100%; }
        }
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
                $nom = htmlspecialchars($p['nom']);
            ?>
                <div class="card">
                    <img src="images/<?= htmlspecialchars($p['image']) ?>" alt="<?= $nom ?>">
                    <h3><?= $nom ?></h3>
                    
                    <div class="desc-text">
                        <?php
                        if ($nom === 'Grain Jigin') {
                            echo "Conçu pour vos moments de convivialité, TAKAMI Grain Jigi offre une combustion longue durée et une chaleur maîtrisée. Idéal pour partager un thé ou passer de bons moments entre proches.";
                        } elseif ($nom === 'Dembaya') {
                            echo "Pensé pour le quotidien, TAKAMI Dembaya offre une combustion régulière, une chaleur durable et peu de fumée. Une solution économique, pratique et respectueuse de l’environnement.";
                        } elseif ($nom === 'Nyènajè') {
                            echo "Idéal pour les mariages, baptêmes et grandes réceptions, TAKAMI Nyènajè offre une combustion puissante et une longue autonomie. Parfait pour les cuissons en grande quantité, avec une énergie performante et écologique.";
                        } else {
                            echo "Charbon naturel TAKAMI. Éco-responsable, longue combustion, sans fumée pour un confort optimal au quotidien.";
                        }
                        ?>
                    </div>

                    <div class="price"><?= number_format($p['prix'], 0, ',', ' ') ?> FCFA</div>
                    
                    <?php if ($stock > 0): ?>
                        <p class="stock-info">En stock : <?= $stock ?></p>
                        <form action="" method="POST">
                            <input type="hidden" name="action_produit" value="ajouter">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="name" value="<?= $nom ?>">
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

<footer style="background: #2e3d26; color: white; padding: 40px 20px; text-align: center; width: 100%;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h3>Contactez TAKAMI</h3>
        <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px; flex-wrap: wrap;">
            <p>📍 Bamako, Mali</p>
            <p>📞 +223 90 00 31 00</p>
            <p>✉️ TAKAMI223@gmail.com</p>
        </div>
        <div style="margin-top: 20px; border-top: 1px solid #4CAF50; padding-top: 15px; font-size: 14px; opacity: 0.8;">
            &copy; 2026 TAKAMI - Charbon Écologique. Tous droits réservés.
        </div>
    </div>
</footer>

<?php if (!empty($message_confirmation)): ?>
    <div class="alert-panier"><?php echo $message_confirmation; ?></div>
<?php endif; ?>

</body>
</html>