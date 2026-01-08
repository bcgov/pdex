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

# Check for processed secrets first (from init container)
if [ -f /tmp/secrets.env ]; then
  ENV_SRC="/tmp/secrets.env"
  echo "Found processed secrets in /tmp/secrets.env"
elif [ -f /vault/secrets/secrets.env ]; then
  ENV_SRC="/vault/secrets/secrets.env"
elif [ -f /vault/secrets/test-secrets.env ]; then
  ENV_SRC="/vault/secrets/test-secrets.env"
fi

if [ -n "$ENV_SRC" ]; then
  echo "Installing env file from $ENV_SRC -> $ENV_DST"
  
  echo "Debug: Source file content preview:"
  head -n 3 "$ENV_SRC"
  
  echo "Debug: Current .env file status:"
  ls -la "$ENV_DST" 2>/dev/null || echo ".env does not exist yet"

  # If .env already exists and is not a symlink, try to remove it
  if [ -e "$ENV_DST" ] && [ ! -L "$ENV_DST" ]; then
    rm -f "$ENV_DST" 2>/dev/null || echo "Cannot remove existing .env (read-only filesystem)"
  fi
  
  # Try to create symlink directly to vault secrets (avoids copying)
  if ln -sf "$ENV_SRC" "$ENV_DST" 2>/dev/null; then
    echo "Successfully created symlink to $ENV_SRC"
    cd /var/www/html
  else
    echo "Cannot create symlink (read-only filesystem)"
    # Since we can't write to /var/www/html, we need to work around it
    # Option: Copy to a writable location and source as env vars
    echo "Loading environment variables from $ENV_SRC"
    set -a  # automatically export all variables

    # the file is -rw-r--r--, we can not source it directly we need to chmod to 777 first
    chmod 777 "$ENV_SRC" 2>/dev/null || echo "Warning: Could not chmod env file"
    source "$ENV_SRC" 2>/dev/null || echo "Warning: Could not source env file"

    # now chmod back to original
    chmod 644 "$ENV_SRC" 2>/dev/null || echo "Warning: Could not chmod back env file"

    set +a
    cd /var/www/html
  fi
else
  echo "No secrets env file found in /vault/secrets"
  cd /var/www/html
fi

# cat .env
echo "ENV file content preview:"
cat .env || echo ".env file not found or not accessible"

chown -R www-data:www-data \
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

echo "Starting apache foreground"
exec apache2-foreground
