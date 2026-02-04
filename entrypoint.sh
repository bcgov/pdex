#!/bin/sh
set -e

echo "Start entrypoint file"

echo "APACHE_REMOTE_IP_HEADER: ${APACHE_REMOTE_IP_HEADER}"
echo "APACHE_REMOTE_IP_TRUSTED_PROXY: ${APACHE_REMOTE_IP_TRUSTED_PROXY}"
echo "APACHE_REMOTE_IP_INTERNAL_PROXY: ${APACHE_REMOTE_IP_INTERNAL_PROXY}"

echo "Setup TZ"
export TZ="${TZ:-America/Vancouver}"
php -r "date_default_timezone_set('${TZ}');"
php -r "echo date_default_timezone_get() . PHP_EOL;"

ENV_DST="/var/www/html/.env"
ENV_SRC=""

# Check for processed secrets first (from init container)
if [ -f /secrets/.env ]; then
  ENV_SRC="/secrets/.env"
  echo "Found processed secrets in /secrets/.env"
else
  echo "No processed secrets found in /secrets/.env"
fi

cp "$ENV_SRC" "$ENV_DST" || echo "Cannot copy env file (read-only filesystem)"

echo "Set permissions"
chown -R apache:apache \
      "$ENV_DST" \
      /var/www/html/storage \
      /var/www/html/bootstrap/cache 2>/dev/null || echo "Warning: Could not change ownership (read-only filesystem)"
chmod -R 775 \
      /var/www/html/storage \
      /var/www/html/bootstrap/cache 2>/dev/null || echo "Warning: Could not change permissions (read-only filesystem)"

echo "ENV_ARG: ${ENV_ARG}"

echo "Install composer"
composer dump-autoload

echo "Run migration"
php artisan migrate --force

echo "Clear cache"
php artisan cache:clear

echo "Clear our midnight queue"
php artisan queue:clear --queue=midnight --force

echo "Generate API documentation"
php artisan l5-swagger:generate || echo "Warning: API documentation generation failed, continuing..."

echo "Creating PHP-FPM runtime directory"
mkdir -p /var/run/php-fpm
chmod 755 /var/run/php-fpm

echo "Starting PHP-FPM"
php-fpm83 -D
sleep 2

echo "Verifying PHP-FPM is listening"
if [ -S /var/run/php-fpm/fpm.sock ]; then
  echo "✓ PHP-FPM socket created: /var/run/php-fpm/fpm.sock"
else
  echo "✗ ERROR: PHP-FPM socket not found at /var/run/php-fpm/fpm.sock"
  exit 1
fi

echo "Starting apache foreground"
exec httpd -DFOREGROUND
