# =========================================
# Stage 1: Frontend Build (Vite + Vue)
# =========================================
FROM node:20 AS frontend
WORKDIR /app

ARG VITE_FIREBASE_API_KEY
ARG VITE_FIREBASE_APP_ID
ARG VITE_FIREBASE_AUTH_DOMAIN
ARG VITE_FIREBASE_PROJECT_ID
ARG VITE_FIREBASE_STORAGE_BUCKET
ARG VITE_FIREBASE_MESSAGING_SENDER_ID

ARG NETO_API_KEY
ARG NETO_API_USERNAME
ARG NETO_API_URL

ENV VITE_FIREBASE_API_KEY=$VITE_FIREBASE_API_KEY
ENV VITE_FIREBASE_APP_ID=$VITE_FIREBASE_APP_ID
ENV VITE_FIREBASE_AUTH_DOMAIN=$VITE_FIREBASE_AUTH_DOMAIN
ENV VITE_FIREBASE_PROJECT_ID=$VITE_FIREBASE_PROJECT_ID
ENV VITE_FIREBASE_STORAGE_BUCKET=$VITE_FIREBASE_STORAGE_BUCKET
ENV VITE_FIREBASE_MESSAGING_SENDER_ID=$VITE_FIREBASE_MESSAGING_SENDER_ID

ENV NETO_API_KEY=$NETO_API_KEY
ENV NETO_API_USERNAME=$NETO_API_USERNAME
ENV NETO_API_URL=$NETO_API_URL

COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# =========================================
# Stage 2: Backend PHP (Laravel) + Nginx
# =========================================
FROM php:8.2-fpm
WORKDIR /var/www/html

# Install Nginx + system deps
RUN apt-get update && apt-get install -y \
    nginx git unzip libzip-dev curl libpng-dev libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd opcache

# OPcache config for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Laravel files
COPY . .

# Copy built frontend assets
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy Nginx config
COPY nginx.conf /etc/nginx/sites-available/default

# Copy startup script
COPY startup.sh /startup.sh
RUN chmod +x /startup.sh

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/log/nginx \
    && chown -R www-data:www-data /var/log/nginx

EXPOSE 80

CMD ["/startup.sh"]
