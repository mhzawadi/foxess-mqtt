<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;

# State -> https://developers.home-assistant.io/docs/core/entity/sensor/#available-state-classes
# Device class -> https://www.home-assistant.io/integrations/sensor/

class mqtt extends json {

  protected $config;
  public function __construct($config){
    $this->config = $config;
  }

  /**
   * Setup MQTT
   *
   * Setup MQTT for new topics
   *
   * @return return type
   */
  public function setup_mqtt($deviceSN, $deviceType, $option_name, $option_unit) {
    $this->log('Start of MQTT setup for HA', 1);
    $this->log($option_unit, 1);
    $state_cla_name = '"state_class"';
    $state_cla = 'measurement';
    switch($option_unit){
      case '°C':
        $dev_cla = 'temperature';
        break;
      case '%':
        $dev_cla = 'power_factor';
        break;
      case 'V':
        $dev_cla = 'voltage';
        break;
      case 'A':
        $dev_cla = 'current';
        break;
      case 'kW':
        $dev_cla = 'power';
        switch($option_name){
          case 'todayYield':
            $state_cla = 'total_increasing';
            break;
        }
        break;
      case 'kWh':
        $dev_cla = 'energy';
        $state_cla = 'total';
        break;
      case 'Hz':
        $dev_cla = 'frequency';
        break;
      case 'kVar':
        $dev_cla = 'reactive_power';
        break;
      default:
        $dev_cla = 'text';
        $state_cla_name = '"mode"';
        $state_cla = 'text';
        $option_unit = null;
        break;
    }

    # Hack to get Temperature to work
    if(strstr($option_name, 'Temperature') !== false || strstr($option_name, 'Temperation') !== false ){
      $dev_cla = 'temperature';
      $option_unit = '°C';
    }

    $data = '{
    "name": "'.$option_name.'",
    "device": {
      "identifiers": "'.$deviceSN.'",
      "name": "'.$this->config->mqtt_topic.'-'.$deviceSN.'",
      "model": "'.$deviceType.'",
      "manufacturer": "FoxEss"
    },
    "stat_t": "~'.$option_name.'",
    "uniq_id": "'.$deviceSN.'-'.$option_name.'",
    "~": "'.$this->config->mqtt_topic.'/'.$deviceSN.'/",
    "unit_of_measurement": "'.$option_unit.'",
    "dev_cla": "'.$dev_cla.'",
    "exp_aft": 86400,
    '.$state_cla_name.': "'.$state_cla.'"
    }';
    $this->log('Post to MQTT '.$deviceSN.'-'.$option_name, 1);
    try {
      $this->post_mqtt('homeassistant/sensor/'.$deviceSN.'-'.$option_name.'/config', $data, true);
    } catch (Exception $e) {
      $this->log('[WARN] MQTT not yet ready, need to sleep on first run maybe', 1);
    }
  }

  /**
   * Post to MQTT
   *
   * Take some data and post to MQTT
   *
   * @param string topic
   * @param string data
   * @param bool retain
   * @return return type
   */
  public function post_mqtt($topic, $data, bool $retain = false) {
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
    $mqtt->publish($topic, $data, 0, $retain);
    $mqtt->disconnect();
  }
}
