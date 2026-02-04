FROM alpine:latest
ARG TZ=America/Vancouver
ARG DEVENV=prod

WORKDIR /var/www/html

# ---- Packages (Apache + proxy_fcgi + PHP-FPM + common PHP extensions) ----
RUN apk add --no-cache \
    apache2 apache2-proxy apache2-ssl apache2-mpm-event \
    php83 php83-fpm php83-opcache \
    php83-pdo php83-pdo_pgsql \
    php83-mbstring php83-xml php83-json php83-curl php83-ctype php83-tokenizer php83-phar php83-dom php83-session \
    php83-fileinfo php83-simplexml php83-xmlwriter php83-openssl \
    curl bash ca-certificates tzdata \
  && update-ca-certificates

# ---- Composer ----
ENV COMPOSER_ALLOW_SUPERUSER=1
# RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN test -f /usr/bin/php || ln -s /usr/bin/php83 /usr/bin/php \
    && curl -sS https://getcomposer.org/installer | php83 -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

# ---- Copy app ----
COPY . /var/www/html

# ---- PHP-FPM config (socket + permissions) ----
RUN mkdir -p /var/run/php-fpm \
  && sed -i 's|^;*listen = .*|listen = /var/run/php-fpm/fpm.sock|' /etc/php83/php-fpm.d/www.conf \
  && sed -i 's|^;*listen.owner = .*|listen.owner = apache|' /etc/php83/php-fpm.d/www.conf \
  && sed -i 's|^;*listen.group = .*|listen.group = apache|' /etc/php83/php-fpm.d/www.conf \
  && sed -i 's|^;*listen.mode = .*|listen.mode = 0660|' /etc/php83/php-fpm.d/www.conf \
  && sed -i 's|^user = .*|user = apache|' /etc/php83/php-fpm.d/www.conf \
  && sed -i 's|^group = .*|group = apache|' /etc/php83/php-fpm.d/www.conf

# ---- Apache: ports + ServerName ----
RUN mkdir -p /etc/apache2/conf.d \
  && echo "ServerName localhost" > /etc/apache2/conf.d/servername.conf

# ---- Apache: PHP handler via proxy_fcgi + socket ----
RUN cat > /etc/apache2/conf.d/php-fpm.conf <<'EOF'
DirectoryIndex index.php index.html

# Laravel typical docroot is /var/www/html/public
# If you already set DocumentRoot elsewhere, keep yours and remove the next line.
DocumentRoot "/var/www/html/public"

<Directory "/var/www/html/public">
    AllowOverride All
    Require all granted
</Directory>

<FilesMatch \.php$>
    SetHandler "proxy:unix:/var/run/php-fpm/fpm.sock|fcgi://localhost/"
</FilesMatch>
EOF

# ---- Apache: switch to mpm_event + fix module paths ----
RUN set -eux; \
  CONF=/etc/apache2/httpd.conf; \
  \
  # Ensure Apache uses /etc/apache2 as ServerRoot (more standard)
  sed -i 's|^ServerRoot .*|ServerRoot /etc/apache2|' "$CONF" || true; \
  \
  # Convert ONLY LoadModule lines that use "modules/mod_*.so" to absolute paths
  sed -i -E 's|^([[:space:]]*LoadModule[[:space:]]+[^[:space:]]+[[:space:]]+)modules/(mod_[^[:space:]]+\.so)|\1/usr/lib/apache2/\2|g' "$CONF"; \
  \
  # Disable prefork/worker, enable event
  sed -i -E 's|^[[:space:]]*LoadModule[[:space:]]+mpm_prefork_module.*|# &|g' "$CONF"; \
  sed -i -E 's|^[[:space:]]*LoadModule[[:space:]]+mpm_worker_module.*|# &|g' "$CONF"; \
  \
  # If an event line exists but is commented, uncomment it
  sed -i -E 's|^[[:space:]]*#[[:space:]]*LoadModule[[:space:]]+mpm_event_module|LoadModule mpm_event_module|g' "$CONF"; \
  \
  # If no event module line exists at all, add it (absolute path)
  grep -qE '^[[:space:]]*LoadModule[[:space:]]+mpm_event_module' "$CONF" || \
    echo 'LoadModule mpm_event_module /usr/lib/apache2/mod_mpm_event.so' >> "$CONF"; \
  \
  # Ensure required modules are enabled (absolute paths; harmless if already enabled)
  grep -qE '^[[:space:]]*LoadModule[[:space:]]+rewrite_module' "$CONF" || \
    echo 'LoadModule rewrite_module /usr/lib/apache2/mod_rewrite.so' >> "$CONF"; \
  grep -qE '^[[:space:]]*LoadModule[[:space:]]+proxy_module' "$CONF" || \
    echo 'LoadModule proxy_module /usr/lib/apache2/mod_proxy.so' >> "$CONF"; \
  grep -qE '^[[:space:]]*LoadModule[[:space:]]+proxy_fcgi_module' "$CONF" || \
    echo 'LoadModule proxy_fcgi_module /usr/lib/apache2/mod_proxy_fcgi.so' >> "$CONF"; \
  \
  # Validate config at build time
  httpd -t

# ---- Permissions (Laravel) ----
RUN chown -R apache:apache /var/www/html \
  && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# ---- Entrypoint (your existing script) ----
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080 8443

ENTRYPOINT ["/entrypoint.sh"]
