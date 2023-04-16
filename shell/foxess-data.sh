#!/bin/bash

STATIC_JSON=foxess_data
OPTIONS=$(jq -r '.variables | length' ${STATIC_JSON})

# Curl FOxEss Cloud for the date
collect_data(){
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
  jq > /tmp/raw.json

  if [ -s /tmp/raw.json ]
  then
    # Check if we need to login
    if [ $(jq -r '.errno' /tmp/raw.json) -eq 41809 ]
    then
      ~/bin/foxess-login.sh
      collect_data
    elif [ $(jq -r '.errno' /tmp/raw.json) -gt 0 ]
    then
      # if .errno greater then 0
      echo "We have an error getting data, we have logged in fine"
      exit 1
    fi
  else
    echo "We have an error getting data, the file is empty"
    exit 1
  fi

}

collect_data

# Get the data and find the bits
for (( i = 0 ; i < $OPTIONS; i++ ))
do
  OPTION=$(jq -r ".variables[${i}]" ${STATIC_JSON})
  if [ "$(jq -r '.result' /tmp/raw.json)" == "null" ]
  then
    VALUE=0
  else
    DATA=$(jq ".result[] | select(.variable == \"${OPTION}\") | .data" /tmp/raw.json)
    if [[ $(echo ${DATA} | jq ".[-1].time" | grep -q "$(date +"%Y-%m-%d %H")") -eq 0 ]]
    then
      VALUE=$(echo $DATA | jq ".[-1].value")
    else
      VALUE=0
    fi
  fi
  /usr/local/bin/mqttools publish \
  --retain \
  --host ${MQTT_HOST} \
  --username ${MQTT_USERNAME} \
  --password ${MQTT_PASSWORD} \
  foxesscloud/${OPTION} \
  "$VALUE" 2>&1 > /dev/null
done

# ~/bin/foxess-report.sh
