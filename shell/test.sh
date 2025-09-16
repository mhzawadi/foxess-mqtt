#!/bin/sh

if [ $# -lt 1 ]
then
  docker scout quickview fs://.
  docker scout cves fs://.
  docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:latest ./ --exclude=vendor --no-configuration --no-cache && \
  docker compose -f docker-compose-dev.yml up foxess-mqtt
elif [ "$1" == "composer" ]
then
  docker image rm mhzawadi/foxess-mqtt:dev-php;
  docker build -t mhzawadi/foxess-mqtt:dev-php -f Dockerfile . && \
  docker run --rm -it -v '/Users/matt/git/foxess-mqtt:/foxess-mqtt' mhzawadi/foxess-mqtt:dev-php /usr/local/bin/composer update
  docker compose -f docker-compose-dev.yml up -d
elif [ "$1" == "up" ]
then
  docker image rm mhzawadi/foxess-mqtt:dev-php && \
  docker build -t mhzawadi/foxess-mqtt:dev-php -f Dockerfile . && \
  docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:latest ./ --exclude=vendor --no-configuration --no-cache && \
  docker compose -f docker-compose-dev.yml up -d
else
  docker compose -f docker-compose-dev.yml down
fi
