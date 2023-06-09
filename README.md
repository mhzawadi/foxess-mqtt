# Horwoods Foxess Cloud Data collector

This is a very simple tool to get the data out of FoxEss-cloud and into MQTT, it has some very rough edges.

## Running

```bash
docker run --name foxescloud \
  -e - TIMEZONE=Europe/London \
  -v /home/user/foxess_data:/foxess-mqtt/data \
  mhzawadi/foxess-mqtt
```

## Docker environment variables

- TIMEZONE - This is the time your in, else use UTC [List of Supported Timezones](https://www.php.net/manual/en/timezones.php)

## Config file

The new PHP script uses json for the config,
you can copy the below code and paste into config.josn or run the image and wait for it spit out the files.

`device_id` - the UUID that can be found on FoxEss-cloud in the url path on the Inverter Details page.
Please make sure that this is exact value from inverter details page address between = and & character:

The json for the config.json file
```
{
  "foxess_username": "username",
  "foxess_password": "secretPassword",
  "device_id": "device_id",
  "mqtt_host": "mosquitto",
  "mqtt_port": "1883",
  "mqtt_user": "foxess",
  "mqtt_pass": "foxess",
  "log_level": 2
}
```

- log_level: is how much you want in the console, 1 is minimal, 2 is basic, 3 is everything
