FROM alpine:3.21
MAINTAINER Matthew Horwood <matt@horwood.biz>

# Install required deb packages
RUN apk update && apk upgrade && \
    apk add php84-json php84-curl git php84 php84-phar php84-xml php84-tokenizer \
    php84-sockets curl php84-openssl php84-mbstring php84-dom php84-xmlwriter php84-pecl-redis\
    && rm -f /var/cache/apk/*; \
    [ -f /usr/bin/php ] && rm -f /usr/bin/php; \
    ln -s /usr/bin/php84 /usr/bin/php; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer;

COPY . /foxess-mqtt
WORKDIR /foxess-mqtt
RUN composer install;
VOLUME /foxess-mqtt/data
CMD ["/foxess-mqtt/run.sh"]
