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
    $total_general = $facture['total'];
} else {
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: url('images/panier2.jpeg') no-repeat center center fixed; background-size: cover; margin: 0; padding: 0; color: var(--text-main); }
        
        .web-layout { display: flex; flex-direction: column; align-items: center; min-height: 100vh; width: 100%; padding: 130px 15px 40px 15px; box-sizing: border-box; }
        
        .invoice-container { width: 100%; max-width: 850px; background: #ffffff !important; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); overflow: hidden; }
        .invoice-top-banner { background: #2e7d32 !important; color: #ffffff !important; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .invoice-logo-img { height: 50px; width: auto; }
        .banner-right { text-align: right; }
        .banner-right h1 { margin: 0; font-size: 1.5rem; }
        
        .invoice-body { padding: 25px; }
        .grid-parties { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th { background: #5d7c4d !important; color: #ffffff !important; text-align: left; padding: 12px; font-size: 0.85rem; }
        td { padding: 12px; border-bottom: 1px solid #eee !important; font-size: 0.9rem; }
        
        .totals-section { display: flex; justify-content: flex-end; margin-top: 20px; }
        .grand-total-row { font-size: 1.2rem; font-weight: bold; color: var(--green-dark) !important; }
        
        .invoice-actions { display: flex; justify-content: center; gap: 10px; margin-top: 30px; flex-wrap: wrap; }
        .btn { padding: 12px 20px; border-radius: 30px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; font-size: 0.9rem; }
        .btn-back { background: white; color: var(--green-primary); border: 2px solid var(--green-primary); }
        .btn-print { background: #5d7c4d; color: white; }
        .btn-pdf { background: var(--green-primary); color: white; }

        @media (max-width: 600px) {
            .web-layout { padding-top: 100px; }
            .invoice-top-banner { flex-direction: column; text-align: center; }
            .banner-right { text-align: center; }
            .invoice-body { padding: 15px; }
        }

        @media print {
            .navbar, .invoice-actions, .navbar-spacer { display: none !important; }
            body { background: none !important; }
            .invoice-container { box-shadow: none !important; width: 100% !important; }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="web-layout">
    <div class="invoice-container" id="invoice-block">
        <div class="invoice-top-banner">
            <img src="images/logo.png" alt="TAKAMÍ" class="invoice-logo-img">
            <div class="banner-right"><h1>FACTURE</h1><p><b>Date :</b> <?= $date_facture; ?></p></div>
        </div>
        <div class="invoice-body">
            <div class="grid-parties">
                <div><h3>Émetteur</h3><p>Takami / Aw Ka Charbon Inc.</p></div>
                <div><h3>Client</h3><p><b>Nom : </b> <?= htmlspecialchars($nom_client); ?><br><b>Paiement : </b> <?= htmlspecialchars($moyen_paiement); ?></p></div>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Produit</th><th style="text-align: center;">PU</th><th style="text-align: center;">Qté</th><th style="text-align: right;">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($panier_a_afficher as $item): ?>
                            <tr><td><?= htmlspecialchars($item['name']); ?></td><td style="text-align: center;"><?= number_format($item['price'], 0, ',', ' '); ?></td><td style="text-align: center;"><?= $item['quantity']; ?></td><td style="text-align: right;"><?= number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> FCFA</td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="totals-section">
                <div class="grand-total-row">Total Général : <?= number_format($total_general, 0, ',', ' '); ?> FCFA</div>
            </div>
        </div>
    </div>
    
    <div class="invoice-actions">
        <a href="produits.php" class="btn btn-back">Retour</a>
        <button class="btn btn-print" onclick="window.print()">Imprimer</button>
        <button class="btn btn-pdf" onclick="downloadPDF()">Enregistrer PDF</button>
    </div>
</div>

<script>
function downloadPDF() {
    const element = document.getElementById('invoice-block');
    const opt = { 
        margin: 5, 
        filename: 'Facture_Takami.pdf', 
        image: { type: 'jpeg', quality: 0.98 }, 
        html2canvas: { scale: 2, scrollY: 0 }, 
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } 
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>