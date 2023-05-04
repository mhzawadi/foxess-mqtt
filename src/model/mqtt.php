<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\model\config;

class mqtt extends json {

  protected $config;
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      echo 'Missing config: ',  $e->getMessage(), "\n";
    }


  }

  /**
   * Setup MQTT
   *
   * Setup MQTT for new topics
   *
   * @return return type
   */
  public function setup_mqtt($foxess_data) {
    echo 'Start of MQTT setup for HA'."\n";
    foreach($foxess_data['result'] as $name => $value){
      $data = '{
      "name": "foxesscloud '.$name.'",
      "device": {
        "identifiers": "foxesscloud",
        "name": "foxesscloud",
        "model": "F5000",
        "manufacturer": "FoxEss"
      },
      "stat_t": "~'.$name.'",
      "uniq_id": "foxesscloud-'.$name.'",
      "~": "foxesscloud/",
      "unit_of_measurement": "KW",
      "dev_cla": "power",
      "exp_aft": 86400
      }';
      echo 'Post to MQTT foxesscloud-'.$name."\n";
      $this->post_mqtt('homeassistant/sensor/foxesscloud-'.$name.'/config', $data);
      $data = '{
        "name": "foxesscloud '.$name.'_kwh",
        "device": {
          "identifiers": "foxesscloud",
          "name": "foxesscloud",
          "model": "F5000",
          "manufacturer": "FoxEss"
        },
        "stat_t": "~'.$name.'_kwh",
        "uniq_id": "foxesscloud-'.$name.'_kwh",
        "~": "foxesscloud/",
        "unit_of_measurement": "kWh",
        "dev_cla": "energy",
        "state_class": "total_increasing",
        "exp_aft": 86400
      }';
      echo 'Post to MQTT foxesscloud-'.$name.'_kwh'."\n";
      try {
        $this->post_mqtt('homeassistant/sensor/foxesscloud-'.$name.'_kwh/config', $data);
      } catch (\Exception $e) {
        echo "MQTT not yet ready, need to sleep on first run maybe";
      }
    }
    $date = new \DateTimeImmutable;
    $time = $date->add(new \DateInterval("PT1H"));
    $foxess_data['setup'] = $time->format('U');
    $this->save_to_file('data/foxess_data.json', $foxess_data);
    echo 'Setup complete'."\n";
  }

  /**
   * Post to MQTT
   *
   * Take some data and post to MQTT
   *
   * @param string topic
   * @param string data
   * @return return type
   */
  public function post_mqtt($topic, $data) {
    $connectionSettings = (new \PhpMqtt\Client\ConnectionSettings)
      // The username used for authentication when connecting to the broker.
      ->setUsername($this->config->mqtt_user)
      // The password used for authentication when connecting to the broker.
      ->setPassword($this->config->mqtt_pass);
    $server   = $this->config->mqtt_host;
    $port     = $this->config->mqtt_port;
    $clientId = 'foxess_cloud_mqtt';

    $mqtt = new \PhpMqtt\Client\MqttClient($server, $port, $clientId);
    $mqtt->connect($connectionSettings, false);
    $mqtt->publish($topic, $data, 0);
    $mqtt->disconnect();
  }
}
