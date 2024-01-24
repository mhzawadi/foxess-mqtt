# BREAKING CHANGE !!!!!

This version now needs redis to store all the data it collects, before an upload to MQTT.

See the below compose file for a quick setup

## BREAKING CHANGE !!!!

# Horwoods Foxess Cloud Data collector

This is a very simple tool to get the data out of FoxEss-cloud and into MQTT, it has some very rough edges.

## Running

```bash
docker run --name redis \
  -v home/user/redis_data:/data \
  redis;
docker run --name foxescloud \
  -e TIMEZONE=Europe/London \
  -v /home/user/foxess_data:/foxess-mqtt/data \
  mhzawadi/foxess-mqtt;
```

## Docker environment variables

- TIMEZONE - This is the time your in, else use UTC [List of Supported Timezones](https://www.php.net/manual/en/timezones.php)

## Config file

The new PHP script uses json for the config,
you can copy the below code and paste into config.josn or run the image and wait for it spit out the files.

You now dont need to provide your device ID as we now collect all devices in your account

The json for the config.json file
```json
{
  {
    "foxess_username": "changeme",
    "foxess_password": "changeme",
    "foxess_apikey": "changeme",
    "foxess_lang": "en",
    "device_id": "changeme",
    "mqtt_host": "changeme",
    "mqtt_port": "changeme",
    "mqtt_user": "changeme",
    "mqtt_pass": "changeme",
    "mqtt_topic": "foxesscloud",
    "log_level": 2,
    "total_over_time": true,
    "redis_server": "redis",
    "redis_port": "6379"
  }
}
```

- log_level: is how much you want in the console, 1 is minimal, 2 is basic, 3 is everything
- mqtt_topic: this is a custom top level topic, you will still get sub topics for each device on your account
- total_over_time: this will track KWh over time if true (the default) or whats downloaded at every run
- you now need to collect your API key from the Foxess cloud portal
  - head over to https://www.foxesscloud.com/user/center
  - Click `API Management` on the left
  - Click `Generate API key` and store the key (Maybe use a password manager like Bitwarden)
  - set `foxess_apikey` to the lolng string you just got
- redis_server: this is the IP or host name of the redis server
  - if you use the code above leave it as redis
- redis_port: the port your redis server is listening on, `6379` is the default port

## Compose file

The below compose file will help start both redis and my image

```
version: "2.1"

volumes:
  redis_data:

services:
  redis:
    image: redis
    container_name: redis
    volumes:
      - redis_data:/data

  foxess-mqtt:
    image: mhzawadi/foxess-mqtt:dev-php
    container_name: foxess-mqtt
    volumes:
      - ./config.json:/foxess-mqtt/data/config.json
    environment:
      - TIMEZONE=Europe/London
```
