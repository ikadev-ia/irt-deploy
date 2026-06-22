# Utiliser une image Python officielle légère
FROM python:3.12-slim

# Définir des variables d'environnement
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1

# Définir le répertoire de travail
WORKDIR /app

# Installer les dépendances système nécessaires (pour Pillow par exemple)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    gcc \
    python3-dev \
    musl-dev \
    libjpeg-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les dépendances Python
COPY requirements.txt /app/
RUN pip install --no-cache-dir -r requirements.txt

# Copier le reste du code du projet
COPY . /app/

# Exposer le port sur lequel Django va tourner
EXPOSE 8000

# Commande pour lancer l'application (en utilisant gunicorn pour la prod ou manage.py pour le dev)
CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]
