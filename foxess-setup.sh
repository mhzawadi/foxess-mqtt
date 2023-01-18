#!/bin/bash

STATIC_JSON=foxess_data

setup_ha_mqtt(){
  MQTT_PASSWORD=$1
  MQTTtopic=$2
  # Config for date sensor
  /usr/local/bin/mqttools publish \
    --retain \
    --host ${MQTT_HOST} \
    --username ${MQTT_USERNAME} \
    --password ${MQTT_PASSWORD} \
    homeassistant/sensor/foxesscloud-${MQTTtopic}/config \
    "{
    \"name\": \"foxesscloud ${MQTTtopic}\",
    \"device\": {
      \"identifiers\": \"foxesscloud\",
      \"name\": \"foxesscloud\",
      \"model\": \"F5000\",
      \"manufacturer\": \"FoxEss\"
    },
    \"stat_t\": \"~${MQTTtopic}\",
    \"uniq_id\": \"foxesscloud-${MQTTtopic}\",
    \"~\": \"foxesscloud/\",
    \"unit_of_measurement\": \"KW\",
    \"dev_cla\": \"power\",
    \"exp_aft\": 86400
  }" 2>&1 > /dev/null
}

OPTIONS=$(jq -r '.variables | length' ${STATIC_JSON})
MIN=$(date +"%M" | sed 's/[1-9]$/0/')
for (( i = 0 ; i < $OPTIONS; i++ ))
do
  OPTION=$(jq -r ".variables[${i}]" ${STATIC_JSON})
  setup_ha_mqtt ${MQTT_PASSWORD} ${OPTION}
  if [ -f .foxess_kwh ]
  then
    cat <<<EOF > .foxess_kwh
    EOF
  fi
done
