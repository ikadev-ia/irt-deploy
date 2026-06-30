<?php 
global $conn;

// 1. NOUVEAU : Ajout automatique de 1 au stock au clic
if (isset($_GET['ajouter_stock'])) {
    $id = (int)$_GET['ajouter_stock'];
    $conn->query("UPDATE produits SET Quantité = Quantité + 1 WHERE id = $id");
    // Redirection pour nettoyer l'URL après l'action
    header("Location: ?page=liste_produit");
    exit();
}

// 2. Suppression
if(isset($_GET['supprimer'])) { $conn->query("DELETE FROM produits WHERE id=".$_GET['supprimer']); }

// 3. Ajout de nouveau produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = $conn->real_escape_string($_POST['nom']);
    $prix = (float)$_POST['prix'];
    $qte = (int)$_POST['Quantité'];
    $img = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "images/".$img);
    $conn->query("INSERT INTO produits (nom, prix, Quantité, image) VALUES ('$nom', $prix, $qte, '$img')");
}
?>

<?php 
$alertes = $conn->query("SELECT nom, Quantité FROM produits WHERE Quantité < 5");
if ($alertes->num_rows > 0): ?>
    <div class="card" style="border-left:5px solid #d32f2f; background:#fff5f5;">
        <h3 style="color:#d32f2f;">⚠️ Alerte Stock Faible</h3>
        <?php while($a = $alertes->fetch_assoc()): ?>
            <p>Le produit <b><?= $a['nom'] ?></b> n'a plus que <?= $a['Quantité'] ?> unité(s) en stock.</p>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Ajouter un Produit</h3>
    <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px;">
        <input type="text" name="nom" placeholder="Nom" required style="padding:10px; border-radius:8px; border:1px solid #ddd;">
        <input type="number" name="prix" placeholder="Prix" required style="padding:10px; border-radius:8px; border:1px solid #ddd;">
        <input type="number" name="Quantité" placeholder="Stock" required style="padding:10px; border-radius:8px; border:1px solid #ddd;">
        <input type="file" name="image" required>
        <button type="submit" name="ajouter" style="background:var(--primary); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">Valider</button>
    </form>
</div>

<div class="card">
    <h3>Catalogue</h3>
    <table style="width:100%; border-collapse: collapse;">
        <tr style="text-align:left; color:#888;"><th>Image</th><th>Nom</th><th>Prix</th><th>Stock</th><th>Action</th></tr>
        <?php $res = $conn->query("SELECT * FROM produits");
        while($p = $res->fetch_assoc()): ?>
        <tr style="border-bottom:1px solid #eee; <?= ($p['Quantité'] == 0) ? 'background:#ffebee;' : '' ?>">
            <td style="padding:10px;"><img src="images/<?= $p['image'] ?>" width="50"></td>
            <td><?= $p['nom'] ?> <?= ($p['Quantité'] == 0) ? '<span style="color:red; font-size:12px;">(RUPTURE)</span>' : '' ?></td>
            <td><?= $p['prix'] ?> FCFA</td>
            <td><?= $p['Quantité'] ?></td>
            <td>
                <a href="?page=liste_produit&ajouter_stock=<?= $p['id'] ?>" style="background:#4caf50; color:white; padding:5px 10px; border-radius:5px; text-decoration:none; font-size:12px;">Ajouter +1</a>
                <a href="?page=liste_produit&supprimer=<?= $p['id'] ?>" style="color:red; margin-left:10px; text-decoration:none;">Supprimer</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>