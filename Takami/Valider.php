<?php 
require_once 'config.php';

// Sécurité : Si le client n'est pas connecté ou panier vide, on redirige
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['panier'])) {
    header('Location: produits.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Valider ma commande - Takami</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
        body { background: url('images/Lumière.png') center/cover no-repeat fixed; min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
        .container-main { width: 100%; display: flex; justify-content: center; align-items: center; flex-grow: 1; padding: 40px 20px; }
        .form-container { width: 100%; max-width: 500px; background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .form-container h1 { text-align: center; color: #2e7d32; font-family: serif; font-size: 2.2rem; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #555; margin-bottom: 35px; font-size: 0.95rem; }
        .input-group { margin-bottom: 22px; }
        .input-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 0.9rem; }
        .input-group input, .input-group textarea { width: 100%; padding: 14px 18px; border: 1px solid rgba(0, 0, 0, 0.15); border-radius: 15px; background: #fdfdfd; outline: none; font-size: 1rem; }
        
        .payment-selector { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 10px; margin-bottom: 20px; }
        .payment-option input[type="radio"] { display: none; }
        .payment-box { display: flex; flex-direction: column; align-items: center; background: #f5f5f5; border: 2px solid transparent; padding: 15px 10px; border-radius: 18px; cursor: pointer; transition: 0.2s; }
        .payment-box img { width: 40px; height: 40px; object-fit: contain; margin-bottom: 8px; }
        .payment-option input[type="radio"]:checked + .payment-box { background: #f1f8e9; border-color: #2e7d32; }
        
        .btn { width: 100%; padding: 16px; border: none; border-radius: 30px; background: #2e7d32; color: white; font-weight: bold; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-main">
    <div class="form-container">
        <h1>🛒 Validation</h1>
        <p class="subtitle">Connecté en tant que : <b><?php echo htmlspecialchars($_SESSION['user']['nom'] ?? 'Utilisateur'); ?></b></p>

        <form action="traitement.php" method="POST">
            <div class="input-group">
                <label>Nom complet</label>
                <input type="text" name="nom" value="<?php echo htmlspecialchars($_SESSION['user']['nom'] ?? ''); ?>" readonly>
            </div>

            <div class="input-group">
                <label for="telephone">Numéro de téléphone</label>
                <input type="tel" id="telephone" name="telephone" placeholder="Ex: 70 00 00 00" required>
            </div>
            <div class="input-group">
                <label for="adresse">Adresse exacte de livraison</label>
                <textarea id="adresse" name="adresse" rows="3" required></textarea>
            </div>

            <label>Mode de paiement</label>
            <div class="payment-selector">
                <label class="payment-option"><input type="radio" name="paiement" value="orange" required><div class="payment-box"><img src="images/orangemoney.png" alt="Orange Money"><span>OM</span></div></label>
                <label class="payment-option"><input type="radio" name="paiement" value="wave"><div class="payment-box"><img src="images/wave.jpeg" alt="Wave"><span>Wave</span></div></label>
                <label class="payment-option"><input type="radio" name="paiement" value="carte"><div class="payment-box"><img src="images/carte.png" alt="Carte"><span>Carte</span></div></label>
                <label class="payment-option"><input type="radio" name="paiement" value="cash"><div class="payment-box"><img src="images/cash.png" alt="Cash"><span>Cash</span></div></label>
            </div>

            <button type="submit" class="btn">Confirmer la commande</button>
        </form>
    </div>
</div>
</body>
</html>