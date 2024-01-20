<?php

// TODO: Make sure we have an API KEY [x]
// TODO: Fail out if not [x]

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;

class Exception extends \Exception {}

class config extends json {

  public $foxess_username;
  public $foxess_password;
  public $foxess_apikey;
  public $foxess_lang;
  public $device_id;
  public $mqtt_host;
  public $mqtt_port;
  public $mqtt_user;
  public $mqtt_pass;
  public $mqtt_topic;

  public function __construct(){
    try {
      $config = $this->load_from_file('data/config.json');
      if( $this->foxess_username === 'changeme' &&
          $this->foxess_password === 'changeme' &&
          $this->foxess_apikey === 'changeme' ){
          throw new Exception('default config found');
      }

      $this->foxess_username = $config['foxess_username'];
      $this->foxess_password = $config['foxess_password'];
      $this->foxess_apikey = $config['foxess_apikey'];
      $this->device_id = $config['device_id'];
      $this->mqtt_host = $config['mqtt_host'];
      $this->mqtt_port = $config['mqtt_port'];
      $this->mqtt_user = $config['mqtt_user'];
      $this->mqtt_pass = $config['mqtt_pass'];
      $this->redis_server = $config['redis_server'];
      $this->redis_port = $config['redis_port'];
      if(!defined('log_level') && isset($config['log_level'])){
        define('log_level', $config['log_level']);
      }elseif(!defined('log_level') && getenv('LOG_LEVEL') !== false){
        define('log_level', getenv('LOG_LEVEL'));
      }elseif(!defined('log_level')){
        define('log_level', 2);
      }
      if(isset($config['mqtt_topic'])){
        $this->log('Using MQTT topic: '.$config['mqtt_topic'], 1, 3);
        $this->mqtt_topic = $config['mqtt_topic'];
      }else{
        $this->mqtt_topic = 'foxesscloud';
      }
      if(isset($config['total_over_time'])){
        $this->log('total over time is: '.var_export($config['total_over_time'], true), 1, 3);
        $this->total_over_time = $config['total_over_time'];
      }else{
        $this->total_over_time = 'true';
      }
      if(isset($config['foxess_apikey'])){
        $this->foxess_lang = $config['foxess_lang'];
      }else{
        $this->foxess_lang = 'en';
      }

    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 3, 1);
    }
  }

  /**
   * Whats the error message
   *
   * use our list of error codes and get the message for it
   *
   * @param int errno The error number
   * @return return string
   */
  public function errno($errno)
  {
    $errors = $this->load_from_file('data/error_codes.json');
    return $errors[$errno];
  }
}
