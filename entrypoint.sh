#!/bin/sh
set -e

echo "Starting container entrypoint..."

# Ensure runtime dirs exist
mkdir -p /var/run/php-fpm
mkdir -p /run/apache2

# Fix permissions (Laravel typical)
chown -R apache:apache /var/www/html || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

echo "Starting PHP-FPM..."
php-fpm83 --daemonize

# Small wait to ensure socket exists
sleep 1

echo "Checking Apache config..."
httpd -t

echo "Starting Apache..."
exec httpd -DFOREGROUND
