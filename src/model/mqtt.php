<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;

class mqtt extends json {

  protected $config;
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      $this->log('Missing config: '. $e->getMessage(), 1);
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
    $this->log('Start of MQTT setup for HA', 3);
    foreach($foxess_data['result'] as $name => $value){
      if(strstr($name, 'Temperature') !== false || strstr($name, 'Soc') !== false ||
          strstr($name, 'Temperation') !== false
        ){
        $dev_cla = 'temperature';
        $unit = '°C';
      }elseif(strstr($name, 'Volt') !== false){
        $dev_cla = 'voltage';
        $unit = 'V';
      }elseif(strstr($name, 'Current') !== false){
        $dev_cla = 'current';
        $unit = 'A';
      }else{
        $dev_cla = 'power';
        $unit = 'kW';
        $data_kwh = '{
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
        $this->log('Post to MQTT foxesscloud-'.$name.'_kwh', 3);
        try {
          $this->post_mqtt('homeassistant/sensor/foxesscloud-'.$name.'_kwh/config', $data_kwh);
        } catch (\Exception $e) {
          $this->log('MQTT not yet ready, need to sleep on first run maybe', 1);
        }
      }
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
      "unit_of_measurement": "'.$unit.'",
      "dev_cla": "'.$dev_cla.'",
      "exp_aft": 86400
      }';
      $this->log('Post to MQTT foxesscloud-'.$name, 3);
      try {
        $this->post_mqtt('homeassistant/sensor/foxesscloud-'.$name.'/config', $data);
      } catch (Exception $e) {
        $this->log('MQTT not yet ready, need to sleep on first run maybe', 1);
      }
    }
    $date = new \DateTimeImmutable;
    $time = $date->add(new \DateInterval("PT1H"));
    $this->log('Setup complete', 3);
    return $time->format('U');
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
