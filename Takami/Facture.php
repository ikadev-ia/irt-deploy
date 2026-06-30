<?php
// Démarrage de session sécurisé
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// RÉCUPÉRATION AUTOMATISÉE DES DONNÉES
if (isset($_SESSION['derniere_facture'])) {
    $facture = $_SESSION['derniere_facture'];
    $nom_client = $facture['nom'];
    $moyen_paiement = $facture['paiement'];
    $date_facture = $facture['date'];
    $panier_a_afficher = $facture['articles'];
    $sous_total = $facture['total'];
    $total_general = $facture['total'];
} else {
    // Redirection si aucune commande n'est en cours
    header('Location: produits.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - Takami</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root { --green-dark: #1b5e20; --green-primary: #2e7d32; --text-main: #333; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: url('images/panier3.png') no-repeat center center fixed; background-size: cover; margin: 0; padding: 0; color: var(--text-main); box-sizing: border-box; }
        .web-layout { display: flex; flex-direction: column; align-items: center; min-height: 100vh; width: 100%; }
        .navbar-spacer { height: 130px; }
        .invoice-container { width: 90%; max-width: 850px; background: #ffffff !important; border-radius: 24px; box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2); border: 1px solid #ddd; overflow: hidden; margin-bottom: 40px; box-sizing: border-box; }
        .invoice-top-banner { background: #2e7d32 !important; color: #ffffff !important; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; box-sizing: border-box; }
        .invoice-logo-img { height: 65px; width: auto; object-fit: contain; margin-bottom: 5px; }
        .banner-right { text-align: right; color: #ffffff !important; }
        .banner-right h1 { margin: 0 0 5px 0; font-size: 2rem; color: #ffffff !important; }
        .invoice-body { padding: 40px; background: #ffffff !important; }
        .grid-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .party-box h3 { margin: 0 0 12px 0; color: var(--green-dark) !important; font-size: 0.95rem; text-transform: uppercase; border-left: 3px solid var(--green-primary); padding-left: 10px; }
        .party-box p { margin: 5px 0; font-size: 0.95rem; color: #333 !important; }
        .section-title { margin: 0 0 20px 0; color: var(--green-dark) !important; font-size: 0.95rem; text-transform: uppercase; border-left: 3px solid var(--green-primary); padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #5d7c4d !important; color: #ffffff !important; text-align: left; padding: 12px 15px; font-size: 0.85rem; }
        td { padding: 15px; border-bottom: 1px solid #eeeeee !important; font-size: 0.95rem; color: #333 !important; }
        .totals-section { display: flex; justify-content: flex-end; margin-top: 20px; }
        .totals-table { width: 280px; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 0.95rem; color: #333 !important; }
        .grand-total-row { border-top: 2px solid #333 !important; padding-top: 15px; font-size: 1.2rem; font-weight: bold; color: var(--green-dark) !important; }
        .invoice-actions { display: flex; justify-content: center; gap: 15px; margin-bottom: 60px; width: 100%; }
        .btn { padding: 12px 25px; border-radius: 30px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; }
        .btn-back { background: #ffffff; color: var(--green-primary); border: 2px solid var(--green-primary); }
        .btn-print { background: #5d7c4d; color: white; }
        .btn-pdf { background: var(--green-primary); color: white; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="navbar-spacer"></div>
<div class="web-layout">
    <div class="invoice-container" id="invoice-block">
        <div class="invoice-top-banner">
            <div class="invoice-logo-block"><img src="images/logo.png" alt="TAKAMÍ" class="invoice-logo-img"></div>
            <div class="banner-right"><h1>FACTURE</h1><p><b>Date :</b> <?php echo $date_facture; ?></p></div>
        </div>
        <div class="invoice-body">
            <div class="grid-parties">
                <div class="party-box"><h3>Émetteur</h3><p><b>Takami / Aw Ka Charbon Inc.</b></p></div>
                <div class="party-box"><h3>Client</h3><p><b>Nom : </b> <?php echo htmlspecialchars($nom_client); ?></p><p><b>Moyen de paiement : </b> <?php echo htmlspecialchars($moyen_paiement); ?></p></div>
            </div>
            <h3 class="section-title">Détails de la commande</h3>
            <table>
                <thead><tr><th>Produit</th><th style="text-align: center;">Prix Unitaire</th><th style="text-align: center;">Quantité</th><th style="text-align: right;">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($panier_a_afficher as $item): ?>
                        <tr><td><?php echo htmlspecialchars($item['name']); ?></td><td style="text-align: center;"><?php echo number_format($item['price'], 0, ',', ' '); ?> FCFA</td><td style="text-align: center;"><?php echo $item['quantity']; ?></td><td style="text-align: right;"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> FCFA</td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="totals-section">
                <div class="totals-table">
                    <div class="total-row grand-total-row"><span>Total Général :</span><span><?php echo number_format($total_general, 0, ',', ' '); ?> FCFA</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="invoice-actions">
        <a href="produits.php" class="btn btn-back">Retour</a>
        <button class="btn btn-print" onclick="window.print()">Imprimer</button>
        <button class="btn btn-pdf" onclick="downloadPDF()">Enregistrer en PDF</button>
    </div>
</div>
<script>
function downloadPDF() {
    const element = document.getElementById('invoice-block');
    setTimeout(() => {
        const opt = { margin: [10, 10, 10, 10], filename: 'Facture_Takami.pdf', image: { type: 'jpeg', quality: 1 }, html2canvas: { scale: 2, useCORS: true }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } };
        html2pdf().set(opt).from(element).save();
    }, 500);
}
</script>
</body>
</html>