#!/bin/bash

# Attendre que la base de données soit prête
echo "Attente de la base de données MySQL..."
while ! nc -z agricheck-db 3306; do
  sleep 1
done
echo "Base de données prête !"

# Exécuter les migrations
echo "Exécution des migrations..."
python manage.py migrate --noinput

# Collecter les fichiers statiques (WhiteNoise)
echo "Collecte des fichiers statiques..."
python manage.py collectstatic --noinput

# Lancer le serveur avec Gunicorn
echo "Démarrage de Gunicorn sur le port 8091..."
exec gunicorn --bind 0.0.0.0:8091 agricheck.wsgi:application
