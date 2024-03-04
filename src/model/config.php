<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;

class config extends json {

  public $foxess_apikey;
  public $foxess_lang;
  public $mqtt_host;
  public $mqtt_port;
  public $mqtt_user;
  public $mqtt_pass;
  public $mqtt_topic;

  public function __construct(){
    try {
      $config = $this->load_from_file('data/config.json')
        or exit(date('Y-m-d H:i:s').' [ERROR] unable to start');
      if( $this->foxess_apikey === 'changeme' ){
          throw new Exception('default config found');
      }

      $this->foxess_apikey = $config['foxess_apikey'];
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
        $this->log('Using MQTT topic: '.$config['mqtt_topic'], 1);
        $this->mqtt_topic = $config['mqtt_topic'];
      }else{
        $this->mqtt_topic = 'foxesscloud';
      }
      if(isset($config['total_over_time'])){
        $this->log('total over time is: '.var_export($config['total_over_time'], true), 1);
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
      $this->log('Missing config: '.  $e->getMessage(), 3);
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

  /**
   * undocumented function summary
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return type
   */
  public function timestamp()
  {
    $date = new \DateTimeImmutable;
    $time = $date->add(new \DateInterval("PT6H"));
    $this->log('Setup complete', 1);
    return $time->format('U');
  }
}
