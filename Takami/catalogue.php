<?php
// On récupère les produits depuis votre base de données
$result = $conn->query("SELECT * FROM produits");
$produits = $result->fetch_all(MYSQLI_ASSOC);
?>