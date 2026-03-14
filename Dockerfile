# Master Money — Backend PHP pour Railway
FROM php:8.2-cli

# Extension PostgreSQL pour PDO
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copier l'application (config, lib, api, public)
COPY config/ ./config/
COPY lib/ ./lib/
COPY api/ ./api/
COPY public/ ./public/

# Railway fournit PORT
ENV PORT=8080
EXPOSE 8080

# Serveur PHP intégré : document root = public, routeur = public/index.php
# -t public = document root pour les fichiers statiques (si besoin)
CMD php -S 0.0.0.0:${PORT} -t /app/public /app/public/index.php
