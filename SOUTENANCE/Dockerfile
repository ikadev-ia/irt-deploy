FROM php:8.2-apache

# Installation des extensions PHP nécessaires pour MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Activation de mod_rewrite pour Apache
RUN a2enmod rewrite

# Copie des fichiers du projet
COPY . /var/www/html/

# Permissions pour le dossier uploads et le stockage de session si besoin
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Port exposé par Apache
EXPOSE 80
