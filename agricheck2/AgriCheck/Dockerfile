FROM nginx:stable-alpine

# On suppose que le dossier build/web existe (résultat de flutter build web)
# Si vous n'avez pas encore fait le build, faites-le avant le docker-compose build
COPY ./build/web /usr/share/nginx/html

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
