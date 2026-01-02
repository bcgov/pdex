#!/bin/bash
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

if [ -f /vault/secrets/secrets.env ]; then
  ENV_SRC="/vault/secrets/secrets.env"
elif [ -f /vault/secrets/test-secrets.env ]; then
  ENV_SRC="/vault/secrets/test-secrets.env"
fi

if [ -n "$ENV_SRC" ]; then
  echo "Installing env file from $ENV_SRC -> $ENV_DST"
  cd /var/www/html
  
  echo "Debug: Current .env file status:"
  ls -la "$ENV_DST" 2>/dev/null || echo ".env does not exist yet"

  # Copy into place and set permissions (overwrite without removing first)
  cat "$ENV_SRC" > "$ENV_DST"
  chmod 644 "$ENV_DST"
else
  echo "No secrets env file found in /vault/secrets"
fi

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
php artisan l5-swagger:generate

echo "Starting apache foreground"
exec apache2-foreground
