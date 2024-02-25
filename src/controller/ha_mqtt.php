<?php

// Public API - https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html

namespace MHorwood\foxess_mqtt\controller;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\config;
use MHorwood\foxess_mqtt\model\mhredis;

class ha_mqtt extends json {
  protected $config;
  public function __construct($config){
    $this->config = $config;
  }

  public function subscribe_mqtt($topic, $data, bool $retain = false) {
    try {
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

      // Subscribe to the topic 'foo/bar/baz' using QoS 0.
      $client->subscribe('house/ha/status', function (string $topic, string $message, bool $retained) use ($logger, $client) {
        $this->log("We received a $typeOfMessage on topic [$topic]: $message");
        $this->foxess_data = $this->redis->get('foxess_data');
        $this->foxess_data['setup'] = '0';
        $this->redis->set('foxess_data', $this->foxess_data);
      }, MqttClient::QOS_AT_MOST_ONCE);

      // Since subscribing requires to wait for messages, we need to start the client loop which takes care of receiving,
      // parsing and delivering messages to the registered callbacks. The loop will run indefinitely, until a message
      // is received, which will interrupt the loop.
      $client->loop(true);

      // Gracefully terminate the connection to the broker.
      $client->disconnect();
    } catch (MqttClientException $e) {
      // MqttClientException is the base exception of all exceptions in the library. Catching it will catch all MQTT related exceptions.
      $this->log('Subscribing to a topic using QoS 0 failed. An exception occurred. '.  $e->getMessage(), 3);
    }
  }
}
