<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;

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
    $this->log('Start of MQTT setup for HA', 1, 3);
    $this->log($option_unit, 1, 3);
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
        break;
      case 'kWh':
        $dev_cla = 'energy';
        break;
      case 'Hz':
        $dev_cla = 'frequency';
        break;
      case 'kVar':
        $dev_cla = 'reactive_power';
        break;
      default:
        $dev_cla = '';
        $option_unit = null;
        break;
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
    "exp_aft": 86400
    }';
    $this->log('Post to MQTT '.$deviceSN.'-'.$option_name, 1, 3);
    try {
      $this->post_mqtt('homeassistant/sensor/'.$deviceSN.'-'.$option_name.'/config', $data);
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
