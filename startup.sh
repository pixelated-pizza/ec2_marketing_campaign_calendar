#!/bin/bash
set -e

echo "==> Running Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
nginx -g "daemon off;"