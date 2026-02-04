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
echo "Ensuring Apache listens on container ports (8080/8443)"
# Update ports.conf and httpd.conf to expected ports for readiness/liveness probes
if [ -f /etc/apache2/ports.conf ]; then
  sed -i 's/^Listen[[:space:]]\+80$/Listen 8080/' /etc/apache2/ports.conf || true
  sed -i 's/^Listen[[:space:]]\+443$/Listen 8443/' /etc/apache2/ports.conf || true
fi
if [ -f /etc/apache2/httpd.conf ]; then
  sed -i 's/^Listen[[:space:]]\+80$/Listen 8080/' /etc/apache2/httpd.conf || true
  sed -i 's/^Listen[[:space:]]\+443$/Listen 8443/' /etc/apache2/httpd.conf || true
fi

# Ensure a global ServerName to suppress warning
mkdir -p /etc/apache2/conf.d
echo "ServerName localhost" > /etc/apache2/conf.d/servername.conf

# Enable mod_rewrite if not already enabled
if ! grep -q '^LoadModule rewrite_module' /etc/apache2/httpd.conf 2>/dev/null; then
  echo "Enabling mod_rewrite in Apache config..."
  sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/httpd.conf || true
fi

# Enable proxy modules if not already enabled
for module in proxy_module proxy_fcgi_module; do
  if ! grep -q "^LoadModule ${module}" /etc/apache2/httpd.conf 2>/dev/null; then
    echo "Enabling ${module} in Apache config..."
    sed -i "s/#LoadModule ${module}/LoadModule ${module}/" /etc/apache2/httpd.conf || true
    # If still not present, add it explicitly
    if ! grep -q "^LoadModule ${module}" /etc/apache2/httpd.conf 2>/dev/null; then
      short_name=$(echo $module | sed 's/_module//')
      echo "LoadModule ${module} modules/mod_${short_name}.so" >> /etc/apache2/httpd.conf
    fi
  fi
done

# Verify critical modules are loaded
echo "Verifying Apache modules..."
for check_module in rewrite_module proxy_module proxy_fcgi_module; do
  if httpd -M 2>&1 | grep -q "${check_module}"; then
    echo "✓ ${check_module} enabled"
  else
    echo "✗ ERROR: ${check_module} NOT enabled"
  fi
done

echo "=== find module dir ==="
find /usr -maxdepth 4 -type f -name "mod_mpm_*.so" 2>/dev/null || true
find /usr -maxdepth 5 -type f -name "mod_proxy*.so" 2>/dev/null || true
echo
echo "=== any LoadModule lines currently ==="
grep -nE "LoadModule (mpm_|proxy_|rewrite_)" /etc/apache2/httpd.conf || true

CONF=/etc/apache2/httpd.conf

# Disable prefork
echo "Disabled mpm_prefork_module"
sed -i "s|^[[:space:]]*LoadModule[[:space:]]\\+mpm_prefork_module|# LoadModule mpm_prefork_module|g" $CONF

# Disable worker if present (avoid conflicts)
sed -i "s|^[[:space:]]*LoadModule[[:space:]]\\+mpm_worker_module|# LoadModule mpm_worker_module|g" $CONF

# Enable event (add it if missing)
grep -q "^LoadModule mpm_event_module" $CONF || echo "LoadModule mpm_event_module modules/mod_mpm_event.so" >> $CONF

echo "List all modules:"
grep -E "LoadModule (mpm_|proxy_|rewrite_)" /etc/apache2/httpd.conf || true
echo "End list of modules"



httpd -t

exec httpd -DFOREGROUND





