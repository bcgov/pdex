FROM php:8.3-apache
ARG DEBIAN_VERSION=20.04
ARG APACHE_OPENIDC_VERSION=2.4.10
ARG TZ=America/Vancouver
ARG CA_HOSTS_LIST
ARG USER_ID
ARG DEBIAN_FRONTEND=noninteractive
ARG DEVENV=prod
# set entrypoint variables
ENV USER_NAME=${USER_ID}
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

#RUN useradd -u 1000 -ms /bin/bash ${USER_ID}
RUN apt-get -yq update --fix-missing \
    && apt-get update && apt-get install -y --no-install-recommends apt-utils \
#php setup, install extensions, setup configs \
    && apt-get install --no-install-recommends -y \
    libzip-dev \
    libxml2-dev \
    zip \
    nano \
    unzip \
#    cron \
    zlib1g-dev g++ libicu-dev libpq-dev netcat-traditional curl apache2 libcurl4 libcurl3-dev \
    	libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libaio-dev \
    libonig-dev \
    ca-certificates gnupg \
    && pecl install zip pcov && docker-php-ext-enable zip \
    && docker-php-ext-install bcmath soap \
    && docker-php-source delete \
    && sed -ri -e 's!expose_php = On!expose_php = Off!g' $PHP_INI_DIR/php.ini-production \
    && sed -ri -e 's!ServerTokens OS!ServerTokens Prod!g' /etc/apache2/conf-available/security.conf \
    && sed -ri -e 's!ServerSignature On!ServerSignature Off!g' /etc/apache2/conf-available/security.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-install intl opcache\
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql && docker-php-ext-install curl  \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/  \
    && docker-php-ext-install -j$(nproc) gd && a2enmod rewrite \
    && a2enmod remoteip \
    && a2enmod rewrite \
    && a2enmod auth_basic \
    && a2enmod authn_file \
    && a2enmod authz_user \
    && a2enmod autoindex \
    && a2enmod deflate \
    && a2enmod filter \
    && a2dismod mpm_event && a2dismod  mpm_worker && a2enmod mpm_prefork \
    && a2enmod reqtimeout \
    && a2enmod setenvif \
    && sed -i 's/%h/%a/g' /etc/apache2/apache2.conf \
    && { \
        echo 'RemoteIPHeader X-Forwarded-For'; \
        echo 'RemoteIPInternalProxy 142.34.0.0/16'; \
        echo 'RemoteIPInternalProxy 142.35.0.0/16'; \
        echo 'RemoteIPInternalProxy 10.97.0.0/16'; \
        echo 'RemoteIPInternalProxy 10.98.0.0/16'; \
        echo 'RemoteIPInternalProxy 127.0.0.1'; \
    } | tee "$APACHE_CONFDIR/conf-available/remoteip.conf" && \
    a2enconf remoteip && \
    a2enconf security-headers \
# Apache - Hide version
  && sed -i -e 's/^ServerTokens OS$/ServerTokens Prod/g' \
        -e 's/^ServerSignature On$/ServerSignature Off/g' \
        /etc/apache2/conf-available/security.conf \
# Enable apache modules
  && a2enmod rewrite headers \
    # Install Node.js with proper verification - using latest LTS version 22
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && NODE_MAJOR=22 \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && apt-get autoclean && apt-get autoremove && apt-get clean && rm -rf /var/lib/apt/lists/* \
#fix Action '-D FOREGROUND' failed.
    && a2enmod lbmethod_byrequests \
    && mkdir -p /var/log/php  \
    # Install Composer first with allow_url_fopen enabled temporarily
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    # Now set secure PHP configuration with allow_url_fopen disabled
    && printf 'error_log=/var/log/php/error.log\nlog_errors=1\nerror_reporting=E_ERROR\nmemory_limit=450M\nexpose_php=Off\nallow_url_fopen=Off\nallow_url_include=Off\ndisplay_errors=Off\ndisplay_startup_errors=Off\nmax_execution_time=30\nmax_input_time=60\npost_max_size=50M\nupload_max_filesize=50M\nsession.cookie_httponly=1\nsession.cookie_secure=1\nsession.use_strict_mode=1\n' > /usr/local/etc/php/conf.d/security.ini \
    && mkdir -p /etc/apache2/sites-enabled \
    && sed -i -e 's/80/8080/g' -e 's/443/8443/g' -e 's/25/2525/g' /etc/apache2/ports.conf \
    # Apache- Prepare to be run as non root user
    && mkdir -p /var/lock/apache2 /var/run/apache2 \
    && chgrp -R 0 /etc/apache2/mods-* \
        /etc/apache2/sites-* \
        /run /var/lib/apache2 \
        /var/run/apache2 \
        /var/lock/apache2 \
        /var/log/apache2 \
    && chmod -R g=u /etc/passwd \
        /etc/apache2/mods-* \
        /etc/apache2/sites-* \
        /run \
        /var/lib/apache2 \
        /var/run/apache2 \
        /var/lock/apache2 \
        /var/log/apache2 \
    && chmod 755 /docker-bin/*.sh \
    && /docker-bin/docker-build.sh && export COMPOSER_HOME="$HOME/.config/composer";


#RUN supervisorctl reread && supervisorctl update
WORKDIR /var/www/html/

RUN mkdir -p storage && mkdir -p bootstrap/cache && chmod -R ug+rwx storage bootstrap/cache \
    && cd /var/www && chown -R 1001:root html && chmod -R ug+rw html \
    && chmod 754 /var/www/html/artisan \
    && chmod 755 /var/www/html/probe-check.sh \
    && cd /var/www/html/public && chmod 644 mix-manifest.json \
    && mkdir /.npm && mkdir /.npm/_cache && chown -R 1001:0 "/.npm" \
    && mkdir -p /.config/psysh && chown -R 1001:root /.config && chmod -R 775 /.config \
    && mkdir -p /.composer && chown -R 1001:root /.composer && chmod -R 755 /.composer \
    && echo "<?php return ['runtimeDir' => '/tmp', 'configDir' => '/tmp', 'dataDir' => '/tmp'];" >> /.config/psysh/config.php \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && chmod 755 /sbin/entrypoint.sh

# Ensure cache directories are writable by non-root user before composer install
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/api-docs \
    && chmod -R 775 bootstrap/cache storage/ \
    && npm config set cache /.npm/_cache --global

#composer install
RUN composer install && npm install --prefix /var/www/html/ && npm run --prefix /var/www/html/ ${DEVENV}


# Switch to non-root user for OpenShift compatibility
USER 1001

ENTRYPOINT ["bash", "/sbin/entrypoint.sh"]
