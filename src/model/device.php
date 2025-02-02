<?php

namespace MHorwood\foxess_mqtt\model;
use MHorwood\foxess_mqtt\classes\json;
use MHorwood\foxess_mqtt\classes\logger;
use MHorwood\foxess_mqtt\model\mqtt;
use MHorwood\foxess_mqtt\model\request;
use MHorwood\foxess_mqtt\model\config;

class device extends json {

  protected $mqtt;
  protected $request;
  protected $config;
  public function __construct($config){
    $this->config = $config;
    $this->request = new request($config);
    $this->redis   = new mhredis($config);
    $this->mqtt  = new mqtt($config);

  }

  /**
   * Get the list of error codes
   *
   */
  public function get_error_codes() {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER,
      array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'lang: '.$this->config->foxess_lang,
        'X-Requested-With: XMLHttpRequest',
        'token: '
      )
    );
    curl_setopt_array ( $curl , [
      CURLOPT_URL => "https://www.foxesscloud.com/c/v0/errors/message",
      CURLOPT_RETURNTRANSFER => true
    ] );
    $return_data = json_decode(curl_exec($curl), true);
    curl_close($curl);
    if(empty($return_data) ){
      $this->log('Issue getting error codes', 3);
      return true;
    }elseif($return_data['errno'] > 0 ){
      $this->log($this->config->errno($return_data['errno']), 3);
      return true;
    }else{
      $this->redis->set('error_codes', $return_data['result']['messages'][$this->config->foxess_lang]);
      return false;
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
    $errors = $this->redis->get('error_codes');
    return $errors[$errno];
  }

  /**
   * Get the list of devices
   **/
  public function list(){
    $this->get_error_codes();
    $this->log('start of device listing', 1);
    $foxess_data = $this->redis->get('foxess_data');
    $data = '{"pageSize": 10, "currentPage": 1}';
    $url ='/op/v0/device/list';

    $this_curl = $this->request->sign_post($url, $data, $this->config->foxess_lang);
    if(empty($this_curl) ){
      $this->log('Empty device list', 3);
      return false;
    }

    $return_data = json_decode($this_curl, true);
    $this->request->getinfo();
    if($return_data['errno'] > 0 ){
      $this->log($this->errno($return_data['errno']), 3);
      return false;
    }else{
      $this->redis->set('devices', $return_data);
      $this->log('storing devices', 1);
      $foxess_data['device_total'] = $return_data['result']['total'];
      if(empty($foxess_data['devices'])){ // new config
        $this->log('New config time', 1);
        for( $device = 0; $device < $return_data['result']['total']; $device++ ){
          $foxess_data['devices'][$device] = $return_data['result']['data'][$device];
        }
      }else{ // we have config, update it
        $this->log('Update config time', 1);
        for( $device = 0; $device < $return_data['result']['total']; $device++ ){
          if(!is_array($foxess_data['devices'][$device])){
            $foxess_data['devices'][$device] = $return_data['result']['data'][$device];
          }
        }
      }
      if($this->redis->set('foxess_data', $foxess_data)){
        $this->log('Device list done', 1);
        $this->variable_list();
        return true;
      }else{
        $this->log('Redis didnt save', 1);
        return false;
      }
    }
  }

  /**
   * get device variables
   *
   * Undocumented function long description
   *
   * @param type var Description
   * @return return true
   */
  public function variable_list(){
    $this->log('Start of variable listing', 1);
    $foxess_data = $this->redis->get('foxess_data');
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){//for each device
      $url = '/op/v0/device/variable/get';
      $this_curl = $this->request->sign_get($url);
      $return_data = json_decode($this_curl, true);
      if(empty($this_curl) ){
        $this->log('Issue getting data', 3);
        return false;
      }elseif($return_data['errno'] > 0){
        $this->log('Getting variables, file not saved', 3);
        return false;
      }else{
        $this->redis->set($foxess_data['devices'][$device]['deviceSN'].'-variables', $return_data);
        $variables = $return_data['result'];
        $var_count = count($variables);
        $this->log('Storing variables', 1);
        $foxess_data['devices'][$device]['variable_list'] = array();
        for( $i = 0 ; $i < $var_count; $i++ ){
          $name = array_keys($variables[$i]);
          $variable_name = $name[0];
          $foxess_data['devices'][$device]['variable_list'][$i] = $variable_name;
        }
      }
    }
    if($this->redis->set('foxess_data', $foxess_data)){
      $this->log('all done', 1);
      return true;
    }else{
      $this->log('Redis didnt save', 1);
      return false;
    }
  }
}
