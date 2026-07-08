<?php
// 1. Gestion de la session et des actions de modification
session_start();

// Si le panier n'existe pas, on l'initialise
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Code PHP qui intercepte les clics sur les boutons (+, - ou Supprimer)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION 1 : Modification de quantité (+ ou -)
    if (isset($_POST['action_panier'])) {
        $id = $_POST['product_id'];
        $change = (int)$_POST['change'];

        $target_key = null;
        if (isset($_SESSION['panier'][$id])) {
            $target_key = $id;
        } else {
            foreach ($_SESSION['panier'] as $key => $item) {
                if (is_array($item) && isset($item['id']) && $item['id'] == $id) {
                    $target_key = $key;
                    break;
                }
            }
        }

        if ($target_key !== null && is_array($_SESSION['panier'][$target_key])) {
            $_SESSION['panier'][$target_key]['quantity'] += $change;
            if ($_SESSION['panier'][$target_key]['quantity'] <= 0) {
                unset($_SESSION['panier'][$target_key]);
            }
        }
    }
    
    // ACTION 2 : Suppression totale du produit du panier
    if (isset($_POST['action_supprimer'])) {
        $id = $_POST['product_id'];
        
        if (isset($_SESSION['panier'][$id])) {
            unset($_SESSION['panier'][$id]);
        } else {
            foreach ($_SESSION['panier'] as $key => $item) {
                if (is_array($item) && isset($item['id']) && $item['id'] == $id) {
                    unset($_SESSION['panier'][$key]);
                    break;
                }
            }
        }
    }
    
    header("Location: panier.php");
    exit();
}

$panier_reel = $_SESSION['panier'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - Écologique</title>
    <style>
        :root { --green-primary: #5d7c4d; --text-dark: #333; --bg-card: rgba(255, 255, 255, 0.9); --danger-color: #dc3545; }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: url('images/img_panier.jpeg') no-repeat center center fixed; background-size: cover; margin: 0; min-height: 100vh; color: var(--text-dark); display: flex; flex-direction: column; align-items: center; padding-top: 100px; }

        .container { width: 95%; max-width: 950px; text-align: center; padding: 20px 0; }

        h1 { font-family: serif; font-size: clamp(2rem, 5vw, 3.5rem); color: black; margin-bottom: 10px; }
        .subtitle { color: #333; font-weight: 500; margin-bottom: 30px; background: rgba(255,255,255,0.4); display: inline-block; padding: 5px 15px; border-radius: 20px; }

        .cart-card { background: var(--bg-card); border-radius: 20px; padding: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); backdrop-filter: blur(12px); overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th { text-align: left; padding-bottom: 20px; color: #444; border-bottom: 2px solid rgba(0,0,0,0.1); font-size: 0.9rem; }
        td { padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }

        .product-info { display: flex; align-items: center; }
        .product-info img { width: 60px; height: 60px; border-radius: 12px; margin-right: 15px; object-fit: cover; }
        
        .qty-controls { display: flex; align-items: center; justify-content: center; border: 1px solid #bbb; border-radius: 8px; width: fit-content; margin: 0 auto; background: white; }
        .qty-btn { background: #eee; border: none; padding: 8px 12px; cursor: pointer; font-weight: bold; color: var(--green-primary); }
        .qty-val { padding: 0 15px; font-weight: bold; }

        .btn-delete { background: none; border: 1px solid var(--danger-color); color: var(--danger-color); padding: 5px 10px; border-radius: 8px; cursor: pointer; }
        .btn-delete:hover { background: var(--danger-color); color: white; }

        .total-row { text-align: right; margin-top: 30px; font-size: 1.2rem; font-weight: bold; }
        .actions { display: flex; justify-content: space-between; margin-top: 30px; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 15px 30px; border-radius: 40px; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .btn-continue { background: white; color: var(--green-primary); border: 2px solid var(--green-primary); }
        .btn-validate { background: var(--green-primary); color: white; border: 2px solid var(--green-primary); }

        @media (max-width: 600px) {
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>Mon Panier</h1>
    <p class="subtitle">Voici les articles dans votre panier 🔥</p>

    <div class="cart-card">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th style="text-align: center;">Quantité</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotal = 0;
                $hasItems = false;
                if (!empty($panier_reel) && is_array($panier_reel)): 
                    foreach ($panier_reel as $key => $item): 
                        if (is_array($item)):
                            $hasItems = true;
                            $p_id = $item['id'] ?? $key;
                            $p_name = $item['name'] ?? 'Produit';
                            $p_price = (int)($item['price'] ?? 0);
                            $p_qty = (int)($item['quantity'] ?? 1);
                            $p_image = $item['image'] ?? 'default.png';
                            $itemTotal = $p_price * $p_qty;
                            $grandTotal += $itemTotal;
                ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="images/<?= htmlspecialchars($p_image) ?>" alt="<?= htmlspecialchars($p_name) ?>">
                                <div><b><?= htmlspecialchars($p_name) ?></b><br><small><?= number_format($p_price, 0, ',', ' ') ?> FCFA</small></div>
                            </div>
                        </td>
                        <td>
                            <div class="qty-controls">
                                <form action="" method="POST" class="inline-form">
                                    <input type="hidden" name="product_id" value="<?= $p_id ?>"><input type="hidden" name="change" value="-1">
                                    <button type="submit" name="action_panier" class="qty-btn">-</button>
                                </form>
                                <span class="qty-val"><?= $p_qty ?></span>
                                <form action="" method="POST" class="inline-form">
                                    <input type="hidden" name="product_id" value="<?= $p_id ?>"><input type="hidden" name="change" value="1">
                                    <button type="submit" name="action_panier" class="qty-btn">+</button>
                                </form>
                            </div>
                        </td>
                        <td style="text-align: right;"><strong><?= number_format($itemTotal, 0, ',', ' ') ?> FCFA</strong></td>
                        <td style="text-align: center;">
                            <form action="" method="POST" onsubmit="return confirm('Supprimer ?');">
                                <input type="hidden" name="product_id" value="<?= $p_id ?>">
                                <button type="submit" name="action_supprimer" class="btn-delete">X</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; endforeach; endif; 
                if (!$hasItems): echo '<tr><td colspan="4" style="text-align:center; padding:40px;">Votre panier est vide.</td></tr>'; endif; ?>
            </tbody>
        </table>

        <div class="total-row">
            Total : <?= number_format($grandTotal, 0, ',', ' ') ?> FCFA
        </div>
    </div>

    <div class="actions">
        <a href="produits.php" class="btn btn-continue">Continuer Mes Achats</a>
        <a href="Valider.php" class="btn btn-validate">Valider la commande</a>
    </div>
</div>
</body>
</html>