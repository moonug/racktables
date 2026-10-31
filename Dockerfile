FROM debian:12-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        cron \
        curl \
        libnet-telnet-perl \
        netcat-openbsd \
        nginx \
        openssh-client \
        perl \
        php8.2-bcmath \
        php8.2-cli \
        php8.2-fpm \
        php8.2-gd \
        php8.2-ldap \
        php8.2-mbstring \
        php8.2-mysql \
        php8.2-snmp \
        python3 \
        snmp \
        tini \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default /var/www/html

COPY . /usr/share/racktables
COPY docker/nginx.conf /etc/nginx/conf.d/racktables.conf
COPY docker/php-fpm.conf /etc/php/8.2/fpm/pool.d/racktables.conf
COPY docker/app-init.php docker/healthz.php /usr/local/lib/racktables/
COPY docker/entrypoint.sh docker/cron-entrypoint.sh docker/rt-cron.sh /usr/local/bin/

# The plugins directory has to exist: RackTables resolves it with realpath()
# and falls back to scanning the filesystem root when it is missing.
RUN rm -f /etc/php/8.2/fpm/pool.d/www.conf \
    && chmod -R a+rX /usr/share/racktables \
    && mkdir -p /usr/share/racktables/plugins /run/racktables \
    && install -m 0644 /usr/local/lib/racktables/healthz.php /usr/share/racktables/wwwroot/healthz.php \
    && chmod 0755 /usr/local/bin/entrypoint.sh /usr/local/bin/cron-entrypoint.sh /usr/local/bin/rt-cron.sh \
    && php -l /usr/local/lib/racktables/app-init.php \
    && php -l /usr/share/racktables/wwwroot/healthz.php

EXPOSE 80
ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/entrypoint.sh"]
