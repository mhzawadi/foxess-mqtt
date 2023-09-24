#!/bin/sh

docker-compose down;
docker image rm mhzawadi/foxess-mqtt:dev-php && \
docker build -t mhzawadi/foxess-mqtt:dev-php -f Dockerfile-php . && \
docker-compose -f docker-compose-dev.yml up
