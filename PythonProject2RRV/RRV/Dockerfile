# Utiliser une image Python officielle comme base
FROM python:3.11-slim

# Définir des variables d'environnement
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1

# Définir le répertoire de travail dans le conteneur
WORKDIR /app

# Installer les dépendances système nécessaires pour psycopg2 et Pillow
RUN apt-get update && apt-get install -y \
    libpq-dev \
    gcc \
    python3-dev \
    libjpeg-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Copier le fichier requirements.txt et installer les dépendances Python
COPY requirements.txt /app/
RUN pip install --no-cache-dir -r requirements.txt

# Copier le reste du code de l'application
COPY . /app/

# Exposer le port sur lequel Django s'exécute
EXPOSE 8000

# Commande par défaut pour démarrer le serveur de développement (ou gunicorn pour la prod)
CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]
