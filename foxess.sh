#!/bin/bash

setup_ha_mqtt(){
  MQTT_KEY=$1
  MQTT_TOPIC=$2
  # Config for date sensor
  /usr/bin/mqttools publish \
    --retain \
    --host ${MQTT_HOST} \
    --username ${MQTT_USERNAME} \
    --password ${MQTT_PASSWORD} \
    homeassistant/sensor/foxesscloud-${MQTT_TOPIC}/config \
    "{
    \"name\": \"foxesscloud ${MQTT_TOPIC}\",
    \"device\": {
      \"identifiers\": \"foxesscloud\",
      \"name\": \"foxesscloud\",
      \"model\": \"F5000\",
      \"manufacturer\": \"FoxEss\"
    },
    \"stat_t\": \"~${MQTT_TOPIC}\",
    \"uniq_id\": \"foxesscloud-${MQTT_TOPIC}\",
    \"~\": \"foxesscloud/\",
    \"unit_of_measurement\": \"KW\",
    \"dev_cla\": \"power\",
    \"exp_aft\": 86400
  }" 2>&1 > /dev/null
}
setup_ha_mqtt_kwh(){
  MQTT_KEY=$1
  MQTT_TOPIC=$2
  # Config for date sensor
  /usr/bin/mqttools publish \
    --retain \
    --host ${MQTT_HOST} \
    --username ${MQTT_USERNAME} \
    --password ${MQTT_PASSWORD} \
    homeassistant/sensor/foxesscloud-${MQTT_TOPIC}_kwh/config \
    "{
    \"name\": \"foxesscloud ${MQTT_TOPIC}_kwh\",
    \"device\": {
      \"identifiers\": \"foxesscloud\",
      \"name\": \"foxesscloud\",
      \"model\": \"F5000\",
      \"manufacturer\": \"FoxEss\"
    },
    \"stat_t\": \"~${MQTT_TOPIC}_kwh\",
    \"uniq_id\": \"foxesscloud-${MQTT_TOPIC}_kwh\",
    \"~\": \"foxesscloud/\",
    \"unit_of_measurement\": \"kWh\",
    \"dev_cla\": \"energy\",
    \"state_class\": \"total_increasing\",
    \"exp_aft\": 86400
  }" 2>&1 > /dev/null
}

get_data(){
  # Curl FOxEss Cloud for the date
  curl -s -X POST \
  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 OPR/89.0.4447.83', \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'lang: en' \
  -H 'sec-ch-ua-platform: macOS' \
  -H 'Sec-Fetch-Site: same-origin' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: https://www.foxesscloud.com/login?redirect=/' \
  -H 'Accept-Language: en-US;q=0.9,en;q=0.8,de;q=0.7,nl;q=0.6' \
  -H 'Connection: keep-alive' \
  -H 'X-Requested-With: XMLHttpRequest' \
  -H "token: $(cat .token)" \
  -H "Content-Type: application/json" \
  -d "{
      \"deviceID\": \"${DEVICE_ID}\",
      \"variables\": [
          \"generationPower\",\"feedinPower\",\"batChargePower\",\"batDischargePower\",\"gridConsumptionPower\",\"loadsPower\",\"SoC\",\"batTemperature\",\"pv1Power\",\"pv2Power\",\"pv3Power\",\"pv4Power\"
      ],
      \"timespan\": \"day\",
      \"beginDate\": {
          \"year\": $(date +"%Y"),
          \"month\": $(date +"%m" | sed 's/^0//'),
          \"day\": $(date +"%d" | sed 's/^0//'),
          \"hour\": $(date +"%H" | sed 's/^0//'),
          \"minute\": 0,
          \"second\": 0
      }
  }" \
  https://www.foxesscloud.com/c/v0/device/history/raw |
  jq "." > /tmp/raw.json
}

foxess_login(){
  curl -s -X POST \
  -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 OPR/89.0.4447.83', \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'lang: en' \
  -H 'sec-ch-ua-platform: macOS' \
  -H 'Sec-Fetch-Site: same-origin' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: https://www.foxesscloud.com/login?redirect=/' \
  -H 'Accept-Language: en-US;q=0.9,en;q=0.8,de;q=0.7,nl;q=0.6' \
  -H 'Connection: keep-alive' \
  -H 'X-Requested-With: XMLHttpRequest' \
  -H 'token: ' \
  -d "{
      \"user\": \"${FOXESS_USERNAME}\",
      \"password\": \"${FOXESS_PASSWORD}\"
  }" \
  https://www.foxesscloud.com/c/v0/user/login |
  jq -r '.result.token' > .token
}

JSON='{
    "variables": [
        "generationPower","feedinPower","batChargePower","batDischargePower","gridConsumptionPower","loadsPower","SoC","batTemperature","pv1Power","pv2Power","pv3Power","pv4Power"
    ]
}'
OPTIONS=$(echo ${JSON} | jq -r '.variables | length')

# Run MQTT Home Assistant setup
if [ ! -f .setup_ha ]
then
  foxess_login
  for (( i = 0 ; i < ${OPTIONS}; i++ ))
  do
    OPTION=$(echo ${JSON} | jq -r ".variables[${i}]")
    setup_ha_mqtt ${MQTT_KEY} ${OPTION}
    setup_ha_mqtt_kwh ${MQTT_KEY} ${OPTION}
  done
  touch .setup_ha
fi

# Get live data from FoxEss Cloud
get_data
while [ "$(jq -r '.errno' /tmp/raw.json)" != "0" ]
do
  # Login, as you dont have a token
  foxess_login
  get_data
done


# Loop over the data and post to MQTT
for (( i = 0 ; i < $OPTIONS; i++ ))
do
  OPTION=$(echo ${JSON} | jq -r ".variables[${i}]")
  if [ "$(jq -r '.result' /tmp/raw.json)" == "null" ]
  then
    VALUE=0
  else
    DATA=$(jq ".result[] | select(.variable == \"${OPTION}\") | .data" /tmp/raw.json)
    if [ "$(echo ${DATA} | jq ".[-1].value")" == '' ]
    then
      VALUE=0
      VALUE_KWH=0
    else
      VALUE=$(echo ${DATA} | jq ".[-1].value")
      VALUE_KWH=$(python3 -c "print(round(float(${VALUE} * 0.08), 2))")
    fi
  fi
  /usr/bin/mqttools publish \
  --retain \
  --host ${MQTT_HOST} \
  --username ${MQTT_USERNAME} \
  --password ${MQTT_PASSWORD} \
  foxesscloud/${OPTION} \
  "$VALUE" 2>&1 > /dev/null

  # KW * TIME = KWH
  # 0.253 * 0.08 = KWH (the KW from foxess * 5 minutes as decimal)
  # $($VALUE * 0.08) | bc > mqtt
  /usr/bin/mqttools publish \
  --retain \
  --host ${MQTT_HOST} \
  --username ${MQTT_USERNAME} \
  --password ${MQTT_PASSWORD} \
  foxesscloud/${OPTION}_kwh \
  "$VALUE_KWH" 2>&1 > /dev/null
  # echo ${OPTION}_kWh=$( echo "${OPTION}_kWh + ${VALUE}" | bc ) >> etc/foxess_data
done
