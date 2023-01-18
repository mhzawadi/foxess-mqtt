#!/bin/bash

JSON=$(jq '.' foxess.data)
STATIC_JSON=foxess_data
OPTIONS=$(jq -r '.variables | length' ${STATIC_JSON})

# Get the data and find the bits
for (( i = 0 ; i < $OPTIONS; i++ ))
do
  OPTION=$(jq -r ".variables[${i}]" ${STATIC_JSON})
  LASTDATA=$(jq ".result.${OPTION}" foxess.data)
  if [ "$(jq -r '.result' /tmp/raw.json)" == "null" ]
  then
    VALUE_KWH=${LASTDATA}
  else
    DATA=$(jq ".result[] | select(.variable == \"${OPTION}\") | .data" /tmp/raw.json)
    if [[ $(echo ${DATA} | jq ".[-1].time" | grep -q "$(date +"%Y-%m-%d %H")") -eq 0 ]]
    then
      VALUE=$(echo $DATA | jq ".[-1].value")
      VALUE_KWH=$(python3 -c "print(round(float($LASTDATA + $VALUE), 2))")
    else
      VALUE=${LASTDATA}
      VALUE_KWH=${LASTDATA}
    fi
  fi
  JSON=$(echo $JSON | jq ".result.${OPTION} |= ${VALUE_KWH}")
  /usr/local/bin/mqttools publish \
  --retain \
  --host ${MQTT_HOST} \
  --username ${MQTT_USERNAME} \
  --password ${MQTT_PASSWORD} \
  foxesscloud/${OPTION}_kwh \
  "$VALUE_KWH" 2>&1 > /dev/null
done
echo $JSON > foxess.data
