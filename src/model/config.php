<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;

class Exception extends \Exception {}

class config extends json {

  public $foxess_username;
  public $foxess_password;
  public $device_id;
  public $mqtt_host;
  public $mqtt_port;
  public $mqtt_user;
  public $mqtt_pass;

  public function __construct(){
    try {
      $config = $this->load_from_file('data/config.json');
      $this->foxess_username = $config['foxess_username'];
      $this->foxess_password = $config['foxess_password'];
      $this->device_id = $config['device_id'];
      $this->mqtt_host = $config['mqtt_host'];
      $this->mqtt_port = $config['mqtt_port'];
      $this->mqtt_user = $config['mqtt_user'];
      $this->mqtt_pass = $config['mqtt_pass'];
      if(!defined('log_level') && isset($config['log_level'])){
        define('log_level', $config['log_level']);
      }elseif(!defined('log_level') && getenv('LOG_LEVEL') !== false){
        define('log_level', getenv('LOG_LEVEL'));
      }elseif(!defined('log_level')){
        define('log_level', 2);
      }
    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 1);
    }


    if( $this->foxess_username === 'changeme' &&
        $this->foxess_password === 'changeme' ){
        throw new Exception('default config found');
    }
  }
}
