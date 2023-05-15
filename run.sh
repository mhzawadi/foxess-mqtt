#!/bin/sh

# Get the timezone and update PHP
if [ $TIMEZONE ]
then
  echo "Setting your timezone to $TIMEZONE"
  sed -i'' "s!;date.timezone =!date.timezone = $TIMEZONE!" /etc/php81/php.ini
else
  echo "We dont have a timezone, it could UTC"
fi

if [ ! -f /foxess-mqtt/data/config.json ]
then
  cp /foxess-mqtt/template/config.json /foxess-mqtt/data/config.json
  cp /foxess-mqtt/template/foxess_data.json /foxess-mqtt/data/foxess_data.json
  echo 'Please update to config.json file'
  exit 1;
else
  echo "first run will sleep for 5 seconds to allow MQTT to get setup"
  sleep 5
  while true
  do
    php run.php
    sleep 300
  done
fi
