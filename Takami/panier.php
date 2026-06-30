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

        // RECHERCHE INTELLIGENTE : On cherche le produit par sa clé directe OU dans le tableau
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

        // Si le produit est trouvé, on applique la modification de quantité
        if ($target_key !== null && is_array($_SESSION['panier'][$target_key])) {
            $_SESSION['panier'][$target_key]['quantity'] += $change;
            
            // Si la quantité tombe à 0 ou moins, on retire automatiquement le produit
            if ($_SESSION['panier'][$target_key]['quantity'] <= 0) {
                unset($_SESSION['panier'][$target_key]);
            }
        }
    }
    
    // ACTION 2 : Suppression totale du produit du panier
    if (isset($_POST['action_supprimer'])) {
        $id = $_POST['product_id'];
        
        // On supprime par la clé directe si elle existe
        if (isset($_SESSION['panier'][$id])) {
            unset($_SESSION['panier'][$id]);
        } else {
            // Sinon on cherche l'ID à l'intérieur du tableau pour nettoyer proprement
            foreach ($_SESSION['panier'] as $key => $item) {
                if (is_array($item) && isset($item['id']) && $item['id'] == $id) {
                    unset($_SESSION['panier'][$key]);
                    break;
                }
            }
        }
    }
    
    // Redirection propre pour éviter le renvoi de formulaire en rafraîchissant
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
        :root {
            --green-primary: #5d7c4d;
            --text-dark: #333;
            --bg-card: rgba(255, 255, 255, 0.85);
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/panier2.jpeg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 130px; /* espace pour la navbar */
        }

        .container { 
            width: 95%; 
            max-width: 950px; 
            text-align: center; 
            padding: 20px 0;
        }

        h1 { 
            font-family: serif; 
            font-size: 3.5rem; 
            color: #2e3d26; 
            margin-bottom: 10px; 
            text-shadow: 2px 2px 4px rgba(255,255,255,0.7);
        }
        
        .subtitle {
            color: #333;
            font-weight: 500;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.4);
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
        }

        .cart-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            backdrop-filter: blur(12px);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding-bottom: 20px; color: #444; border-bottom: 2px solid rgba(0,0,0,0.1); text-transform: uppercase; font-size: 0.9rem; }
        td { padding: 25px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }

        .product-info { display: flex; align-items: center; text-align: left; }
        .product-info img { width: 70px; height: 70px; border-radius: 12px; margin-right: 20px; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .product-info b { font-size: 1.1rem; color: #1a1a1a; }
        .product-price { font-size: 0.9rem; color: #666; margin-top: 4px; display: block; }

        .qty-controls {
            display: flex;
            align-items: center;
            border: 1px solid #bbb;
            border-radius: 8px;
            width: fit-content;
            margin: 0 auto;
            background: white;
            overflow: hidden;
        }
        .qty-btn {
            background: #eee;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--green-primary);
            transition: 0.2s;
        }
        .qty-btn:hover { background: var(--green-primary); color: white; }
        .qty-val { padding: 0 20px; font-weight: bold; font-size: 1.1rem; }

        .total-item { font-weight: bold; font-size: 1.1rem; color: #333; }

        /* Style du Bouton Supprimer */
        .btn-delete {
            background: none;
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        .btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }

        .total-row { text-align: right; margin-top: 40px; font-size: 1.4rem; border-top: 2px solid rgba(0,0,0,0.1); padding-top: 20px; }
        .total-price { font-weight: 800; color: #2e3d26; margin-left: 20px; }

        .actions { display: flex; justify-content: space-between; margin-top: 40px; }
        .btn {
            padding: 18px 40px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-continue { 
            background: rgba(255,255,255,0.8); 
            color: var(--green-primary); 
            border: 2px solid var(--green-primary); 
        }
        .btn-validate { 
            background: var(--green-primary); 
            color: white; 
            border: 2px solid var(--green-primary); 
            box-shadow: 0 4px 15px rgba(93, 124, 77, 0.4);
        }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        
        .empty-cart-msg { font-size: 1.2rem; color: #666; padding: 40px 0; font-weight: 500; }
        .inline-form { display: inline; margin: 0; padding: 0; }
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
                    <th style="text-align: center; width: 120px;">Action</th>
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
                            
                            // Récupération sécurisée des données
                            $p_id    = isset($item['id']) ? $item['id'] : $key;
                            $p_name  = isset($item['name']) ? $item['name'] : 'Produit Indisponible';
                            $p_price = isset($item['price']) ? (int)$item['price'] : 0;
                            $p_qty   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                            $p_image = !empty($item['image']) ? $item['image'] : 'images/default.png';

                            $itemTotal = $p_price * $p_qty;
                            $grandTotal += $itemTotal;
                ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="<?php echo $p_image; ?>" alt="<?php echo $p_name; ?>" onerror="this.src='https://via.placeholder.com/70'">
                                <div>
                                    <b><?php echo $p_name; ?></b>
                                    <span class="product-price"><?php echo number_format($p_price, 0, ',', ' '); ?> FCFA / unité</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="qty-controls">
                                <form action="panier.php" method="POST" class="inline-form">
                                    <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                    <input type="hidden" name="change" value="-1">
                                    <button type="submit" name="action_panier" class="qty-btn">-</button>
                                </form>

                                <span class="qty-val"><?php echo $p_qty; ?></span>

                                <form action="panier.php" method="POST" class="inline-form">
                                    <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                    <input type="hidden" name="change" value="1">
                                    <button type="submit" name="action_panier" class="qty-btn">+</button>
                                </form>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="total-item"><?php echo number_format($itemTotal, 0, ',', ' '); ?> FCFA</span>
                        </td>
                        <td style="text-align: center;">
                            <form action="panier.php" method="POST" class="inline-form" onsubmit="return confirm('Supprimer ce produit du panier ?');">
                                <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                <button type="submit" name="action_supprimer" class="btn-delete">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php 
                        endif;
                    endforeach; 
                endif; 

                if (!$hasItems):
                ?>
                    <tr>
                        <td colspan="4" class="empty-cart-msg">Votre panier est complètement vide. 🛒</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="total-row">
            <span>Total Panier :</span>
            <span class="total-price"><span><?php echo number_format($grandTotal, 0, ',', ' '); ?></span> FCFA</span>
        </div>
    </div>

    <div class="actions">
        <a href="produits.php" class="btn btn-continue">Continuer Mes Achats</a>
        <a href="valider.php" class="btn btn-validate">Valider la commande</a>
    </div>
</div>

</body>
</html>