FROM alpine:3.24
LABEL org.opencontainers.image.authors="matt@horwood.biz"

# Install required deb packages
RUN apk update && apk upgrade && \
    apk add php85-json php85-curl git php85 php85-phar php85-xml php85-tokenizer \
    php85-sockets curl php85-openssl php85-mbstring php85-dom php85-xmlwriter php85-pecl-redis \
    php85-ctype \
    && rm -f /var/cache/apk/*; \
    [ -f /usr/bin/php ] && rm -f /usr/bin/php; \
    ln -s /usr/bin/php85 /usr/bin/php; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer;

COPY . /foxess-mqtt
WORKDIR /foxess-mqtt
RUN composer install;
VOLUME /foxess-mqtt/data
CMD ["/foxess-mqtt/run.sh"]
