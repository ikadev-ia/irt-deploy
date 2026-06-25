<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();

try {
    // Table des transactions
    $conn->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER,
        type TEXT NOT NULL, -- 'depense' ou 'revenu'
        category TEXT NOT NULL, -- 'nourriture', 'vaccins', 'materiel', 'vente_poulets', 'vente_oeufs'
        amount REAL NOT NULL,
        description TEXT,
        date DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(batch_id) REFERENCES batches(id) ON DELETE SET NULL
    )");
    echo "✅ Table 'transactions' créée.<br>";
    
    // Ajouter des colonnes prix unitaire et coût alimentaire dans batches (optionnel)
    $result = $conn->query("PRAGMA table_info(batches)");
    $cols = [];
    while ($row = $result->fetch()) $cols[] = $row['name'];
    if (!in_array('prix_unitaire', $cols)) {
        $conn->exec("ALTER TABLE batches ADD COLUMN prix_unitaire REAL DEFAULT 0");
        echo "✅ Colonne 'prix_unitaire' ajoutée à batches.<br>";
    }
    if (!in_array('cout_aliment_total', $cols)) {
        $conn->exec("ALTER TABLE batches ADD COLUMN cout_aliment_total REAL DEFAULT 0");
        echo "✅ Colonne 'cout_aliment_total' ajoutée à batches.<br>";
    }
    echo "<a href='dashboard.php'>Retour au tableau de bord</a>";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>