FROM alpine:latest
ARG TZ=America/Vancouver
ARG DEVENV=prod

WORKDIR /var/www/html

# ---- Packages (Apache + proxy_fcgi + PHP-FPM + common PHP extensions) ----
RUN apk add --no-cache \
    apache2 apache2-proxy apache2-ssl \
    php83 php83-fpm php83-opcache \
    php83-pdo php83-pdo_pgsql \
    php83-mbstring php83-xml php83-json php83-curl php83-ctype php83-tokenizer php83-phar php83-dom php83-session \
    php83-fileinfo php83-simplexml php83-xmlwriter php83-openssl \
    curl bash ca-certificates tzdata \
    nodejs npm \
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
# Copy optimized config and disable default
RUN mv /etc/php83/php-fpm.d/www.conf /etc/php83/php-fpm.d/www.conf.bak \
  && mkdir -p /var/run/php-fpm

COPY aws/apache/etc/php83/php-fpm.d/zzz-pdex.conf /etc/php83/php-fpm.d/zzz-pdex.conf
COPY aws/apache/etc/php83/conf.d/opcache.ini /etc/php83/conf.d/opcache.ini

# ---- Apache: ports + ServerName ----
RUN mkdir -p /etc/apache2/conf.d \
  && echo "ServerName localhost" > /etc/apache2/conf.d/servername.conf

# Make "modules/..." paths work (ServerRoot=/etc/apache2 => /etc/apache2/modules/...)
RUN rm -rf /etc/apache2/modules \
 && ln -s /usr/lib/apache2 /etc/apache2/modules

# ---- Apache: PHP handler via proxy_fcgi + socket ----
# Copy vhost config
COPY aws/apache/etc/apache2/sites-available/000-default.conf /etc/apache2/conf.d/php-fpm.conf

# ---- Apache: switch to mpm_event + fix module paths ----
RUN set -eux; \
  CONF=/etc/apache2/httpd.conf; \
  \
  # Use /etc/apache2 as ServerRoot
  sed -i 's|^ServerRoot .*|ServerRoot /etc/apache2|' "$CONF" || true; \
  \
  # Apache expects logs/ under ServerRoot
  mkdir -p /etc/apache2/logs; \
  # Ensure modules dir exists as symlink to the real module location
  rm -rf /etc/apache2/modules; \
  ln -s /usr/lib/apache2 /etc/apache2/modules; \
  \
  # Disable prefork/worker
  sed -i -E 's|^[[:space:]]*LoadModule[[:space:]]+mpm_prefork_module.*|# &|g' "$CONF"; \
  sed -i -E 's|^[[:space:]]*LoadModule[[:space:]]+mpm_worker_module.*|# &|g' "$CONF"; \
  \
  # Enable event (uncomment if present)
  sed -i -E 's|^[[:space:]]*#[[:space:]]*LoadModule[[:space:]]+mpm_event_module|LoadModule mpm_event_module|g' "$CONF"; \
  \
  # Enable rewrite module (uncomment if present)
  sed -i -E 's|^[[:space:]]*#[[:space:]]*LoadModule[[:space:]]+rewrite_module|LoadModule rewrite_module|g' "$CONF"; \
  \
  # If still missing, add it using modules/ path (now valid due to symlink)
  grep -qE '^[[:space:]]*LoadModule[[:space:]]+mpm_event_module' "$CONF" || \
    echo 'LoadModule mpm_event_module modules/mod_mpm_event.so' >> "$CONF"; \
  \
  httpd -t
# Apache must listen on the container ports used by k8s probes/service
RUN sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/httpd.conf \
 && sed -i 's/^Listen 443$/Listen 8443/' /etc/apache2/conf.d/ssl.conf

# ---- Permissions (Laravel) ----
# After COPY . /var/www/html (or before composer install), add:
RUN mkdir -p /var/www/html/storage/framework/views \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
             /var/log/php \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod 755 /var/log/php \
 && chown -R apache:apache /var/www/html/storage /var/www/html/bootstrap/cache /var/log/php || true


# ---- Entrypoint (your existing script) ----
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

#composer install
RUN composer install && npm install --prefix /var/www/html/ && npm run --prefix /var/www/html/ ${DEVENV}

EXPOSE 8080 8443

ENTRYPOINT ["/entrypoint.sh"]
