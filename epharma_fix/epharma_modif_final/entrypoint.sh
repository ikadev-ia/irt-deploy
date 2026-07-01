#!/bin/bash

# Attendre que la DB soit prête (optionnel mais recommandé)
echo "Attente de la base de données..."
sleep 5

# Créer les migrations si elles n'existent pas
echo "Création des migrations..."
python manage.py makemigrations accounts catalogue commandes livraison

# Appliquer les migrations
echo "Application des migrations..."
python manage.py migrate

# Peupler la base de données (si le script existe)
if [ -f "populate_db.py" ]; then
    echo "Peuplement de la base de données..."
    python populate_db.py
fi

# Démarrer Gunicorn
echo "Démarrage du serveur..."
exec gunicorn --bind 0.0.0.0:8000 epharma.wsgi:application
