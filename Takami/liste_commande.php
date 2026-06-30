<?php 
global $conn; 
// On tente de récupérer les données
$query = "SELECT id, nom_client, details_produits, montant_total, date_commande FROM commandes ORDER BY date_commande DESC";
$resCmd = $conn->query($query);
?>

<div class="card">
    <h3>🛒 Historique des Commandes</h3>
    
    <?php if (!$resCmd): ?>
        <p style="color:red;">Erreur SQL : <?= $conn->error ?></p>
    <?php elseif ($resCmd->num_rows === 0): ?>
        <p>Aucune commande enregistrée pour le moment.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
            <tr style="background:#f4f7f6; color:#2e7d32;">
                <th style="padding:15px; text-align:left;">ID</th>
                <th style="padding:15px; text-align:left;">Client</th>
                <th style="padding:15px; text-align:left;">Détails</th>
                <th style="padding:15px; text-align:left;">Total</th>
                <th style="padding:15px; text-align:left;">Date</th>
            </tr>
            <?php while($cmd = $resCmd->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:15px;"><?= $cmd['id'] ?></td>
                <td style="padding:15px; font-weight:bold;"><?= htmlspecialchars($cmd['nom_client']) ?></td>
                <td style="padding:15px;"><?= htmlspecialchars($cmd['details_produits']) ?></td>
                <td style="padding:15px; color:#2e7d32;"><?= number_format($cmd['montant_total'], 0, ',', ' ') ?> FCFA</td>
                <td style="padding:15px;"><?= $cmd['date_commande'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>