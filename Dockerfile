FROM alpine:latest
ARG TZ=America/Vancouver
ARG DEVENV=prod

# set entrypoint variables
ENV USER_HOME=/var/www/html
ENV PSYSH_CONFIG_DIR=/tmp

ENV APACHE_REMOTE_IP_HEADER=X-Forwarded-For
# BC Gov Silver and Gold clusters specific proxy configuration
ENV APACHE_REMOTE_IP_TRUSTED_PROXY="142.34.0.0/16 142.35.0.0/16 10.97.0.0/16 10.98.0.0/16 127.0.0.1"
ENV APACHE_REMOTE_IP_INTERNAL_PROXY="142.34.0.0/16 142.35.0.0/16 10.97.0.0/16 10.98.0.0/16 127.0.0.1"

# System - Set default timezone
ENV TZ=${TZ}
ENV APACHE_SERVER_NAME=__default__

WORKDIR /
COPY openshift/apache-oc/image-files/ /
COPY openshift/apache-oc/image-files/etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf
COPY entrypoint.sh /sbin/entrypoint.sh
COPY / /var/www/html/

EXPOSE 8080 8443 2525

RUN apk add --no-cache --update \
    apache2 \
    php83 \
    php83-fpm \
    php83-bcmath \
    php83-soap \
    php83-intl \
    php83-opcache \
    php83-phar \
    php83-mbstring \
    php83-openssl \
    php83-zip \
    php83-pdo \
    php83-pdo_pgsql \
    php83-pgsql \
    php83-curl \
    php83-gd \
    php83-apcu \
    php83-common \
    php83-cli \
    php83-tokenizer \
    php83-simplexml \
    php83-fileinfo \
    php83-xml \
    php83-xmlreader \
    php83-xmlwriter \
    php83-json \
    php83-session \
    ca-certificates \
    curl \
    gnupg \
    nano \
    unzip \
    zip \
    g++ \
    nodejs \
    npm \
    netcat-openbsd \
    && apk add --no-cache --virtual .build-deps \
    autoconf \
    build-base \
    libtool \
    pcre-dev \
    && mkdir -p /var/log/php \
    && sed -ri -e 's!expose_php = On!expose_php = Off!g' /etc/php83/php.ini \
    && sed -ri -e 's!ServerTokens OS!ServerTokens Prod!g' /etc/apache2/conf.d/security.conf \
    && sed -ri -e 's!ServerSignature On!ServerSignature Off!g' /etc/apache2/conf.d/security.conf \
    && printf 'error_log=/var/log/php/error.log\nlog_errors=1\nerror_reporting=E_ERROR\nmemory_limit=450M\nexpose_php=Off\nallow_url_fopen=Off\nallow_url_include=Off\ndisplay_errors=Off\ndisplay_startup_errors=Off\nmax_execution_time=30\nmax_input_time=60\npost_max_size=50M\nupload_max_filesize=50M\nsession.cookie_httponly=1\nsession.cookie_secure=1\nsession.use_strict_mode=1\n' > /etc/php83/conf.d/security.ini \
    && ln -s /etc/php83 /etc/php \
    && ln -s /usr/bin/php83 /usr/bin/php \
    && sed -i 's/80/8080/g; s/443/8443/g; s/25/2525/g' /etc/apache2/ports.conf \
    && sed -i 's/%h/%a/g' /etc/apache2/httpd.conf \
    && { \
        echo 'RemoteIPHeader X-Forwarded-For'; \
        echo 'RemoteIPInternalProxy 142.34.0.0/16'; \
        echo 'RemoteIPInternalProxy 142.35.0.0/16'; \
        echo 'RemoteIPInternalProxy 10.97.0.0/16'; \
        echo 'RemoteIPInternalProxy 10.98.0.0/16'; \
        echo 'RemoteIPInternalProxy 127.0.0.1'; \
    } > /etc/apache2/conf.d/remoteip.conf \
    && sed -i -e 's/^ServerTokens OS$/ServerTokens Prod/g' \
        -e 's/^ServerSignature On$/ServerSignature Off/g' \
        /etc/apache2/conf.d/security.conf \
    && sed -i '/#LoadModule auth_basic_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/#LoadModule authn_file_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule authz_core_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/#LoadModule authz_user_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule autoindex_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/#LoadModule cgid_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule deflate_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/#LoadModule dir_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule filter_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule headers_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule mpm_event_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/#LoadModule mpm_prefork/s/^/#/' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule mime_module/s/^/#/' /etc/apache2/httpd.conf \
    && sed -i '/#LoadModule lbmethod_byrequests_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule proxy_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '#/LoadModule proxy_balancer_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule proxy_fcgi_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule rewrite_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule remoteip_module/s/^#//' /etc/apache2/httpd.conf \
    && sed -i '/LoadModule setenvif_module/s/^#//' /etc/apache2/httpd.conf \
    && mkdir -p /var/lock/apache2 /var/run/apache2 /var/run/php-fpm \
    && chgrp -R 0 /etc/apache2 \
        /run /var/lib/apache2 \
        /var/run/apache2 \
        /var/lock/apache2 \
        /var/log/apache2 \
    && chmod -R g=u /etc/passwd \
        /etc/apache2 \
        /run \
        /var/lib/apache2 \
        /var/run/apache2 \
        /var/lock/apache2 \
        /var/log/apache2 \
    && chmod 755 /docker-bin/*.sh 2>/dev/null || true \
    && mkdir -p /etc/apache2/sites-enabled /etc/php83/conf.d \
    && apk del .build-deps \
    && php -m | grep -i opcache || echo "Warning: OPcache not detected in PHP modules"

# Install Composer
RUN test -f /usr/bin/php || ln -s /usr/bin/php83 /usr/bin/php \
    && curl -sS https://getcomposer.org/installer | php83 -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

WORKDIR /var/www/html/

# Disable default PHP-FPM pool and use optimized config
RUN mv /etc/php83/php-fpm.d/www.conf /etc/php83/php-fpm.d/www.conf.bak

# Prepare application directories
RUN mkdir -p storage bootstrap/cache && chmod -R ug+rwx storage bootstrap/cache \
    && mkdir -p /var/www && chown -R apache:apache /var/www/html && chmod -R ug+rw /var/www/html \
    && chmod 754 /var/www/html/artisan \
    && chmod 755 /var/www/html/probe-check.sh \
    && mkdir -p /var/www/html/public && chmod 644 /var/www/html/public/mix-manifest.json 2>/dev/null || true \
    && mkdir -p /.npm && mkdir -p /.npm/_cache && chown -R apache:0 "/.npm" \
    && mkdir -p /.config/psysh && chown -R apache:apache /.config && chmod -R 775 /.config \
    && mkdir -p /.composer && chown -R apache:apache /.composer && chmod -R 755 /.composer \
    && echo "<?php return ['runtimeDir' => '/tmp', 'configDir' => '/tmp', 'dataDir' => '/tmp'];" >> /.config/psysh/config.php \
    && mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/api-docs \
    && chmod -R 775 bootstrap/cache storage/ \
    && npm config set cache /.npm/_cache --global \
    && chmod 755 /sbin/entrypoint.sh

# Install PHP dependencies
RUN cd /var/www/html && composer install --no-interaction --no-dev --prefer-dist

# Install Node dependencies
RUN npm install --prefix /var/www/html/

# Audit and fix npm vulnerabilities
RUN npm audit fix --prefix /var/www/html/ || true

# Build frontend assets
RUN npm run --prefix /var/www/html/ ${DEVENV}

ENTRYPOINT ["sh", "/sbin/entrypoint.sh"]