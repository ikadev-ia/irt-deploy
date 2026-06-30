<?php global $conn; ?>
<div class="card">
    <h3>Gestion des Comptes Clients</h3>
    <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <tr style="background:#f4f7f6; color:#2e7d32;">
            <th style="padding:15px; text-align:left;">Nom</th>
            <th style="padding:15px; text-align:left;">Email</th>
            <th style="padding:15px; text-align:left;">Inscription</th>
            <th style="padding:15px; text-align:left;">Dernière connexion</th>
            <th style="padding:15px; text-align:left;">Mot de passe (Hash)</th>
        </tr>
        <?php 
        // On récupère toutes les colonnes nécessaires
        $resC = $conn->query("SELECT nom, email, password, date_inscription, derniere_connexion FROM clients");
        
        while($c = $resC->fetch_assoc()): ?>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:15px; font-weight: 500;"><?= htmlspecialchars($c['nom']) ?></td>
            <td style="padding:15px;"><?= htmlspecialchars($c['email']) ?></td>
            <td style="padding:15px; color:#555;"><?= $c['date_inscription'] ?></td>
            <td style="padding:15px; color:#2e7d32; font-weight:bold;">
                <?= $c['derniere_connexion'] ?? 'Jamais connecté' ?>
            </td>
            <td style="padding:15px; font-family: monospace; font-size: 12px; color: #555;">
                <?= substr($c['password'], 0, 15) . "..." ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>