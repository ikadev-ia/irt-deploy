FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite pour Apache
RUN a2enmod rewrite

# Copier les fichiers du projet
COPY . /var/www/html/

# Donner les droits appropriés
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
