# Horwoods Foxess Cloud Data collector

This is a very simple tool to get the data out of foxesscloud and into MQTT, it has some very rough edges.

## Running

```bash
docker pull mhzawadi/foxess-mqtt
docker run --name picocms \
  -e MQTT_HOST=192.168.0.2 \
  -e MQTT_USERNAME=homeassistant \
  -e MQTT_PASSWORD=ASecurePassword \
  -e FOXESS_USERNAME=foxAccount \
  -e FOXESS_PASSWORD=foxPassword \
  -e DEVICE_ID=a-b-d-c-d \
  mhzawadi/foxess-mqtt
```

## Docker environment variables

- MQTT_HOST the host or IP of your MQTT server
- MQTT_USERNAME - the username for MQTT
- MQTT_PASSWORD - the password for MQTT
- FOXESS_USERNAME - your Foxess Cloud login
- FOXESS_PASSWORD - your Foxess Cloud login
- DEVICE_ID - the UUID that can be found on foxesscloud in the url path on the Inverter Details page.
  - Please make sure that this is exact value from inverter details page address between = and & character:
