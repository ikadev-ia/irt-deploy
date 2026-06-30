"""
Script de réparation de la base de données E-Pharma.
Lance avec : python fix_db.py
"""
import sqlite3
import os

db_path = os.path.join(os.path.dirname(__file__), 'db.sqlite3')
print(f"\nBase de données : {db_path}")

conn = sqlite3.connect(db_path)
cursor = conn.cursor()

# Voir les colonnes actuelles
cursor.execute("PRAGMA table_info(livraison_livraison)")
colonnes = [row[1] for row in cursor.fetchall()]
print(f"Colonnes actuelles : {colonnes}")

# Ajouter les colonnes manquantes
nouvelles_colonnes = [
    ("statut", "VARCHAR(20) DEFAULT 'assignee'"),
    ("heure_depart", "DATETIME NULL"),
    ("heure_arrivee", "DATETIME NULL"),
    ("notes_livreur", "TEXT DEFAULT ''"),
    ("latitude", "REAL NULL"),
    ("longitude", "REAL NULL"),
    ("derniere_maj_position", "DATETIME NULL"),
]

for col_name, col_def in nouvelles_colonnes:
    if col_name not in colonnes:
        try:
            cursor.execute(f"ALTER TABLE livraison_livraison ADD COLUMN {col_name} {col_def}")
            print(f"Colonne ajoutée : {col_name}")
        except Exception as e:
            print(f"Erreur {col_name} : {e}")
    else:
        print(f"Colonne existe déjà : {col_name}")

conn.commit()
conn.close()
print("\nBase de données réparée ! Lance maintenant : python setup_livraison.py\n")
