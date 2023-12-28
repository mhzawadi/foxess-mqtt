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
  public $mqtt_topic;

  public function __construct(){
    try {
      $config = $this->load_from_file('data/config.json');
      $this->foxess_username = $config['foxess_username'];
      $this->foxess_password = $config['foxess_password'];
      $this->foxess_apikey = $config['foxess_apikey'];
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
      if(isset($config['mqtt_topic'])){
        $this->log('Using MQTT topic: '.$config['mqtt_topic'], 3);
        $this->mqtt_topic = $config['mqtt_topic'];
      }else{
        $this->mqtt_topic = 'foxesscloud';
      }
      if(isset($config['total_over_time'])){
        $this->log('total over time is: '.var_export($config['total_over_time'], true), 3);
        $this->total_over_time = $config['total_over_time'];
      }else{
        $this->total_over_time = 'true';
      }
    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 1);
    }


    if( $this->foxess_username === 'changeme' &&
        $this->foxess_password === 'changeme' ){
        throw new Exception('default config found');
    }
  }

  /**
   * Get the list of error codes
   *
   */
  public function get_error_codes()
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER,
      array(
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 OPR/89.0.4447.83',
        'Accept: application/json, text/plain, */*',
        'lang: en',
        'sec-ch-ua-platform: macOS',
        'Sec-Fetch-Site: same-origin',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
        'Referer: https://www.foxesscloud.com/login?redirect=/',
        'Accept-Language: en-US;q=0.9,en;q=0.8,de;q=0.7,nl;q=0.6',
        'Connection: keep-alive',
        'X-Requested-With: XMLHttpRequest',
        'token: '
      )
    );
    curl_setopt_array ( $curl , [
      CURLOPT_URL => "https://www.foxesscloud.com/c/v0/errors/message",
      CURLOPT_RETURNTRANSFER => true
    ] );
    $return_data = json_decode(curl_exec($curl), true);
    $this->save_to_file('data/error_codes.json', $return_data['result']['messages']['en']);
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
