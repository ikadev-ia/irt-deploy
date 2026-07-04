FROM nginx:stable-alpine

# Copier les fichiers du build Flutter web vers le répertoire Nginx
COPY ./web /usr/share/nginx/html

# Configuration personnalisée de Nginx pour gérer le routage Single Page Application (SPA) si nécessaire
RUN echo 'server { \
    listen 80; \
    location / { \
        root /usr/share/nginx/html; \
        index index.html; \
        try_files $uri $uri/ /index.html; \
    } \
}' > /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
