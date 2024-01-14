<?php

// TODO: Get device list -> return (https://www.foxesscloud.com/public/i18n/en/OpenApiDocument.html#get20device20list0a3ca20id3dget20device20list4303e203ca3e)
// TODO: process_data

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
  public function __construct(){
    try {
      $this->config = new config();
    } catch (Exception $e) {
      $this->log('Missing config: '.  $e->getMessage(), 1);
    }
    $this->request = new request();
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
    $this->save_to_file('data/error_codes.json', $return_data['result']['messages'][$this->config->foxess_lang]);
  }

  /**
   * Get the list of devices
   **/
  public function list(){
    $this->log('start of device listing', 1, 2);
    $foxess_data = $this->load_from_file('data/foxess_data.json');
    $data = '{"pageSize": 10, "currentPage": 1}';
    $url ='/op/v0/device/list';

    $this_curl = $this->request->sign_post($url, $data, $this->config->foxess_lang);
    if(empty($this_curl) ){
      $this->log('Empty device list', 3, 2);
      return false;
    }

    $return_data = json_decode($this_curl, true);
    if($return_data['errno'] > 0 ){
      $this->log($this->config->errno($return_data['errno']), 3, 2);
      return false;
    }else{
      $this->save_to_file('data/devices.json', $return_data);

      $this->log('storing devices', 1, 3);
      $foxess_data['device_total'] = $return_data['result']['total'];
      for( $device = 0; $device < $return_data['result']['total']; $device++ ){
        if(!is_array($foxess_data['devices'][$device])){
          $foxess_data['devices'][$device] = $return_data['result']['devices'][$device];
          $foxess_data['devices'][$device]['variables'] = $foxess_data['result'];
        }else{
          $foxess_data['devices'][$device]['generationTotal'] = $return_data['result']['devices'][$device]['generationTotal'];
          $foxess_data['devices'][$device]['generationToday'] = $return_data['result']['devices'][$device]['generationToday'];
        }
      }
      $this->save_to_file('data/foxess_data.json', $foxess_data);
      $this->log('all done', 1, 3);
      $this->variable_list();
      return true;
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
    $this->log('start of variable listing', 2);
    $foxess_data = $this->load_from_file('data/foxess_data.json');
    for( $device = 0; $device < $foxess_data['device_total']; $device++ ){//for each device
      $url = '/op/v0/device/variable/get';
      $return_data = $this->request->sign_get($url);
      $return_data = json_decode(curl_exec($curl), true);
      if($return_data['errno'] > 0){
        $this->log('[ERROR] getting variables, file not saved', 3);
        return false;
      }else{
        $this->save_to_file('data/'.$foxess_data['devices'][$device]['deviceSN'].'-variables.json', $return_data);
        $variables = $return_data['result']['variables'];
        $var_count = count($variables);
        $this->log('storing variables', 3);
        $foxess_data['devices'][$device]['variable_list'] = array();
        for( $i = 0 ; $i < $var_count; $i++ ){
          $foxess_data['devices'][$device]['variable_list'][$i] = $variables[$i]['variable'];
          if(!isset($foxess_data['devices'][$device]['variables'][$variables[$i]['variable']])){
            $foxess_data['devices'][$device]['variables'][$variables[$i]['variable']] = 0;
          }
        }
      }
    }
    $this->save_to_file('data/foxess_data.json', $foxess_data);
    $this->log('all done', 3);
    return true;
  }
}
