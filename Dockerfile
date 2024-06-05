FROM alpine:3.20
MAINTAINER Matthew Horwood <matt@horwood.biz>

# Install required deb packages
RUN apk update && apk upgrade && \
    apk add php82-json php82-curl git php82 php82-phar php82-xml php82-tokenizer \
    php82-sockets curl php82-openssl php82-mbstring php82-dom php82-xmlwriter php82-pecl-redis\
    && rm -f /var/cache/apk/*; \
    [ -f /usr/bin/php ] && rm -f /usr/bin/php; \
    ln -s /usr/bin/php82 /usr/bin/php; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer;

COPY . /foxess-mqtt
WORKDIR /foxess-mqtt
RUN composer install;
VOLUME /foxess-mqtt/data
CMD ["/foxess-mqtt/run.sh"]
