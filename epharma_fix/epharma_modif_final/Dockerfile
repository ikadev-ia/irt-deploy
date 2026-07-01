FROM python:3.10-slim

# Environnement
ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1

# Répertoire de travail
WORKDIR /app

# Dépendances système
RUN apt-get update && apt-get install -y \
    libpq-dev \
    gcc \
    && rm -rf /var/lib/apt/lists/*

# Installation des dépendances Python
COPY requirements.txt /app/
RUN pip install --no-cache-dir -r requirements.txt
RUN pip install --no-cache-dir gunicorn psycopg2-binary

# Copier le projet
COPY . /app/

RUN chmod +x /app/manage.py

EXPOSE 8000

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
