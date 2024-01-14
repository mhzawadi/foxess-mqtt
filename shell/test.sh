#!/bin/sh

docker image rm mhzawadi/foxess-mqtt:dev-php && \
docker build -t mhzawadi/foxess-mqtt:dev-php -f Dockerfile-php . && \
docker run --rm -t -v "${PWD}":/workdir overtrue/phplint:latest ./ --exclude=vendor --no-configuration --no-cache && \
docker-compose -f docker-compose-dev.yml up
docker-compose down;
